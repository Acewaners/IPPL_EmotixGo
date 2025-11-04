<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $primaryKey = 'detail_id';
    public $timestamps = false;

    protected $fillable = ['transaction_id','product_id','quantity','subtotal','created_at'];

    public function product(){
        return $this->belongsTo(Product::class,'product_id','product_id');
    }
}
