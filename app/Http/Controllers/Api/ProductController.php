<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with([
            'category',
            'images',
            'variants.stock',
        ])
            ->where('is_active', true)
            ->paginate(10);

        return ProductListResource::collection($products);
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['category', 'images', 'variants.stock'])
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request)
    {
        DB::transaction(function () use ($request, &$product) {

            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'is_active' => true,
            ]);

            // Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $image) {
                    $path = $image->store('products', 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => 'storage/' . $path,
                        'is_primary' => $i === 0,
                    ]);
                }
            }

            // Variants + Stock
            foreach ($request->variants as $variant) {
                $v = ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $variant['size'],
                    'color' => $variant['color'],
                    'price' => $variant['price'],
                ]);

                Stock::create([
                    'product_variant_id' => $v->id,
                    'quantity' => $variant['stock'],
                ]);
            }
        });

        return new ProductResource(
            $product->load(['category', 'images', 'variants.stock'])
        );
    }
}
