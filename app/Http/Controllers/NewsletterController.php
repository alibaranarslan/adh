<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name'  => ['nullable', 'string', 'max:255'],
        ]);

        $existing = NewsletterSubscription::where('email', $validated['email'])->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu e-posta adresi zaten kayıtlı.',
                ], 422);
            }

            $existing->update([
                'is_active' => true,
                'confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aboneliğiniz yeniden aktif edildi.',
            ]);
        }

        NewsletterSubscription::create([
            ...$validated,
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bülten aboneliğiniz başarıyla kaydedildi.',
        ]);
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscription = NewsletterSubscription::where('unsubscribe_token', $token)->first();

        if ($subscription) {
            $subscription->update(['is_active' => false]);
        }

        return redirect('/')->with('message', 'Bülten aboneliğiniz iptal edildi.');
    }
}
