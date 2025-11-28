<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\TransactionDetail;
use App\Models\Sentiment;
use Illuminate\Http\Request;
use App\Services\SentimentService;

class ReviewController extends Controller
{
    protected SentimentService $sentiment;

    public function __construct(SentimentService $sentiment)
    {
        $this->sentiment = $sentiment;
    }

    /**
     * Review per product (dipakai di halaman detail produk)
     */
    public function byProduct(Product $product)
    {
        $reviews = Review::with(['buyer', 'sentimentRecord'])
            ->where('product_id', $product->product_id)
            ->latest()
            ->get();

        $count = $reviews->count();
        $avgRating = $count ? round($reviews->avg('rating'), 1) : 0;

        // 🔹 Hitung sentiment dari kolom `sentiment` dulu
        $positive = $reviews->where('sentiment', 'positive')->count();
        $neutral  = $reviews->where('sentiment', 'neutral')->count();
        $negative = $reviews->where('sentiment', 'negative')->count();

        // Kalau semua nol (belum dianalisis AI), fallback ke rating
        if ($positive + $neutral + $negative === 0) {
            $positive = $reviews->where('rating', '>=', 4)->count();
            $neutral  = $reviews->where('rating', 3)->count();
            $negative = $reviews->where('rating', '<=', 2)->count();
        }

        return response()->json([
            'data' => $reviews,
            'meta' => [
                'count'        => $count,
                'avg_rating'   => $avgRating,
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

    /**
     * Semua review milik buyer yang login
     */
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

    /**
     * Buat / update review untuk 1 produk
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'product_id'  => 'required|integer|exists:products,product_id',
            'review_text' => 'required|string|min:5',
            'rating'      => 'nullable|integer|min:1|max:5',
        ]);

        // 🔒 Cek: user benar-benar pernah beli & status completed
        $hasBought = TransactionDetail::where('product_id', $data['product_id'])
            ->whereHas('transaction', function ($q) use ($r) {
                $q->where('buyer_id', $r->user()->user_id)
                  ->where('status', 'completed');
            })
            ->exists();

        abort_unless($hasBought, 422, 'You can only review completed orders.');

        // 🔹 Panggil AI untuk analisis teks
        $ai = $this->sentiment->analyze($data['review_text']);  // bisa null kalau error

        // label dari AI (positive / neutral / negative)
        $sentimentLabel = $ai['label'] ?? null;

        // fallback kalau AI gagal tapi ada rating
        if (!$sentimentLabel && !empty($data['rating'])) {
            if ($data['rating'] >= 4) {
                $sentimentLabel = 'positive';
            } elseif ($data['rating'] == 3) {
                $sentimentLabel = 'neutral';
            } else {
                $sentimentLabel = 'negative';
            }
        }

        // Tentukan rating final:
        $finalRating = $data['rating'] ?? ($ai['stars'] ?? null);

        // 🔁 1 buyer hanya boleh 1 review per produk
        $review = Review::updateOrCreate(
            [
                'buyer_id'   => $r->user()->user_id,
                'product_id' => $data['product_id'],
            ],
            [
                'review_text' => $data['review_text'],
                'rating'      => $finalRating,
                'sentiment'   => $sentimentLabel,
            ]
        );

        // 🔹 Simpan ke tabel `sentiments` (log analisis AI)
        if ($sentimentLabel) {
            Sentiment::updateOrCreate(
                ['review_id' => $review->review_id],
                [
                    'category'     => $sentimentLabel,
                    'model_version'=> $ai['model'] ?? 'huggingface',
                    'analyzed_at'  => now(),
                ]
            );
        }

        return $review->load('product', 'buyer', 'sentimentRecord');
    }

    /**
     * Update review (teks / rating)
     * (kalau mau, nanti bisa sekalian re-run AI di sini juga)
     */
    public function update(Request $r, Review $review)
    {
        abort_unless($review->buyer_id === $r->user()->user_id, 403);

        $data = $r->validate([
            'review_text' => 'required|string|min:5',
            'rating'      => 'nullable|integer|min:1|max:5',
        ]);

        $review->update($data);

        return response()->json($review->fresh()->load('product'));
    }
}
