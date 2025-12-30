<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;
    protected $fillable = ['buyer_id','seller_id','transaction_date','total_price','status','tracking_number','created_at'];

    public function getRouteKeyName()
    {
        return 'transaction_id';
    }

    public function details(){
        return $this->hasMany(TransactionDetail::class, 'transaction_id', 'transaction_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }
}
