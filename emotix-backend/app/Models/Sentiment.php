<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sentiment extends Model
{
    protected $primaryKey = 'sentiment_id';
    public $timestamps = false;

    protected $fillable = [
        'review_id',
        'category',
        'model_version',
        'analyzed_at',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'review_id');
    }
}
