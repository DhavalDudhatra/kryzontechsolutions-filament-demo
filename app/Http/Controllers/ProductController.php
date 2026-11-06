<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    // For User Product Page
    public function index(): Response
    {
        $products = Product::where('is_active', true)
            ->with('category:id,name')
            ->latest()
            ->paginate(24)
            ->through(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'description' => html_entity_decode(strip_tags($product->description)),
                    'image' => $product->image
                        ? asset('storage/'.$product->image)
                        : null,
                    'category' => $product->category?->name,
                ];
            });

        return Inertia::render('products/index', [
            'products' => $products,
            'default_no_image' => asset('images/no_images.jpeg'),
        ]);
    }
}
