<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;

class AdTrackingController extends Controller
{
    public function click(Advertisement $ad): JsonResponse
    {
        $ad->increment('click_count');

        return response()->json(['ok' => true]);
    }

    public function impression(Advertisement $ad): JsonResponse
    {
        $ad->increment('view_count');

        return response()->json(['ok' => true]);
    }
}
