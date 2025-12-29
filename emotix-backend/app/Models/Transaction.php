<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;
    protected $fillable = ['buyer_id','seller_id','transaction_date','total_price','status','tracking_number','created_at'];

    // Agar route model binding {transaction} berjalan lancar
    public function getRouteKeyName()
    {
        return 'transaction_id'; 
    }

    // Relasi ke detail transaksi (Barang-barang yang dibeli)
    public function details(){
        return $this->hasMany(TransactionDetail::class, 'transaction_id', 'transaction_id');
    }

    // ✅ TAMBAHKAN INI: Relasi ke User Pembeli
    public function buyer()
    {
        // Parameter: (Model Tujuan, Foreign Key di tabel ini, Primary Key di tabel User)
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    // ✅ TAMBAHKAN INI JUGA: Relasi ke User Penjual
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }
}
