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

    public function byProduct(Product $product)
    {
        $reviews = Review::with(['buyer', 'sentimentRecord'])
            ->where('product_id', $product->product_id)
            ->latest()
            ->get();

        $count = $reviews->count();
        $avgRating = $count ? round($reviews->avg('rating'), 1) : 0;

        $positive = $reviews->where('sentiment', 'positive')->count();
        $neutral  = $reviews->where('sentiment', 'neutral')->count();
        $negative = $reviews->where('sentiment', 'negative')->count();

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


    public function destroy(Request $r, Review $review)
    {
        if ($review->buyer_id !== $r->user()->user_id) {
            return response()->json(['message' => 'Anda tidak berhak menghapus ulasan ini.'], 403);
        }
        Sentiment::where('review_id', $review->review_id)->delete();
        $review->delete();
        return response()->json(['message' => 'Ulasan berhasil dihapus.']);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'product_id'  => 'required|integer|exists:products,product_id',
            'review_text' => 'required_without:rating|nullable|string',
            'rating'      => 'required_without:review_text|nullable|integer|min:1|max:5',
        ]);

        $hasPurchased = TransactionDetail::where('product_id', $data['product_id'])
            ->whereHas('transaction', function($q) use ($r) {
                $q->where('buyer_id', $r->user()->user_id)
                  ->where('status', 'completed');
            })->exists();

        if (!$hasPurchased) {
            return response()->json([
                'message' => 'Anda harus membeli produk ini dan menyelesaikan pesanan (status Completed) sebelum memberikan ulasan.'
            ], 403);
        }

        $aiStars = null;
        $sentimentLabel = null;
        $ai = [];

        if (!empty($data['review_text'])) {
            $ai = $this->sentiment->analyze($data['review_text']);
            $sentimentLabel = $ai['label'] ?? null;
            $aiStars = $ai['stars'] ?? null;
        }

        $userRating = $data['rating'] ?? null;
        $finalRating = $userRating ?: $aiStars;

        if (!$finalRating) {
             $finalRating = 3;
        }

        $review = Review::updateOrCreate(
            [
                'buyer_id'   => $r->user()->user_id,
                'product_id' => $data['product_id'],
            ],
            [
                'review_text' => $data['review_text'] ?? null,
                'rating'      => $finalRating,
                'sentiment'   => $sentimentLabel,
            ]
        );

        if ($sentimentLabel) {
            Sentiment::updateOrCreate(
                ['review_id' => $review->review_id],
                [
                    'category'      => $sentimentLabel,
                    'model_version' => $ai['model'] ?? 'indo-roberta',
                    'analyzed_at'   => now(),
                ]
            );
        } else {
            // Sentiment::where('review_id', $review->review_id)->delete();
        }

        return $review->load('product', 'buyer', 'sentimentRecord');
    }

    public function update(Request $r, Review $review)
    {
        abort_unless($review->buyer_id === $r->user()->user_id, 403);

        $data = $r->validate([
            'review_text' => 'required_without:rating|nullable|string',
            'rating'      => 'required_without:review_text|nullable|integer|min:1|max:5',
        ]);

        $sentimentLabel = $review->sentiment;
        $ai = [];

        if (!empty($data['review_text'])) {
            if ($data['review_text'] !== $review->review_text) {
                $ai = $this->sentiment->analyze($data['review_text']);
                $sentimentLabel = $ai['label'] ?? null;
            }
        } else {
            $sentimentLabel = null;
        }

        $review->update([
            'review_text' => $data['review_text'] ?? null,
            'rating'      => $data['rating'] ?? $review->rating,
            'sentiment'   => $sentimentLabel,
        ]);

        if ($sentimentLabel) {
            Sentiment::updateOrCreate(
                ['review_id' => $review->review_id],
                [
                    'category'      => $sentimentLabel,
                    'model_version' => $ai['model'] ?? 'update-v1',
                    'analyzed_at'   => now(),
                ]
            );
        }

        return response()->json($review->fresh()->load('product'));
    }
}
