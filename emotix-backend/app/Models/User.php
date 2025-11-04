<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'user_id';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['name','email','password','role','is_admin'];
    protected $hidden = ['password','remember_token'];

    public function products(){
        return $this->hasMany(Product::class,'seller_id','user_id');
    }
    public function boughtTransactions(){
        return $this->hasMany(Transaction::class,'buyer_id','user_id');
    }
    public function soldTransactions(){
        return $this->hasMany(Transaction::class,'seller_id','user_id');
    }
}
