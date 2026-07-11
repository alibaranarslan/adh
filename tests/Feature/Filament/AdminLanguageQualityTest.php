<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class AdminLanguageQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_representative_admin_pages_do_not_render_mojibake_sequences(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        foreach (['/admin', '/admin/iha-health', '/admin/media-library', '/admin/general-settings'] as $path) {
            $response = $this->actingAs($admin)->get($path)->assertOk();

            $this->assertDoesNotMatchRegularExpression('/(?:Ã|Ä|Å|â€|Â)/u', $response->getContent() ?: '');
        }
    }

    public function test_admin_source_files_do_not_ship_mojibake_literals(): void
    {
        $files = collect([
            ...$this->phpFiles(app_path('Filament')),
            ...$this->phpFiles(app_path('Providers/Filament')),
            ...$this->phpFiles(app_path('Services')),
            ...$this->phpFiles(app_path('Support')),
            ...$this->bladeFiles(resource_path('views/filament')),
        ])->reject(function (string $path): bool {
            return str_ends_with($path, 'SanitizeAdminHtmlEncoding.php')
                || str_ends_with($path, 'AdhControlCenterPresenter.php')
                || str_ends_with($path, 'PharmacyService.php');
        });

        foreach ($files as $path) {
            $content = file_get_contents($path) ?: '';

            $this->assertDoesNotMatchRegularExpression(
                '/(?:Ã|Ä|Å|â€|Â)/u',
                $content,
                "{$path} contains a mojibake literal.",
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function phpFiles(string $root): array
    {
        return $this->filesWithExtension($root, 'php');
    }

    /**
     * @return array<int, string>
     */
    private function bladeFiles(string $root): array
    {
        return $this->filesWithExtension($root, 'blade.php');
    }

    /**
     * @return array<int, string>
     */
    private function filesWithExtension(string $root, string $extension): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if (str_ends_with($file->getFilename(), $extension)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
