<?php

namespace App\Http\Controllers;

use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    /**
     * Serve protected storage files only to authenticated tenant users.
     *
     * - Anonymous users → redirect to login
     * - Authenticated users → can only see files belonging to their tenant
     * - Cross-tenant access → 403 Forbidden
     *
     * Supports both session (web) and Bearer token (Sanctum) authentication.
     */
    public function show(Request $request, string $path): Response
    {
        // Try session auth first, then Sanctum token
        $user = auth('web')->user() ?? auth('sanctum')->user();

        if (!$user) {
            return redirect()->route('filament.tenant.auth.login');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404);
        }

        // Extract the filename from the path (e.g., "actions/ULID.jpg" → "ULID.jpg")
        $filename = basename($path);
        $directory = dirname($path); // e.g., "actions"

        // For files in the "actions" directory, verify tenant ownership
        if ($directory === 'actions') {
            $tenantId = $user->tenant_id;

            // Search for any Action belonging to this tenant that references this file
            $ownsFile = Action::where('tenant_id', $tenantId)
                ->where(function ($query) use ($filename, $path) {
                    // Check both the full path and just the filename in the JSON data
                    $query->whereJsonContains('data->observation->image', $path)
                          ->orWhereJsonContains('data->observation->image', $filename);
                })
                ->exists();

            if (!$ownsFile) {
                abort(403, 'No tienes permiso para ver este archivo.');
            }
        }

        return response()->file(
            $disk->path($path),
            ['Content-Type' => $disk->mimeType($path)]
        );
    }
}
