<?php

namespace App\Http\Controllers;

use App\Models\AdminGuideProgress;
use App\Support\AdminGuides\AdminGuideRegistry;
use App\Support\AdminPrivileges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminGuideProgressController extends Controller
{
    public function store(Request $request, AdminGuideRegistry $registry): JsonResponse
    {
        $user = $request->user();

        abort_unless(AdminPrivileges::canAccessAdminPanel($user), 403);

        $data = $request->validate([
            'guide_key' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'completed', 'dismissed'])],
            'last_step_index' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
        ]);

        abort_unless($registry->findVisibleByKey($data['guide_key'], $user) !== null, 403);

        $progress = AdminGuideProgress::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'guide_key' => $data['guide_key'],
        ]);

        $progress->status = $data['status'];
        $progress->last_step_index = (int) ($data['last_step_index'] ?? $progress->last_step_index ?? 0);
        $progress->meta = array_replace($progress->meta ?? [], $data['meta'] ?? []);

        if ($data['status'] === 'completed') {
            $progress->completed_at = now();
            $progress->dismissed_at = null;
        } elseif ($data['status'] === 'dismissed') {
            $progress->dismissed_at = now();
        } else {
            $progress->dismissed_at = null;

            if ($data['status'] !== 'completed') {
                $progress->completed_at = null;
            }
        }

        $progress->save();

        return response()->json([
            'ok' => true,
            'progress' => [
                'guide_key' => $progress->guide_key,
                'status' => $progress->status,
                'last_step_index' => $progress->last_step_index,
                'completed_at' => $progress->completed_at?->toIso8601String(),
                'dismissed_at' => $progress->dismissed_at?->toIso8601String(),
                'meta' => $progress->meta ?? [],
            ],
        ]);
    }
}
