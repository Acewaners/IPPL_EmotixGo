<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentService
{
    public function analyze(?string $text): ?array
    {
        $text = trim((string) $text);
        if ($text === '') return null;

        $aiResult = $this->callHuggingFace($text);

        if ($aiResult) {
            Log::info("✅ AI Success: " . $text);
            return $aiResult;
        }


        Log::warning("⚠️ AI Down/Error, menggunakan Fallback Manual untuk: " . $text);
        return $this->localFallbackAnalysis($text);
    }

    private function callHuggingFace(string $text): ?array
    {
        $apiKey = config('services.huggingface.api_key');
        Log::info("Menggunakan Token: " . substr($apiKey, 0, 5) . "...");
        $model  = config('services.huggingface.model');
        $url = "https://router.huggingface.co/hf-inference/models/{$model}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->retry(2, 1000)
            ->timeout(10)
            ->post($url, ['inputs' => $text]);

            if ($response->successful()) {
                $data = $response->json();
                $preds = (isset($data[0]) && is_array($data[0])) ? $data[0] : $data;

                if (isset($preds[0]['label'])) {
                    usort($preds, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

                    return $this->convertLabelToStars($preds[0]['label'], $preds[0]['score'], $text);
                }
            }

            Log::error("HF API Fail Status: " . $response->status() . " Body: " . $response->body());

        } catch (\Throwable $e) {
            Log::error("HF Connection Error: " . $e->getMessage());
        }

        return null;
    }

    private function convertLabelToStars($label, $score, $text)
    {
        $label = strtolower($label);
        $score = (float) $score;
        $textLower = strtolower($text);

        $stars = 3;
        $sentiment = 'neutral';

        // --- 1. LOGIKA AI DASAR ---

        if (in_array($label, ['positive', 'label_2', 'pos'])) {
            $sentiment = 'positive';
            if ($score >= 0.990) {
                $stars = 5;
            } elseif ($score >= 0.80) {
                $stars = 4;
            } else {
                $stars = 3;
            }
        } elseif (in_array($label, ['negative', 'label_0', 'neg'])) {
            $sentiment = 'negative';
            if ($score >= 0.995) {
                $stars = 1;
            } else {
                $stars = 2;
            }
        } else {
            $stars = 3;
            $sentiment = 'neutral';
        }

        // --- 2. LOGIKA KEYWORD ---

        $originalStars = $stars;

        $downgradeWords = [
        // 1. KATA SANGGAHAN
            // Baku
            'tapi', 'cuma', 'hanya', 'agak', 'sayang', 'kurang', 'sedikit', 'rada',
            // Gaul / Singkatan
            'doang', 'aja sih', 'cuman', 'kekurangannya', 'rada', 'b aja',
            'tpi', 'cm', 'kirain', 'sayangnya', 'pengiriman lama', 'kureng'
        ];

        // 2. KATA NETRAL / BELUM DICOBA
        $pendingWords = [
            // Baku
            'belum dicoba', 'baru sampai', 'belum di coba', 'lihat nanti', 'semoga awet',
            // Gaul
            'baru nyampe', 'baru dtg', 'blm dicoba', 'blm di tes', 'moga awet'
        ];

        // 3. KATA BIASA AJA (Standard)
        $neutralWords = [
            // Baku
            'biasa saja', 'standar', 'sesuai harga', 'cukup', 'lumayan',
            // Gaul
            'b aja', 'biasa aja', 'so so', 'not bad', 'oke lah',
            'bolehlah', 'sesuai ekspektasi', 'oke aja', 'mayan', 'standar aja'
        ];

        // 4. KATA FATAL (Hate Speech/Scam)
        $fatalWords = [
            // Baku
            'penipu', 'rusak', 'hancur', 'pecah', 'tidak berfungsi', 'palsu',
            'kecewa berat', 'buang uang', 'tidak sesuai', 'barang bekas',
            // Gaul
            'zonk', 'nyesel', 'kapok', 'parah', 'ancur', 'balikin duit',
            'rugi', 'scam', 'abal-abal', 'sampah', 'gak guna', 'jelek banget', 'ga guna'
        ];

        // 5. KATA BOOSTER (Retensi/Puas)
        $boosterWords = [
            // Baku
            'pesan lagi', 'beli lagi', 'order lagi', 'sangat puas', 'terbaik', 'memuaskan',
            'rekomendasi', 'recommended', 'langganan', 'puas banget',
            // Gaul
            'bakal beli lagi', 'mantul', 'mantap', 'gokil', 'keren abis', 'the best',
            'juara', 'top markotop', 'nagih', 'gapernah nyesel', 'worth it',
            'pasti beli lagi', 'next order', 'repeat order', 'ga nyesel'
        ];

        if ($stars == 5) {
            if ($this->containsAny($textLower, $downgradeWords)) {
                $stars = 4;
                Log::info("Rule Applied: Downgrade 5->4 karena ada kata sanggahan.");
            }
        }

        if ($this->containsAny($textLower, $pendingWords)) {
            $stars = 3;
            $sentiment = 'neutral';
            Log::info("Rule Applied: Force Neutral karena barang belum dicoba.");
        }

        if ($stars > 3 && $this->containsAny($textLower, $neutralWords)) {
            $stars = 3;
            Log::info("Rule Applied: Downgrade ke 3 karena 'Biasa aja'.");
        }

        if ($stars > 1 && $this->containsAny($textLower, $fatalWords)) {
            $stars = 1;
            $sentiment = 'negative';
            Log::info("Rule Applied: Force 1 Star karena indikator fatal.");
        }

        if ($this->containsAny($textLower, $boosterWords)) {
            if ($stars < 4 && $stars > 1) {
                $stars = 4;
                $sentiment = 'positive';
                Log::info("Rule Applied: Booster ke 4 karena indikasi retensi/kepuasan tinggi.");
            }
                elseif ($stars == 1) {
                 // Opsional: Bisa dibiarkan 1, atau dinaikkan jika Anda mau lebih forgiving
                 // $stars = 3;
            }
        }

        Log::info("Tuning V4 Result -> Label: $label | Score: $score | Base Stars: $originalStars | Final Stars: $stars");

        return [
            'stars' => $stars,
            'label' => $sentiment,
            'score' => $score,
            'raw'   => $label
        ];
    }

    // --- FITUR CADANGAN (Hanya jalan jika AI mati) ---
    private function localFallbackAnalysis(string $text): array
    {
        return ['stars' => 3, 'label' => 'neutral', 'score' => 0, 'raw' => 'fallback'];
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

}
