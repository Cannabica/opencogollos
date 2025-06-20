<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function store(Request $request)
    {
        // TODO: Implement notification storage logic
        return response()->json(['message' => 'Notification stored']);
    }
}