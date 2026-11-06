<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('category:id,name')
            ->latest()
            ->limit(4)
            ->get(['id', 'name', 'price', 'image', 'description', 'category_id'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'description' => $product->description_text,
                'image' => $product->image
                    ? asset('storage/'.$product->image)
                    : null,
                'category' => $product->category?->name,
            ]);

        return Inertia::render('home', [
            'products' => $products,
            'default_no_image' => asset('images/no_images.jpeg'),
        ]);
    }
}
