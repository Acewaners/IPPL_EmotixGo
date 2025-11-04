<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sentiment extends Model
{
    protected $primaryKey = 'sentiment_id';
    public $timestamps = false;

    protected $fillable = ['review_id','category','model_version','analyzed_at'];
}
