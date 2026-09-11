<?php

namespace App\Http\Middleware;

use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Ip2Country;
use App\Game\Services\Translator;
use App\Game\Support\Url;
use Closure;
use Illuminate\Http\Request;

/**
 * Boots the per-request game state (port of the top of index.php):
 *  - maintenance mode
 *  - the citizen context ($session) and active user/guest tracking
 *  - language resolution (URL suffix / citizen setting / geo-IP) with the legacy redirects
 *  - "first login of the day" EP reward
 */
class GameSession
{
    public function __construct(
        protected GameDatabase $database,
        protected GameContext $ctx,
        protected Translator $lang,
        protected Url $url,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->ctx->boot();
        $cit = $this->ctx->userinfo;

        // Maintenance mode (admins and the legacy whitelist IP pass through)
        $due = (int) ($this->database->setting['maintenance_due'] ?? 0);
        if ((config('ejahan.maintenance.mode') || $due >= time()) && ! $this->ctx->isAdmin() && ! $request->is('loginprocess.html')) {
            return response()->view('maintenance', ['main_msg' => null], 503);
        }

        // Language (the {lang} route parameter is consumed here so controllers only receive page params)
        $avail = config('ejahan.languages');
        $urlLang = $request->route('lang') ?? $request->query('language');
        if ($request->route() && $request->route('lang') !== null) {
            $request->route()->forgetParameter('lang');
        }
        if ($this->ctx->logged_in) {
            $lang = $cit['setting_lang'] ?? 'en';
            if ($urlLang && in_array($urlLang, $avail, true) && $urlLang !== $lang && $request->routeIs('home')) {
                // language switch via the flags on the home page
                $this->database->updateUserFieldID($cit['CitizenID'], 'setting_lang', $urlLang);
                $lang = $urlLang;
            } elseif ($urlLang && in_array($urlLang, $avail, true) && $urlLang !== $lang) {
                return redirect($this->swapLang($request->getRequestUri(), $lang));
            }
        } else {
            if ($urlLang && in_array($urlLang, $avail, true)) {
                $lang = $urlLang;
            } elseif (session()->has('language')) {
                $lang = session('language');
            } else {
                $code = app(Ip2Country::class)->get_country_code();
                $lang = config('ejahan.geo_languages')[$code] ?? 'en';
            }
        }
        if (! in_array($lang, $avail, true)) {
            $lang = 'en';
        }
        session(['language' => $lang]);
        app()->setLocale($lang === 'fa' ? 'fa' : 'en');
        $this->lang->setLanguage($lang, $this->ctx->CitID);
        $this->url->setLang($lang);

        // Pages that carry a language suffix redirect when it is missing (legacy behaviour)
        if ($request->route()?->getName() === 'home' && ! $urlLang) {
            return redirect('/index-'.$lang.'.html');
        }

        // First login of the day: +1 EP and the daily task reset
        if ($this->ctx->logged_in && (int) ($cit['LastLoggedIn'] ?? 0) < $this->database->getToday()) {
            $this->database->updateUserFieldID($cit['CitizenID'], 'LastLoggedIn', $this->database->getToday());
            $this->database->updateUserFieldID($cit['CitizenID'], 'activeTask', 1);
            $this->database->addEP($cit['CitizenID'], 1, 'Logging into site');
            $this->ctx->fillInfo(null, true);
        }

        return $next($request);
    }

    private function swapLang(string $uri, string $lang): string
    {
        return preg_replace('/-[a-z]{2}\.html(\?.*)?$/', '-'.$lang.'.html$1', $uri) ?: $uri;
    }
}
