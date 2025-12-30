<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\AnalyzeReviewJob;
use App\Services\SentimentService;
use App\Models\Review;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AnalyzeJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_does_nothing_if_review_is_already_done()
    {
        // 1. Setup: Review yang sudah 'done'
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test']);
        $product = Product::create(['seller_id' => $user->user_id, 'category_id' => $category->id, 'product_name' => 'A', 'price' => 1000, 'stock' => 10]);
        
        $review = Review::create([
            'buyer_id' => $user->user_id,
            'product_id' => $product->product_id,
            'review_text' => 'Bagus',
            'rating' => 5,
            'analysis_status' => 'done' // <--- SUDAH DONE
        ]);

        $mockService = Mockery::mock(SentimentService::class);
        $mockService->shouldReceive('analyze')->never();

        // 2. Act
        $job = new AnalyzeReviewJob($review->review_id);
        $job->handle($mockService);

        // 3. Assert
        $this->assertTrue(true);
    }

    public function test_job_resets_status_to_pending_if_error_occurs()
    {
        // 1. Setup: Review baru (pending)
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test']);
        $product = Product::create(['seller_id' => $user->user_id, 'category_id' => $category->id, 'product_name' => 'A', 'price' => 1000, 'stock' => 10]);

        $review = Review::create([
            'buyer_id' => $user->user_id,
            'product_id' => $product->product_id,
            'review_text' => 'Rusak parah',
            'rating' => 1,
            'analysis_status' => 'processing' 
        ]);

        $mockService = Mockery::mock(SentimentService::class);
        $mockService->shouldReceive('analyze')->andThrow(new \Exception("AI Down"));

        $job = new AnalyzeReviewJob($review->review_id);
        
        try {
            $job->handle($mockService);
        } catch (\Exception $e) {
            
        }

        // 3. Assert (Whitebox Check)
        $this->assertDatabaseHas('reviews', [
            'review_id' => $review->review_id,
            'analysis_status' => 'pending'
        ]);
    }
}