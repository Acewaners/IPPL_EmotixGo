<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Transaction;
use App\Jobs\AnalyzeReviewJob;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $r){
        $data = $r->validate([
            'product_id'=>'required|integer|exists:products,product_id',
            'review_text'=>'required|string'
        ]);

        // opsional: pastikan pernah membeli dan completed
        $product = Product::findOrFail($data['product_id']);
        $purchased = Transaction::where('buyer_id',$r->user()->user_id)
            ->where('seller_id',$product->seller_id)
            ->where('status','completed')->exists();
        if(!$purchased){
            return response()->json(['message'=>'Anda belum menyelesaikan pembelian'],422);
        }

        $review = Review::create([
            'product_id'=>$data['product_id'],
            'buyer_id'=>$r->user()->user_id,
            'review_text'=>$data['review_text'],
            'analysis_status'=>'pending'
        ]);

        dispatch(new AnalyzeReviewJob($review->review_id));
        return response()->json($review,201);
    }

    public function byProduct($productId){
        return Review::where('product_id',$productId)
            ->orderByDesc('review_id')->paginate(10);
    }
}
