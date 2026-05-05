<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('warehouses/index', [
            'warehouses' => Warehouse::latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('warehouses/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:5', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:40'],
        ]);

        Warehouse::create($validated);

        return to_route('warehouses.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        return Inertia::render('warehouses/edit', [
            'warehouse' => $warehouse
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:5', 'unique:warehouses,code,' . $warehouse->id],
            'name' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:40'],
        ]);

        $warehouse->update($validated);

        return to_route('warehouses.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return to_route('warehouses.index');
    }
}
