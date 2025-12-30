<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $cats = Category::select('id','name')->orderBy('name')->get();
        return response()->json(['data' => $cats]);
    }
}
