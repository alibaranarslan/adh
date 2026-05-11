<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class HeaderTheme extends Model
{
    use HasTranslations;

    public const SITE = 'adh';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_RANGE = 'range';
    public const TYPE_NTH_WEEKDAY = 'nth_weekday';
    public const TYPE_MANUAL_ONLY = 'manual_only';
    public const MODE_AUTOMATIC = 'automatic';
    public const MODE_MANUAL_ON = 'manual_on';
    public const MODE_DISABLED = 'disabled';
    public const ATATURK_ALLOWED_SLUGS = ['19-mayis', '30-agustos', '10-kasim'];

    public array $translatable = ['name', 'banner_message'];

    protected $fillable = [
        'site',
        'slug',
        'name',
        'theme_type',
        'month',
        'day',
        'weekday',
        'nth_week',
        'starts_at',
        'ends_at',
        'priority',
        'is_enabled',
        'mode',
        'banner_message',
        'style_variant',
        'illustration_mode',
        'illustration_asset',
        'show_flag',
        'show_ataturk',
        'decor_intensity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'day' => 'integer',
            'weekday' => 'integer',
            'nth_week' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'priority' => 'integer',
            'is_enabled' => 'boolean',
            'show_flag' => 'boolean',
            'show_ataturk' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $theme): void {
            $theme->site = self::SITE;
            $theme->show_ataturk = self::allowsAtaturkForSlug($theme->slug) ? (bool) $theme->show_ataturk : false;
        });

        static::saved(fn () => self::bumpVersion());
        static::deleted(fn () => self::bumpVersion());
    }

    public static function allowsAtaturkForSlug(?string $slug): bool
    {
        return in_array((string) $slug, self::ATATURK_ALLOWED_SLUGS, true);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_FIXED => 'Sabit Tarih',
            self::TYPE_RANGE => 'Tarih Aralığı',
            self::TYPE_NTH_WEEKDAY => 'Ayın Belirli Haftası',
            self::TYPE_MANUAL_ONLY => 'Sadece Manuel',
        ];
    }

    public static function modeOptions(): array
    {
        return [
            self::MODE_AUTOMATIC => 'Otomatik',
            self::MODE_MANUAL_ON => 'Manuel Açık',
            self::MODE_DISABLED => 'Kapalı',
        ];
    }

    public static function styleVariantOptions(): array
    {
        return [
            'national' => 'Milli Gün',
            'commemoration' => 'Anma',
            'bayram' => 'Bayram',
        ];
    }

    public static function illustrationModeOptions(): array
    {
        return [
            'inline_svg' => 'Hazır SVG',
            'custom_asset' => 'Özel Görsel',
            'none' => 'Görsel Yok',
        ];
    }

    public static function decorIntensityOptions(): array
    {
        return [
            'soft' => 'Yumuşak',
            'medium' => 'Dengeli',
            'strong' => 'Belirgin',
        ];
    }

    public function scopeForSite($query)
    {
        return $query->where('site', self::SITE);
    }

    public function matchesDate(CarbonInterface $date): bool
    {
        return match ($this->theme_type) {
            self::TYPE_FIXED => (int) $date->month === (int) $this->month
                && (int) $date->day === (int) $this->day,
            self::TYPE_RANGE => $this->matchesDateRange($date),
            self::TYPE_NTH_WEEKDAY => $this->matchesNthWeekday($date),
            default => false,
        };
    }

    public function translatedBannerMessage(?string $locale = null): string
    {
        return $this->resolveTranslation('banner_message', $locale);
    }

    public function translatedName(?string $locale = null): string
    {
        return $this->resolveTranslation('name', $locale);
    }

    public function previewDate(?CarbonInterface $from = null): CarbonImmutable
    {
        $from = CarbonImmutable::instance(($from ?? now())->copy()->startOfDay());

        return match ($this->theme_type) {
            self::TYPE_FIXED => $this->nextFixedDate($from),
            self::TYPE_NTH_WEEKDAY => $this->nextNthWeekdayDate($from),
            self::TYPE_RANGE => $this->starts_at
                ? CarbonImmutable::instance($this->starts_at->copy()->startOfDay())
                : $from,
            default => $from,
        };
    }

    public function scheduleLabel(): string
    {
        return match ($this->theme_type) {
            self::TYPE_FIXED => sprintf('%s %s', $this->day, $this->monthName()),
            self::TYPE_RANGE => $this->starts_at && $this->ends_at
                ? sprintf(
                    '%s - %s',
                    $this->starts_at->locale('tr')->translatedFormat('d M Y'),
                    $this->ends_at->locale('tr')->translatedFormat('d M Y'),
                )
                : 'Tarih aralığı eksik',
            self::TYPE_NTH_WEEKDAY => sprintf(
                '%s %s %s',
                $this->nthWeekLabel(),
                $this->weekdayLabel(),
                $this->monthName(),
            ),
            self::TYPE_MANUAL_ONLY => 'Sadece manuel kullanım',
            default => 'Tanımsız',
        };
    }

    private function matchesDateRange(CarbonInterface $date): bool
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return false;
        }

        $target = CarbonImmutable::instance($date->copy()->startOfDay());

        return $target->betweenIncluded(
            CarbonImmutable::instance($this->starts_at->copy()->startOfDay()),
            CarbonImmutable::instance($this->ends_at->copy()->startOfDay()),
        );
    }

    private function matchesNthWeekday(CarbonInterface $date): bool
    {
        if ((int) $date->month !== (int) $this->month) {
            return false;
        }

        $expected = $this->nthWeekdayDayOfMonth((int) $date->year);

        return $expected !== null && (int) $date->day === $expected;
    }

    private function nextFixedDate(CarbonImmutable $from): CarbonImmutable
    {
        $candidate = $from->setMonth((int) $this->month)->setDay((int) $this->day);

        return $candidate->lt($from) ? $candidate->addYear() : $candidate;
    }

    private function nextNthWeekdayDate(CarbonImmutable $from): CarbonImmutable
    {
        $year = (int) $from->year;
        $candidate = $this->nthWeekdayDateForYear($year);

        if (! $candidate || $candidate->lt($from)) {
            $candidate = $this->nthWeekdayDateForYear($year + 1) ?? $from;
        }

        return $candidate;
    }

    private function nthWeekdayDateForYear(int $year): ?CarbonImmutable
    {
        $day = $this->nthWeekdayDayOfMonth($year);

        return $day === null
            ? null
            : CarbonImmutable::create($year, (int) $this->month, $day, 0, 0, 0, config('app.timezone'));
    }

    private function nthWeekdayDayOfMonth(int $year): ?int
    {
        if (! $this->month || $this->weekday === null || ! $this->nth_week) {
            return null;
        }

        $month = (int) $this->month;
        $weekday = (int) $this->weekday;
        $nth = (int) $this->nth_week;

        if ($nth === -1) {
            $lastDay = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'))->endOfMonth();

            while ((int) $lastDay->dayOfWeek !== $weekday) {
                $lastDay = $lastDay->subDay();
            }

            return (int) $lastDay->day;
        }

        $firstDay = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));
        $offset = ($weekday - (int) $firstDay->dayOfWeek + 7) % 7;
        $day = 1 + $offset + (($nth - 1) * 7);

        return checkdate($month, $day, $year) ? $day : null;
    }

    private function monthName(): string
    {
        return match ((int) $this->month) {
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
            default => 'Ay belirsiz',
        };
    }

    private function weekdayLabel(): string
    {
        return match ((int) $this->weekday) {
            0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi',
            default => 'Gün belirsiz',
        };
    }

    private function nthWeekLabel(): string
    {
        return match ((int) $this->nth_week) {
            1 => 'İlk', 2 => 'İkinci', 3 => 'Üçüncü', 4 => 'Dördüncü', -1 => 'Son',
            default => 'Belirsiz',
        };
    }

    private function resolveTranslation(string $attribute, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        foreach ([$locale, 'tr', 'en', 'ku'] as $candidate) {
            $value = trim((string) $this->getTranslation($attribute, $candidate, false));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function bumpVersion(): void
    {
        Setting::set('system', 'header_theme_version', (string) now()->timestamp);
    }
}
