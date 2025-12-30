<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Review;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $query = Product::with(['category', 'seller'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if ($request->user() && $request->user()->role === 'seller') {
            $query->where('seller_id', $request->user()->user_id);
        }

        $products = $query->orderByDesc('product_id')->get()
            ->map(function ($p) {
                $p->rating = round($p->reviews_avg_rating ?? 0, 1);
                $p->rating_count = $p->reviews_count;
                unset($p->reviews_avg_rating);
                return $p;
            });

        return response()->json([
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_name' => ['required','string','max:100'],
            'price'        => ['required','numeric','min:0'],
            'stock'        => ['required','integer','min:0'],
            'description'  => ['nullable','string'],
            'image'        => ['nullable','image','max:2048'],
            'category_id'  => ['required','exists:categories,id'],
        ]);

        $product              = new Product();
        $product->seller_id   = $request->user()->user_id;
        $product->category_id = $request->integer('category_id');
        $product->product_name= $data['product_name'];
        $product->price       = $data['price'];
        $product->stock       = $data['stock'];
        $product->description = $data['description'] ?? null;

        if ($request->hasFile('image')) {
            try {

                $cloudinary = new \Cloudinary\Cloudinary([
                    'cloud' => [
                        'cloud_name' => 'dx1mtttj0',
                        'api_key'    => '498197175883194',
                        'api_secret' => 'EEfdW5UqfkSaaHQ7Mcbw5k3QjOE',
                    ],
                    'url' => [
                        'secure' => true
                    ]
                ]);

                $uploadedFile = $request->file('image');

                $result = $cloudinary->uploadApi()->upload($uploadedFile->getRealPath(), [
                    'folder' => 'products'
                ]);

                $product->image = $result['secure_url'];

                \Illuminate\Support\Facades\Log::info('✅ Upload Manual Berhasil: ' . $result['secure_url']);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('❌ Upload Manual Gagal: ' . $e->getMessage());
                return response()->json(['message' => 'Upload Failed', 'error' => $e->getMessage()], 500);
            }
        }

        $product->save();

        return response()->json($product->load('category','seller'), 201);
    }

    public function bestSelling()
    {
            $products = Product::where('sold', '>', 0)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('sold', 'desc')
            ->take(4)
            ->get();

        $products->transform(function ($product) {
            $product->rating = round($product->reviews_avg_rating ?? 0, 1);
            $product->rating_count = $product->reviews_count ?? 0;
            return $product;
        });

        return response()->json(['data' => $products]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'product_name' => ['sometimes','required','string','max:100'],
            'price'        => ['sometimes','required','numeric','min:0'],
            'stock'        => ['sometimes','required','integer','min:0'],
            'description'  => ['nullable','string'],
            'image'        => ['nullable','image','max:2048'],
            'category_id'  => ['sometimes','required','exists:categories,id'],
        ]);

        if ($request->filled('category_id')) {
            $product->category_id = $request->integer('category_id');
        }

        if (array_key_exists('product_name', $data)) $product->product_name = $data['product_name'];
        if (array_key_exists('price', $data))        $product->price       = $data['price'];
        if (array_key_exists('stock', $data))        $product->stock       = $data['stock'];
        if (array_key_exists('description', $data))  $product->description = $data['description'];

        // --- LOGIKA UPLOAD UPDATE (MANUAL) ---
        if ($request->hasFile('image')) {
            try {
                $cloudinary = new \Cloudinary\Cloudinary([
                    'cloud' => [
                        'cloud_name' => 'dx1mtttj0',
                        'api_key'    => '498197175883194',
                        'api_secret' => 'EEfdW5UqfkSaaHQ7Mcbw5k3QjOE',
                    ],
                    'url' => [
                        'secure' => true
                    ]
                ]);

                if ($product->image && str_contains($product->image, 'cloudinary')) {
                    try {

                        $path = parse_url($product->image, PHP_URL_PATH);
                        $filename = pathinfo($path, PATHINFO_FILENAME);
                        $publicId = 'products/' . $filename;

                        $cloudinary->uploadApi()->destroy($publicId);
                        \Illuminate\Support\Facades\Log::info('🗑️ Hapus gambar lama: ' . $publicId);
                    } catch (\Exception $e) {

                        \Illuminate\Support\Facades\Log::warning('⚠️ Gagal hapus gambar lama: ' . $e->getMessage());
                    }
                }

                $uploadedFile = $request->file('image');
                $result = $cloudinary->uploadApi()->upload($uploadedFile->getRealPath(), [
                    'folder' => 'products'
                ]);

                $product->image = $result['secure_url'];
                \Illuminate\Support\Facades\Log::info('✅ Update Gambar Berhasil: ' . $result['secure_url']);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('❌ Update Gambar Gagal: ' . $e->getMessage());
                return response()->json(['message' => 'Upload Failed', 'error' => $e->getMessage()], 500);
            }
        }

        $product->save();

        return response()->json($product->load('category','seller'));
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

        public function reviews(Product $product)
    {
        $reviews = Review::where('product_id', $product->product_id)
            ->with('buyer')
            ->latest()
            ->get();

        $meta = [
            'count'      => $reviews->count(),
            'avg_rating' => round($reviews->avg('rating') ?? 0, 1),

            'distribution' => [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ],

            'sentiment'  => [
                'positive' => $reviews->where('sentiment', 'positive')->count(),
                'neutral'  => $reviews->where('sentiment', 'neutral')->count(),
                'negative' => $reviews->where('sentiment', 'negative')->count(),
            ],
        ];

        return response()->json([
            'data' => $reviews,
            'meta' => $meta,
        ]);
    }

}
