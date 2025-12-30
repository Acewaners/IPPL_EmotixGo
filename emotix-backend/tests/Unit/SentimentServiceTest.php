<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SentimentService;
use Illuminate\Support\Facades\Http;

class SentimentServiceTest extends TestCase
{
    public function test_analyze_downgrades_to_one_star_if_fatal_word_detected()
    {
        Http::fake([
            'router.huggingface.co/*' => Http::response([
                [  
                    [ 
                        'label' => 'positive',
                        'score' => 0.99
                    ]
                ]
            ], 200)
        ]);

        $service = new SentimentService();
        $text = "Barang ini penipu banget, jangan beli!"; 

        $result = $service->analyze($text);

        $this->assertEquals(1, $result['stars']);
        $this->assertEquals('negative', $result['label']);
    }

    public function test_analyze_uses_fallback_when_api_fails()
    {
        Http::fake([
            'router.huggingface.co/*' => Http::response(null, 500)
        ]);

        $service = new SentimentService();
        $result = $service->analyze("Produk biasa saja");

        $this->assertEquals('fallback', $result['raw']);
        $this->assertEquals(3, $result['stars']);
    }

    public function test_analyze_boosts_score_if_booster_word_detected()
    {
        Http::fake([
            'router.huggingface.co/*' => Http::response([
                [['label' => 'neutral', 'score' => 0.99]]
            ], 200)
        ]);

        $service = new SentimentService();
        
        $text = "Barangnya biasa aja, tapi saya puas banget sama pelayanannya."; 

        $result = $service->analyze($text);

        $this->assertEquals(4, $result['stars']);
        $this->assertEquals('positive', $result['label']);
    }
}