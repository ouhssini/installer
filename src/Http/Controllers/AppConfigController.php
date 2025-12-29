<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use SoftCortex\Installer\Services\EnvironmentManager;
use SoftCortex\Installer\Services\InstallerService;

class AppConfigController extends Controller
{
    public function __construct(
        private InstallerService $installer,
        private EnvironmentManager $environment
    ) {}

    public function index()
    {
        // Get available locales from lang directory
        $availableLocales = $this->getAvailableLocales();

        // Get current values from .env
        $currentConfig = [
            'app_name' => $this->environment->get('APP_NAME') ?? 'Laravel',
            'app_env' => $this->environment->get('APP_ENV') ?? 'local',
            'app_debug' => $this->environment->get('APP_DEBUG') ?? 'true',
            'app_timezone' => $this->environment->get('APP_TIMEZONE') ?? 'UTC',
            'app_url' => $this->environment->get('APP_URL') ?? 'http://localhost',
            'app_locale' => $this->environment->get('APP_LOCALE') ?? 'en',
        ];

        // Get available timezones
        $timezones = $this->getTimezones();

        return view('installer::app-config', [
            'currentConfig' => $currentConfig,
            'availableLocales' => $availableLocales,
            'timezones' => $timezones,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_env' => 'required|in:local,production,staging,development',
            'app_debug' => 'required|boolean',
            'app_timezone' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_locale' => 'required|string|max:10',
        ]);

        // Update .env file
        $this->environment->setMultiple([
            'APP_NAME' => $validated['app_name'],
            'APP_ENV' => $validated['app_env'],
            'APP_DEBUG' => $validated['app_debug'] ? 'true' : 'false',
            'APP_TIMEZONE' => $validated['app_timezone'],
            'APP_URL' => $validated['app_url'],
            'APP_LOCALE' => $validated['app_locale'],
        ]);

        // Mark step as completed
        $this->installer->completeStep(2);
        $this->installer->setCurrentStep(3);

        return redirect()->route('installer.requirements');
    }

    /**
     * Get available locales from lang directory
     */
    private function getAvailableLocales(): array
    {
        $langPath = base_path('lang');
        $locales = [];

        if (! File::exists($langPath)) {
            return [
                'en' => 'English',
            ];
        }

        // Get directories (e.g., en, fr, es)
        $directories = File::directories($langPath);
        foreach ($directories as $directory) {
            $locale = basename($directory);
            $locales[$locale] = $this->getLocaleName($locale);
        }

        // Get JSON files (e.g., en.json, fr.json)
        $jsonFiles = File::glob($langPath.'/*.json');
        foreach ($jsonFiles as $file) {
            $locale = basename($file, '.json');
            if (! isset($locales[$locale])) {
                $locales[$locale] = $this->getLocaleName($locale);
            }
        }

        // If no locales found, default to English
        if (empty($locales)) {
            $locales['en'] = 'English';
        }

        return $locales;
    }

    /**
     * Get human-readable locale name
     */
    private function getLocaleName(string $locale): string
    {
        $names = [
            'en' => 'English',
            'fr' => 'Français (French)',
            'es' => 'Español (Spanish)',
            'de' => 'Deutsch (German)',
            'it' => 'Italiano (Italian)',
            'pt' => 'Português (Portuguese)',
            'ru' => 'Русский (Russian)',
            'zh' => '中文 (Chinese)',
            'ja' => '日本語 (Japanese)',
            'ko' => '한국어 (Korean)',
            'ar' => 'العربية (Arabic)',
            'hi' => 'हिन्दी (Hindi)',
            'nl' => 'Nederlands (Dutch)',
            'pl' => 'Polski (Polish)',
            'tr' => 'Türkçe (Turkish)',
            'vi' => 'Tiếng Việt (Vietnamese)',
            'th' => 'ไทย (Thai)',
            'id' => 'Bahasa Indonesia (Indonesian)',
            'ms' => 'Bahasa Melayu (Malay)',
            'sv' => 'Svenska (Swedish)',
            'da' => 'Dansk (Danish)',
            'no' => 'Norsk (Norwegian)',
            'fi' => 'Suomi (Finnish)',
            'cs' => 'Čeština (Czech)',
            'hu' => 'Magyar (Hungarian)',
            'ro' => 'Română (Romanian)',
            'uk' => 'Українська (Ukrainian)',
            'el' => 'Ελληνικά (Greek)',
            'he' => 'עברית (Hebrew)',
            'fa' => 'فارسی (Persian)',
        ];

        return $names[$locale] ?? ucfirst($locale);
    }

    /**
     * Get common timezones
     */
    private function getTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'America/New York (EST/EDT)',
            'America/Chicago' => 'America/Chicago (CST/CDT)',
            'America/Denver' => 'America/Denver (MST/MDT)',
            'America/Los_Angeles' => 'America/Los Angeles (PST/PDT)',
            'America/Toronto' => 'America/Toronto',
            'America/Mexico_City' => 'America/Mexico City',
            'America/Sao_Paulo' => 'America/São Paulo',
            'America/Buenos_Aires' => 'America/Buenos Aires',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'Europe/Paris' => 'Europe/Paris (CET/CEST)',
            'Europe/Berlin' => 'Europe/Berlin (CET/CEST)',
            'Europe/Rome' => 'Europe/Rome (CET/CEST)',
            'Europe/Madrid' => 'Europe/Madrid (CET/CEST)',
            'Europe/Amsterdam' => 'Europe/Amsterdam (CET/CEST)',
            'Europe/Brussels' => 'Europe/Brussels (CET/CEST)',
            'Europe/Vienna' => 'Europe/Vienna (CET/CEST)',
            'Europe/Warsaw' => 'Europe/Warsaw (CET/CEST)',
            'Europe/Moscow' => 'Europe/Moscow (MSK)',
            'Europe/Istanbul' => 'Europe/Istanbul (TRT)',
            'Asia/Dubai' => 'Asia/Dubai (GST)',
            'Asia/Karachi' => 'Asia/Karachi (PKT)',
            'Asia/Kolkata' => 'Asia/Kolkata (IST)',
            'Asia/Dhaka' => 'Asia/Dhaka (BST)',
            'Asia/Bangkok' => 'Asia/Bangkok (ICT)',
            'Asia/Singapore' => 'Asia/Singapore (SGT)',
            'Asia/Hong_Kong' => 'Asia/Hong Kong (HKT)',
            'Asia/Shanghai' => 'Asia/Shanghai (CST)',
            'Asia/Tokyo' => 'Asia/Tokyo (JST)',
            'Asia/Seoul' => 'Asia/Seoul (KST)',
            'Australia/Sydney' => 'Australia/Sydney (AEDT/AEST)',
            'Australia/Melbourne' => 'Australia/Melbourne (AEDT/AEST)',
            'Australia/Brisbane' => 'Australia/Brisbane (AEST)',
            'Australia/Perth' => 'Australia/Perth (AWST)',
            'Pacific/Auckland' => 'Pacific/Auckland (NZDT/NZST)',
            'Africa/Cairo' => 'Africa/Cairo (EET)',
            'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
            'Africa/Lagos' => 'Africa/Lagos (WAT)',
            'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
        ];
    }
}
