<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = ['name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
