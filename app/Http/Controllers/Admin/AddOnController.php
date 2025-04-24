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
    public function index()
    {
        $addOns = AddOn::latest()->paginate(10);
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
        // Check if the add-on is in use
        if ($addon->playSessions()->exists()) {
            return redirect()->route('admin.addons.index')
                ->with('error', 'Cannot delete add-on that is in use');
        }
        
        $addon->delete();
        return redirect()->route('admin.addons.index')->with('success', 'Add-On deleted successfully');
    }
}