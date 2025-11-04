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
            'category_id'  => ['required','exists:categories,id'], // ✅ wajib
        ]);

        $product              = new Product();
        $product->seller_id   = $request->user()->user_id; // asumsi pakai auth
        $product->category_id = $request->integer('category_id'); // ✅ simpan kategori
        $product->product_name= $data['product_name'];
        $product->price       = $data['price'];
        $product->stock       = $data['stock'];
        $product->description = $data['description'] ?? null;

        if ($request->hasFile('image')) {
            $product->image = $request->file('image')->store('products', 'public');
        }

        $product->save();

        return response()->json($product->load('category','seller'), 201);
    }

    // PUT/PATCH /api/products/{product}
    public function update(Request $request, Product $product)
    {
        // pastikan route binding pakai product_id di model
        $data = $request->validate([
            'product_name' => ['sometimes','required','string','max:100'],
            'price'        => ['sometimes','required','numeric','min:0'],
            'stock'        => ['sometimes','required','integer','min:0'],
            'description'  => ['nullable','string'],
            'image'        => ['nullable','image','max:2048'],
            'category_id'  => ['sometimes','required','exists:categories,id'], // ✅ boleh diubah
        ]);

        if ($request->filled('category_id')) {
            $product->category_id = $request->integer('category_id'); // ✅ simpan kategori (edit)
        }

        if (array_key_exists('product_name', $data)) $product->product_name = $data['product_name'];
        if (array_key_exists('price', $data))        $product->price       = $data['price'];
        if (array_key_exists('stock', $data))        $product->stock       = $data['stock'];
        if (array_key_exists('description', $data))  $product->description = $data['description'];

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $product->image = $request->file('image')->store('products', 'public');
        }

        $product->save();

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