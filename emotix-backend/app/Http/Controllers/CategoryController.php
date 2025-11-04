<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    // GET /api/categories
    public function index()
    {
        // Cukup id + name supaya ringan di frontend
        $cats = Category::select('id','name')->orderBy('name')->get();
        return response()->json(['data' => $cats]);
    }
}
