<?php

namespace App\Http\Controllers;

use App\Support\AiVisibility\AiVisibilityContent;
use Illuminate\Support\Facades\Cache;

class AiVisibilityController extends Controller
{
    public function llms(AiVisibilityContent $content)
    {
        $body = Cache::remember('ai_visibility_llms_txt', now()->addMinutes(10), fn (): string => $content->llmsTxt());

        return response($body, 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=600');
    }
}
