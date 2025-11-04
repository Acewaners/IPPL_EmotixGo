<?php

namespace App\Jobs;

use App\Models\Review;
use App\Models\Sentiment;
use App\Services\SentimentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AnalyzeReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reviewId)
    {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(SentimentService $svc): void
    {
        $review = Review::findOrFail($this->reviewId);
        if($review->analysis_status === 'done') return;

        $review->update(['analysis_status' => 'processing']);

        try {
            $res = $svc->analyze($review->review_text);

            DB::transaction(function() use ($review, $res) {
                Sentiment::create([
                    'review_id' => $review->review_id,
                    'category' => $res['category'],
                    'model_version' => 'HF:'.parse_url(config('services.huggingface.url'), PHP_URL_PATH),
                ]);

                $review->update([
                    'sentiment' => $res['category'],
                    'analysis_status' => 'done'
                ]);
            });
        } catch (\Throwable $e) {
            $review->update(['analysis_status' => 'pending']);
            throw $e;
        }
    }
}
