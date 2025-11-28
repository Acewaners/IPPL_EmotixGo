<?php

// app/Http/Controllers/ReviewController.php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function byProduct(Product $product)
{
    $reviews = Review::with('buyer')
        ->where('product_id', $product->product_id)
        ->latest()
        ->get();

    $count = $reviews->count();

    $avgRating = $count ? round($reviews->avg('rating'), 1) : 0;

    // klasifikasi sentiment sederhana dari rating
    $positive = $reviews->where('rating', '>=', 4)->count();
    $neutral  = $reviews->where('rating', 3)->count();
    $negative = $reviews->where('rating', '<=', 2)->count();

    return response()->json([
        'data' => $reviews,
        'meta' => [
            'count'      => $count,
            'avg_rating' => $avgRating,
            'distribution' => [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ],
            'sentiment' => [
                'positive' => $positive,
                'neutral'  => $neutral,
                'negative' => $negative,
            ],
        ],
    ]);
}
    // List review milik buyer yang login
    public function myReviews(Request $r)
    {
        $reviews = Review::where('buyer_id', $r->user()->user_id)
            ->with('product')
            ->latest()
            ->get();

        return response()->json($reviews);
    }

        public function indexMe(Request $r)
    {
        $userId = $r->user()->user_id;

        $reviews = Review::with('product')
            ->where('buyer_id', $userId)
            ->latest('created_at')
            ->get();

        return response()->json($reviews);
    }

    // Buat / update review untuk 1 produk
public function store(Request $r)
{
    $data = $r->validate([
        'product_id'   => 'required|integer|exists:products,product_id',
        'review_text'  => 'required|string|min:5',
        'rating'       => 'required|integer|min:1|max:5', // ⬅️ rating 1–5
    ]);

    // cek: buyer pernah beli produk ini dan status transaksi completed
    $hasBought = TransactionDetail::where('product_id', $data['product_id'])
        ->whereHas('transaction', function ($q) use ($r) {
            $q->where('buyer_id', $r->user()->user_id)
              ->where('status', 'completed');
        })
        ->exists();

    abort_unless($hasBought, 422, 'You can only review completed orders.');

    // 1 buyer 1 review per produk -> updateOrCreate
    $review = Review::updateOrCreate(
        [
            'buyer_id'   => $r->user()->user_id,
            'product_id' => $data['product_id'],
        ],
        [
            'review_text' => $data['review_text'],
            'rating'      => $data['rating'], // ⬅️ simpan rating
            'sentiment'   => null,            // nanti diisi AI
        ]
    );

    return $review->load('product');
}


    // Edit review
    public function update(Request $r, Review $review)
    {
        // pastiin yang edit adalah pemilik review
        abort_unless($review->buyer_id === $r->user()->user_id, 403);

        $data = $r->validate([
            'review_text' => 'required|string|min:5',
            'rating'      => 'nullable|integer|min:1|max:5',
        ]);

        $review->update($data);

        return response()->json($review->fresh()->load('product'));
    }
}
