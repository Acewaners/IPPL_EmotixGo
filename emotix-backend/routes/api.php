<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\UserController as AdminUser;

Route::get('/health', fn() => response()->json(['ok' => true]));

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::get('/products',[ProductController::class,'index']);
Route::middleware('auth:sanctum')->group(function(){
    Route::get('/me',[AuthController::class,'me']);
    Route::post('/logout',[AuthController::class,'logout']);

    Route::post('/products',[ProductController::class,'store']);
    Route::put('/products/{product}',[ProductController::class,'update']);     // ✅ ganti PUT
    Route::patch('/products/{product}',[ProductController::class,'update']);   // ✅ optional
    Route::delete('/products/{product}',[ProductController::class,'destroy']); // ✅ DELETE
    Route::get('/products/{product}/reviews', [ReviewController::class, 'byProduct']);

    Route::post('/transactions',[TransactionController::class,'create']);
    Route::get('/buyer/orders',[TransactionController::class,'indexBuyer']);
    Route::get('/seller/orders',[TransactionController::class,'indexSeller']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions/{transaction}/status',[TransactionController::class,'updateStatus']);
    Route::get('/buyer/transactions', [TransactionController::class, 'indexBuyer']);

    Route::get('/reviews/me', [ReviewController::class, 'myReviews']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::prefix('admin')->middleware('admin')->group(function(){
        Route::get('/users',[AdminUser::class,'index']);
        Route::post('/users/{user}/role',[AdminUser::class,'updateRole']);
    });
});
