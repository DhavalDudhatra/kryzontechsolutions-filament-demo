<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// User Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// User Products Listing page
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
