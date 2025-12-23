<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // jika seller login, tampilkan hanya produknya sendiri
        $query = Product::with(['category', 'seller']);

        if ($request->user() && $request->user()->role === 'seller') {
            $query->where('seller_id', $request->user()->user_id);
        }

        // opsional: sort terbaru dulu
        $products = $query->orderByDesc('product_id')->get();

        // Tambahkan logika ini untuk mengubah path gambar menjadi URL lengkap
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = Storage::disk('s3')->url($product->image);
            }
            return $product;
        });

        return response()->json([
            'data' => $products,
        ]);
    }

    // POST /api/products
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

        // PERBAIKAN PENTING: Gunakan 's3', BUKAN 'public'
        if ($request->hasFile('image')) {
            $product->image = $request->file('image')->store('products', 's3');
        }

        $product->save();

        return response()->json($product->load('category','seller'), 201);
    }

    // PUT/PATCH /api/products/{product}
    public function update(Request $request, Product $product)
    {
        // 1. Validasi Input
        $data = $request->validate([
            'product_name' => ['sometimes','required','string','max:100'],
            'price'        => ['sometimes','required','numeric','min:0'],
            'stock'        => ['sometimes','required','integer','min:0'],
            'description'  => ['nullable','string'],
            'image'        => ['nullable','image','max:2048'],
            'category_id'  => ['sometimes','required','exists:categories,id'],
        ]);

        // 2. Update Data (hanya jika ada input)
        if ($request->filled('category_id')) {
            $product->category_id = $request->integer('category_id');
        }

        if (array_key_exists('product_name', $data)) $product->product_name = $data['product_name'];
        if (array_key_exists('price', $data))        $product->price       = $data['price'];
        if (array_key_exists('stock', $data))        $product->stock       = $data['stock'];
        if (array_key_exists('description', $data))  $product->description = $data['description'];

        // 3. Handle Upload Gambar (FIXED: Pakai S3)
        if ($request->hasFile('image')) {
            // Hapus gambar lama di S3 jika ada
            if ($product->image) {
                Storage::disk('s3')->delete($product->image);
            }
            // Simpan gambar baru ke S3
            $product->image = $request->file('image')->store('products', 's3');
        }

        // 4. Simpan ke Database
        $product->save(); // <--- Baris yang tadi error sekarang aman di dalam kurung kurawal fungsi

        return response()->json($product->load('category','seller'));
    }

    // DELETE /api/products/{product}
    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
