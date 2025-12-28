<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentService
{
    /**
     * Menganalisa sentimen: Prioritas AI -> Fallback ke Manual jika AI Gagal
     */
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

        // 2. JIKA AI GAGAL, PAKAI BACKUP (Supaya tidak bintang 3 terus)
        Log::warning("⚠️ AI Down/Error, menggunakan Fallback Manual untuk: " . $text);
        return $this->localFallbackAnalysis($text);
    }

    private function callHuggingFace(string $text): ?array
    {
        $apiKey = config('services.huggingface.api_key');
        Log::info("Menggunakan Token: " . substr($apiKey, 0, 5) . "...");
        $model  = config('services.huggingface.model');

        // PERBAIKAN URL DISINI (Menambahkan /hf-inference/)
        // Ini adalah format URL baru untuk Hugging Face Router
        $url = "https://router.huggingface.co/hf-inference/models/{$model}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->retry(2, 1000) // Coba 2x jika timeout
            ->timeout(10)
            ->post($url, ['inputs' => $text]);

            // Jika status 200 OK
            if ($response->successful()) {
                $data = $response->json();
                
                // Ratakan array (Kadang [[...]] kadang [...])
                $preds = (isset($data[0]) && is_array($data[0])) ? $data[0] : $data;
                
                if (isset($preds[0]['label'])) {
                    // Urutkan score tertinggi
                    usort($preds, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
                    return $this->convertLabelToStars($preds[0]['label'], $preds[0]['score']);
                }
            } 
            
            // Debugging: Catat error jika gagal (misal 404/500)
            Log::error("HF API Fail Status: " . $response->status() . " Body: " . $response->body());

        } catch (\Throwable $e) {
            Log::error("HF Connection Error: " . $e->getMessage());
        }

        return null;
    }

    private function convertLabelToStars($label, $score)
    {
        $label = strtolower($label);
        $score = (float) $score;
        
        $stars = 3; 
        $sentiment = 'neutral';

        // --- TUNING V2 (Berdasarkan Log Data Azriel) ---

        // KASUS POSITIF
        if (in_array($label, ['positive', 'label_2', 'pos'])) {
            $sentiment = 'positive';
            
            // Log Anda: "Sempurna" = 0.994 -> Lolos Bintang 5
            // Log Anda: "Kurang kenyang" = 0.985 -> Gagal, turun ke Bintang 4
            if ($score >= 0.99) { 
                $stars = 5;
            } 
            // Rentang Bintang 4 diperlebar (0.70 - 0.989)
            elseif ($score >= 0.70) {
                $stars = 4;
            }
            // Score rendah/ragu-ragu (Mixed feelings)
            else {
                $stars = 3;
            }
        } 
        
        // KASUS NEGATIVE
        elseif (in_array($label, ['negative', 'label_0', 'neg'])) {
            $sentiment = 'negative';
            
            // Log Anda: "Hate Speech" = 0.999
            // Log Anda: "Komplain Sopan" = 0.996
            
            // STRATEGI BARU: Ambang batas "Kebencian Murni" kita naikkan sangat tinggi
            if ($score >= 0.998) {
                $stars = 1;
            } 
            // Jika score negatif tinggi (0.90 - 0.997) TAPI tidak "Hate Speech"
            // Ini untuk kasus "Mohon maaf... kecewa"
            elseif ($score >= 0.90) {
                $stars = 2; 
            }
            // Negatif ringan (0.60 - 0.89)
            elseif ($score >= 0.60) {
                $stars = 2;
            } 
            // Negatif ragu-ragu (< 0.60)
            else {
                $stars = 3;
                $sentiment = 'neutral';
            }
        } 
        
        // KASUS NEUTRAL
        else {
            $stars = 3;
            $sentiment = 'neutral';
        }

        Log::info("Tuning V2 Result -> Label: $label | Score: $score | Final Stars: $stars");

        return [
            'stars' => $stars,
            'label' => $sentiment,
            'score' => $score,
            'raw'   => $label
        ];
    }

    // --- FITUR CADANGAN (Hanya jalan jika AI mati) ---
}