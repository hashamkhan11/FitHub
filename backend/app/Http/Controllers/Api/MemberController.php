<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberController extends Controller
{
    public function qr(Request $request): Response
    {
        return response(
            QrCode::size(300)->generate($request->user()->qr_code),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }

    public function membership(Request $request)
    {
        $membership = $request->user()
            ->memberships()
            ->with('plan')
            ->latest('end_date')
            ->first();

        return response()->json(['membership' => $membership]);
    }

    public function updateFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $request->user()->update(['fcm_token' => $validated['fcm_token']]);

        return response()->json(['message' => 'Token saved.']);
    }

    public function measurements(Request $request)
    {
        $measurements = $request->user()
            ->measurements()
            ->orderBy('recorded_at')
            ->get();

        return response()->json(['measurements' => $measurements]);
    }

    public function storeMeasurement(Request $request)
    {
        $validated = $request->validate([
            'recorded_at' => ['required', 'date'],
            'weight_kg' => ['nullable', 'numeric'],
            'body_fat_percentage' => ['nullable', 'numeric'],
            'chest_cm' => ['nullable', 'numeric'],
            'waist_cm' => ['nullable', 'numeric'],
            'hips_cm' => ['nullable', 'numeric'],
            'arms_cm' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $measurement = $request->user()->measurements()->create([
            'gym_id' => $request->user()->gym_id,
            ...$validated,
        ]);

        return response()->json(['measurement' => $measurement], 201);
    }
}
