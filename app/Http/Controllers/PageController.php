<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmitted;
use App\Models\Page;
use App\Models\Setting;
use App\Support\DynamicMailConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    private function renderPage(string $slug)
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.show', compact('page'))->with([
            'metaTitle' => $page->meta_title ?: $page->title,
            'metaDescription' => $page->meta_description ?: str($page->content)->stripTags()->limit(160)->toString(),
        ]);
    }

    public function show(string $localeOrSlug, ?string $slug = null)
    {
        return $this->renderPage($slug ?? $localeOrSlug);
    }

    public function about()
    {
        return $this->renderPage('hakkimizda');
    }

    public function editorialPolicy()
    {
        return $this->renderPage('yayin-ilkeleri');
    }

    public function privacy()
    {
        return $this->renderPage('gizlilik-politikasi');
    }

    public function kvkk()
    {
        return $this->renderPage('kvkk-aydinlatma');
    }

    public function cookies()
    {
        return $this->renderPage('cerez-politikasi');
    }

    public function contact()
    {
        return view('pages.contact')->with([
            'metaTitle' => __('İletişim'),
            'metaDescription' => __('Adıyaman Dijital Haber ile haber ihbarı, reklam iş birlikleri ve okur geri bildirimleri için iletişime geçin.'),
        ]);
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:100',
            'message' => 'required|string|max:5000',
        ]);

        $recipient = $this->contactRecipientEmail();

        if (blank($recipient)) {
            Log::warning('Iletisim formu alici e-posta adresi bulunamadi.', [
                'sender' => $validated['email'],
            ]);

            return back()
                ->withInput()
                ->withErrors(['contact' => __('Mesaj alıcı e-posta adresi yapılandırılmamış. Lütfen daha sonra tekrar deneyin.')]);
        }

        try {
            DynamicMailConfig::apply();

            Mail::to($recipient)->send(new ContactFormSubmitted($validated));
        } catch (\Throwable $exception) {
            Log::error('Iletisim formu e-posta gonderimi basarisiz.', [
                'recipient' => $recipient,
                'sender' => $validated['email'],
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['contact' => __('Mesaj gönderilemedi. Lütfen daha sonra tekrar deneyin.')]);
        }

        Log::channel('single')->info('Iletisim formu gonderildi', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'] ?? '-',
            'recipient' => $recipient,
        ]);

        return back()->with('success', __('Mesajiniz basariyla gonderildi. En kisa surede donus yapacagiz.'));
    }

    private function contactRecipientEmail(): ?string
    {
        foreach ([
            Setting::get('general', 'contact_recipient_email'),
            Setting::get('general', 'contact_email'),
            config('mail.from.address'),
        ] as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }
}
