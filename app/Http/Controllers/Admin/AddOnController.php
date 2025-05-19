<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddOnRequest;
use App\Models\AddOn;
use Illuminate\Http\Request;

class AddOnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AddOn::latest();
        
        // Only show active add-ons by default
        if (!$request->has('show_discontinued')) {
            $query->where('active', true);
        }
        
        $addOns = $query->paginate(10);
        return view('admin.addons.index', compact('addOns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.addons.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddOnRequest $request)
    {
        AddOn::create($request->validated());
        return redirect()->route('admin.addons.index')->with('success', 'Add-On created successfully');
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
    public function edit(AddOn $addon)
    {
        return view('admin.addons.edit', compact('addon'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AddOnRequest $request, AddOn $addon)
    {
        $addon->update($request->validated());
        return redirect()->route('admin.addons.index')->with('success', 'Add-On updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AddOn $addon)
    {
        try {
            // Check if the add-on is used in sale items
            if (\App\Models\SaleItem::where('add_on_id', $addon->id)->exists()) {
                // Instead of deleting, mark it as inactive
                $addon->update([
                    'active' => false,
                    'name' => "[DISCONTINUED] " . $addon->name
                ]);
                
                return redirect()->route('admin.addons.index')
                    ->with('warning', 'Add-on has been marked as discontinued because it is used in previous sales. It can no longer be purchased but sales history is preserved.');
            }
            
            // Check if the add-on is in use in play sessions
            if ($addon->playSessions()->exists()) {
                // Instead of deleting, mark it as inactive
                $addon->update([
                    'active' => false,
                    'name' => "[DISCONTINUED] " . $addon->name
                ]);
                
                return redirect()->route('admin.addons.index')
                    ->with('warning', 'Add-on has been marked as discontinued because it is used in play sessions. It can no longer be purchased but records are preserved.');
            }
            
            // If not used anywhere, we can safely delete it
            $addon->delete();
            return redirect()->route('admin.addons.index')
                ->with('success', 'Add-on deleted successfully');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.addons.index')
                ->with('error', 'Failed to delete add-on: ' . $e->getMessage());
        }
    }
}