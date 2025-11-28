<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SentimentService
{
    public function analyze(?string $text): ?array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null; // ga ada teks, ga usah call API
        }

        $apiKey = config('services.huggingface.api_key');
        $model  = config('services.huggingface.model');

        if (!$apiKey || !$model) {
            return null; // kalo lupa isi .env, jangan bikin error fatal
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(10)->post(
                "https://api-inference.huggingface.co/models/{$model}",
                ['inputs' => $text]
            );

            if (!$response->ok()) {
                return null;
            }

            $data = $response->json();

            // nlptown model biasanya balikin: [[ {label: "1 star", score: ...}, ... ]]
            $preds = $data[0] ?? [];

            if (!is_array($preds) || empty($preds)) {
                return null;
            }

            // ambil yang score paling tinggi
            usort($preds, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
            $top = $preds[0];

            $labelRaw = $top['label'] ?? null; // contoh: "4 stars"
            $score    = $top['score'] ?? null;

            if (!$labelRaw) {
                return null;
            }

            // ambil angka bintangnya
            if (preg_match('/(\d)/', $labelRaw, $m)) {
                $stars = (int) $m[1];
            } else {
                $stars = 3; // fallback
            }

            // mapping ke positive / neutral / negative
            if ($stars >= 4) {
                $sentiment = 'positive';
            } elseif ($stars === 3) {
                $sentiment = 'neutral';
            } else {
                $sentiment = 'negative';
            }

            return [
                'stars'    => $stars,
                'label'    => $sentiment,
                'score'    => $score,
                'raw'      => $labelRaw,
            ];
        } catch (\Throwable $e) {
            // kamu bisa log kalau perlu:
            // \Log::warning('Sentiment error: '.$e->getMessage());
            return null;
        }
    }
}
