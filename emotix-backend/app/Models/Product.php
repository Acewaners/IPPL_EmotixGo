<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $primaryKey = 'product_id';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $fillable = ['seller_id','category_id', 'product_name','description','price','stock','sold', 'image'];

    public function getRouteKeyName()
    {
        return 'product_id'; 
    }

    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    public function seller(){
        return $this->belongsTo(User::class,'seller_id','user_id');
    }

    public function reviews()
{
    return $this->hasMany(Review::class, 'product_id', 'product_id');
}
}

