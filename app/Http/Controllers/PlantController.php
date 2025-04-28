<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->tenant;
        $plants = Plant::whereHas('indoor', function($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->with(['seedType', 'indoor'])->get();

        return response()->json($plants);
    }

    public function show(Request $request, $id)
    {
        $tenant = $request->tenant;
        $plant = Plant::whereHas('indoor', function($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->with(['seedType', 'indoor', 'actions'])->findOrFail($id);

        return response()->json($plant);
    }
}