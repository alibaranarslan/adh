<?php

namespace App\Http\Controllers;

use App\Models\HeaderTheme;
use App\Models\LayoutRevision;
use App\Services\HomeModuleDataService;
use App\Services\LayoutConfigService;
use Illuminate\Support\Facades\URL;

class HomePageController extends Controller
{
    public function __construct(
        private readonly LayoutConfigService $layoutConfigService,
        private readonly HomeModuleDataService $homeModuleDataService,
    ) {
    }

    public function index()
    {
        return $this->renderHome();
    }

    public function preview(LayoutRevision $revision)
    {
        abort_unless($revision->area === LayoutConfigService::AREA_HOME, 404);

        $this->applyPreviewLocale();

        return $this->renderHome($revision);
    }

    public function themePreview(HeaderTheme $headerTheme)
    {
        $this->applyPreviewLocale();

        return $this->renderHome();
    }

    private function renderHome(?LayoutRevision $previewRevision = null)
    {
        $layoutState = $this->layoutConfigService->resolveState($previewRevision);
        $payload = $this->homeModuleDataService->collect($layoutState);
        $sections = $this->homeModuleDataService->buildSections($layoutState, $payload);
        $showFallbackNotice = $this->homeModuleDataService->shouldShowFallbackNotice($sections);
        $heroMain = $payload['heroMain'] ?? null;

        return view('home.layout-studio', array_merge($payload, [
            'layoutState' => $layoutState,
            'layoutSections' => $sections,
            'layoutPreviewRevision' => $previewRevision,
            'showFallbackNotice' => $showFallbackNotice,
            'metaTitle' => null,
            'metaDescription' => __('Adiyaman ve cevresinden en guncel haberler.'),
            'ogImage' => $heroMain?->featured_image,
        ]));
    }

    private function applyPreviewLocale(): void
    {
        $locale = request()->string('locale')->toString() ?: app()->getLocale();

        if (in_array($locale, ['tr', 'en', 'ku'], true)) {
            app()->setLocale($locale);
            URL::defaults(['locale' => $locale]);
        }
    }
}
