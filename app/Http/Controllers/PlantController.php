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
        $plant = Plant::with(['seedType', 'indoor', 'actions'])->find($id);
        
        if (!$plant) {
            return response()->json([
                'error' => 'Plant not found'
            ], 404);
        }

        if ($plant->indoor->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'Forbidden - Plant belongs to another tenant'
            ], 403);
        }

        return response()->json($plant);
    }
}