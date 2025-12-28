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

        // 1. COBA PANGGIL AI ASLI (Hugging Face)
        $aiResult = $this->callHuggingFace($text);

        if ($aiResult) {
            Log::info("✅ AI Success: " . $text);
            return $aiResult;
        }

        // 2. JIKA AI GAGAL, PAKAI BACKUP
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
                    
                    // Kita kirim teks aslinya juga untuk analisa keyword
                    return $this->convertLabelToStars($preds[0]['label'], $preds[0]['score'], $text);
                }
            } 
            
            Log::error("HF API Fail Status: " . $response->status() . " Body: " . $response->body());

        } catch (\Throwable $e) {
            Log::error("HF Connection Error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Tuning V4: Hybrid AI Score + Keyword Constraints
     */
    private function convertLabelToStars($label, $score, $text)
    {
        $label = strtolower($label);
        $score = (float) $score;
        $textLower = strtolower($text);
        
        $stars = 3; 
        $sentiment = 'neutral';

        // --- 1. LOGIKA AI DASAR (Tuning Threshold Diperketat) ---

        // KASUS POSITIF
        if (in_array($label, ['positive', 'label_2', 'pos'])) {
            $sentiment = 'positive';
            
            // Log "Gila sih keren banget" = 0.9988 -> OK
            // Log "Lumayan bagus" = 0.9843 -> Ini harusnya TIDAK bintang 5
            // Jadi, batas Bintang 5 kita naikkan ke 0.990
            if ($score >= 0.990) { 
                $stars = 5;
            } 
            // Rentang Bintang 4: 0.80 - 0.989
            elseif ($score >= 0.80) {
                $stars = 4;
            }
            // Positif lemah (< 0.80) -> Bintang 3
            else {
                $stars = 3;
            }
        } 
        
        // KASUS NEGATIF
        elseif (in_array($label, ['negative', 'label_0', 'neg'])) {
            $sentiment = 'negative';
            
            // Log "Penipu" = 0.9993 -> OK Bintang 1
            // Log "Lambat banget" = 0.9928 -> Ini harusnya Bintang 2, bukan 1
            // Jadi, batas Bintang 1 kita naikkan ekstrem ke 0.995 (Hanya Hate Speech/Scam)
            if ($score >= 0.995) {
                $stars = 1;
            } 
            // Kecewa umum
            else {
                $stars = 2; 
            }
        } 
        
        else {
            $stars = 3;
            $sentiment = 'neutral';
        }

        // --- 2. LOGIKA KEYWORD (RULE-BASED CORRECTION) ---
        // Ini untuk mengatasi "kebodohan" AI pada konteks tertentu

        $originalStars = $stars; // Simpan untuk log perbandingan

        // ATURAN A: Penurun Ekspektasi (Tapi/Cuma/Agak/Lumayan)
        // Jika AI bilang bintang 5, tapi ada kata "tapi", turunkan ke 4.
        if ($stars == 5) {
            if ($this->containsAny($textLower, ['tapi', 'cuma', 'hanya', 'agak', 'lumayan', 'sayang', 'pengiriman lama', 'kurang'])) {
                $stars = 4;
                Log::info("Rule Applied: Downgrade 5->4 karena ada kata sanggahan.");
            }
        }

        // ATURAN B: Penetral (Belum dicoba/Baru sampai)
        // Seringkali AI mendeteksi ini sebagai Negatif. Kita paksa jadi Bintang 3.
        if ($this->containsAny($textLower, ['belum dicoba', 'baru sampai', 'belum di coba', 'baru nyampe', 'lihat nanti'])) {
            $stars = 3;
            $sentiment = 'neutral';
            Log::info("Rule Applied: Force Neutral karena barang belum dicoba.");
        }

        // ATURAN C: Biasa Aja
        // Jika AI bilang Positif (Bintang 4/5) tapi user bilang "biasa aja", turunkan ke 3.
        if ($stars > 3 && $this->containsAny($textLower, ['biasa aja', 'standar', 'sesuai harga'])) {
            $stars = 3;
            Log::info("Rule Applied: Downgrade ke 3 karena 'Biasa aja'.");
        }

        // ATURAN D: Hate Speech/Scam Keywords (Force 1 Star)
        if ($stars > 1 && $this->containsAny($textLower, ['penipu', 'rusak parah', 'hancur', 'pecah', 'tidak berfungsi', 'nyesel', 'balikin duit'])) {
            $stars = 1;
            $sentiment = 'negative';
            Log::info("Rule Applied: Force 1 Star karena kata-kata fatal.");
        }

        Log::info("Tuning V4 Result -> Label: $label | Score: $score | Base Stars: $originalStars | Final Stars: $stars");

        return [
            'stars' => $stars,
            'label' => $sentiment,
            'score' => $score,
            'raw'   => $label
        ];
    }

    /**
     * Helper untuk cek array kata kunci
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    // --- FITUR CADANGAN (Hanya jalan jika AI mati) ---
    private function localFallbackAnalysis(string $text): array
    {
        // ... kode fallback lama Anda ...
        return ['stars' => 3, 'label' => 'neutral', 'score' => 0, 'raw' => 'fallback'];
    }
}