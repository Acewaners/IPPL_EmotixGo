<?php

// app/Models/Review.php
// app/Models/Review.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $primaryKey = 'review_id';

    protected $fillable = [
        'product_id',
        'buyer_id',
        'review_text',
        'rating',
        'sentiment',
        'analysis_status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }
    public function sentimentRecord()
{
    return $this->hasOne(Sentiment::class, 'review_id', 'review_id');
}

}
