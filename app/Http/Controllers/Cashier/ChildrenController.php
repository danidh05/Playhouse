<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChildRequest;
use App\Models\Child;
use Illuminate\Http\Request;

class ChildrenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Child::query();

        // Search by name or guardian
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('guardian_name', 'like', "%{$search}%")
                  ->orWhere('guardian_contact', 'like', "%{$search}%")
                  ->orWhere('guardian_phone', 'like', "%{$search}%");
            });
        }

        // Filter by age range
        if ($request->has('age') && !empty($request->age)) {
            $ageRange = $request->age;
            
            switch ($ageRange) {
                case '0-3':
                    $query->whereBetween('age', [0, 3]);
                    break;
                case '4-6':
                    $query->whereBetween('age', [4, 6]);
                    break;
                case '7-10':
                    $query->whereBetween('age', [7, 10]);
                    break;
                case '11+':
                    $query->where('age', '>=', 11);
                    break;
            }
        }

        $children = $query->latest()->paginate(10)->withQueryString();
        return view('cashier.children.index', compact('children'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $redirectToSale = $request->has('redirect_to_sale') && $request->redirect_to_sale;
        return view('cashier.children.create', compact('redirectToSale'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ChildRequest $request)
    {
        $child = Child::create($request->validated());
        
        // If redirect_to_sale is set, redirect to sales.create with child_id
        if ($request->has('redirect_to_sale') && $request->redirect_to_sale) {
            return redirect()->route('cashier.sales.create', ['child_id' => $child->id])
                ->with('success', 'Child created successfully. You can now create a sale for this child.');
        }
        
        return redirect()->route('cashier.children.index')->with('success', 'Child created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Child $child)
    {
        return view('cashier.children.edit', compact('child'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ChildRequest $request, Child $child)
    {
        $child->update($request->validated());
        return redirect()->route('cashier.children.index')->with('success', 'Child updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Child $child)
    {
        $child->delete();
        return redirect()->route('cashier.children.index')->with('success', 'Child deleted successfully');
    }
} 