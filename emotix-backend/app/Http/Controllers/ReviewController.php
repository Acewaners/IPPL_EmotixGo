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

        // 🔹 Hitung sentiment dari kolom `sentiment`
        $positive = $reviews->where('sentiment', 'positive')->count();
        $neutral  = $reviews->where('sentiment', 'neutral')->count();
        $negative = $reviews->where('sentiment', 'negative')->count();

        // Kalau semua nol (belum dianalisis AI atau review hanya bintang), fallback ke rating
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


    public function destroy(Request $r, Review $review)
    {
        // 1. Cek Authorisasi (Pastikan yang menghapus adalah pemilik review)
        if ($review->buyer_id !== $r->user()->user_id) {
            return response()->json(['message' => 'Anda tidak berhak menghapus ulasan ini.'], 403);
        }

        // 2. Hapus Sentimen terkait (Membersihkan sampah data)
        Sentiment::where('review_id', $review->review_id)->delete();

        // 3. Hapus Review
        $review->delete();

        return response()->json(['message' => 'Ulasan berhasil dihapus.']);
    }
    /**
     * Buat / update review untuk 1 produk
     */
    public function store(Request $r)
    {
        // 1. Validasi Fleksibel (Required Without)
        $data = $r->validate([
            'product_id'  => 'required|integer|exists:products,product_id',
            // Review text wajib ada JIKA rating kosong
            'review_text' => 'required_without:rating|nullable|string',
            // Rating wajib ada JIKA review_text kosong
            'rating'      => 'required_without:review_text|nullable|integer|min:1|max:5',
        ]);

        $hasPurchased = TransactionDetail::where('product_id', $data['product_id'])
            ->whereHas('transaction', function($q) use ($r) {
                $q->where('buyer_id', $r->user()->user_id)
                  ->where('status', 'completed'); // Wajib status selesai
            })->exists();

        // Jika belum beli atau transaksi belum selesai, tolak!
        if (!$hasPurchased) {
            return response()->json([
                'message' => 'Anda harus membeli produk ini dan menyelesaikan pesanan (status Completed) sebelum memberikan ulasan.'
            ], 403); // 403 Forbidden
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

        // Safety check: Jika user hanya kasih teks tapi AI gagal menebak bintang, fallback ke 3
        if (!$finalRating) {
             $finalRating = 3; 
        }

        // 4. Update Database
        $review = Review::updateOrCreate(
            [
                'buyer_id'   => $r->user()->user_id,
                'product_id' => $data['product_id'],
            ],
            [
                'review_text' => $data['review_text'] ?? null, // Bisa null jika user cuma kasih bintang
                'rating'      => $finalRating,
                'sentiment'   => $sentimentLabel, // Bisa null jika tanpa teks
            ]
        );

        // 5. Simpan Log Sentimen (Hanya jika ada hasil sentimen)
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
            // Opsional: Jika user mengubah review jadi "hanya bintang", kita bisa hapus record sentimen lama
            // Sentiment::where('review_id', $review->review_id)->delete();
        }

        return $review->load('product', 'buyer', 'sentimentRecord');
    }

    /**
     * Update review (teks / rating)
     */
    public function update(Request $r, Review $review)
    {
        // 1. Cek Authorisasi
        abort_unless($review->buyer_id === $r->user()->user_id, 403);

        // 2. Validasi Input (Sama fleksibelnya dengan store)
        $data = $r->validate([
            'review_text' => 'required_without:rating|nullable|string',
            'rating'      => 'required_without:review_text|nullable|integer|min:1|max:5',
        ]);

        $sentimentLabel = $review->sentiment; // Default pakai sentimen lama
        $ai = [];

        // 3. 🧠 PANGGIL AI (Hanya jika teks ada dan berubah)
        if (!empty($data['review_text'])) {
            // Jika teks berubah atau baru ditambahkan, jalankan AI
            if ($data['review_text'] !== $review->review_text) {
                $ai = $this->sentiment->analyze($data['review_text']);
                $sentimentLabel = $ai['label'] ?? null;
            }
        } else {
            // Jika user menghapus teks (hanya sisa bintang), hapus sentimen
            $sentimentLabel = null;
        }

        // 4. Lakukan Update ke Database
        $review->update([
            'review_text' => $data['review_text'] ?? null,
            'rating'      => $data['rating'] ?? $review->rating, // Jika rating tidak dikirim, pakai yang lama
            'sentiment'   => $sentimentLabel,
        ]);

        // 5. Update Log Sentimen
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

        // Return data fresh
        return response()->json($review->fresh()->load('product'));
    }
}