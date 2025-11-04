<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SentimentService
{
    public function analyze(string $text): array
    {
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.huggingface.key'),
        ])->timeout(10)->post(config('services.huggingface.url'), [
            'inputs' => $text
        ]);

        if(!$resp->ok()){
            throw new \RuntimeException('HF API error');
        }

        $arr = $resp->json();
        $best = collect($arr[0] ?? [])->sortByDesc('score')->first();
        $label = strtolower($best['label'] ?? 'neutral');
        if(!in_array($label, ['positive','negative','neutral'])) $label = 'neutral';

        return ['category'=>$label,'score'=>$best['score'] ?? null];
    }
}
