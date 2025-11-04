<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id','buyer_id','review_text','sentiment','analysis_status','created_at'
    ];
}
