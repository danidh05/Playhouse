<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::latest();
        
        // Only show active products by default
        if (!$request->has('show_discontinued')) {
            $query->where('active', true);
        }
        
        $products = $query->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        Product::create($request->validated());
        return redirect()->route('admin.products.index')->with('success', 'Product created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            // Check if the product has associated sale items
            if (\App\Models\SaleItem::where('product_id', $product->id)->exists()) {
                // Instead of deleting, we could mark it as inactive or disable it
                $product->update([
                    'active' => false,
                    'stock_qty' => 0,
                    'name' => "[DISCONTINUED] " . $product->name
                ]);
                
                return redirect()->route('admin.products.index')
                    ->with('warning', 'Product has been marked as discontinued because it is used in previous sales. It can no longer be purchased but sales history is preserved.');
            }
            
            // If no sale items reference this product, we can safely delete it
            $product->delete();
            return redirect()->route('admin.products.index')
                ->with('success', 'Product deleted successfully');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
}