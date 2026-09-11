<?php

namespace App\Game\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * DB-driven translation engine (port of include/translator.php). Phrases live in
 * trans_strings, grouped by "location". Falls back to English, and marks
 * untranslated phrases for members of the translation team.
 */
class Translator
{
    public string $lang = 'en';

    /** @var array<string, array<string,string>> */
    protected array $langdata = [];

    /** @var array<string, array<string,string>> */
    protected array $enlangdata = [];

    public bool $isTranslator = false;

    /** @var array<string,bool> */
    protected array $loaded = [];

    public function setLanguage(string $lang, ?int $citID = null): static
    {
        $lang = in_array($lang, config('ejahan.languages'), true) ? $lang : 'en';
        if ($lang !== $this->lang) {
            $this->langdata = $this->enlangdata = $this->loaded = [];
        }
        $this->lang = $lang;
        $this->isTranslator = $citID
            ? DB::table('trans_team')->where('langID', $lang)->where('citID', $citID)->exists()
            : false;
        foreach (['main', 'msgs', 'country'] as $loc) {
            $this->addPhrases($loc);
        }

        return $this;
    }

    /** Drop every cached phrase set (after translations are edited). */
    public function flush(): void
    {
        foreach (DB::table('trans_strings')->distinct()->pluck('location') as $loc) {
            foreach (array_merge(['en'], config('ejahan.languages', [])) as $lang) {
                Cache::forget("ejahan.trans.{$lang}.{$loc}");
            }
        }
        $this->loaded = [];
    }

    public function addPhrases(string $loc = 'main'): void
    {
        if (isset($this->loaded[$loc])) {
            return;
        }
        $this->loaded[$loc] = true;
        $lang = $this->lang;
        $key = "ejahan.trans.{$lang}.{$loc}";
        $data = Cache::remember($key, 300, function () use ($lang, $loc) {
            $col = ($lang && $lang !== 'en') ? 'trans_'.$lang : 'trans_en';
            $rows = DB::table('trans_strings')->where('location', $loc)->get(['phrase', 'trans_en', DB::raw("`{$col}` AS str")]);
            $out = ['str' => [], 'en' => []];
            foreach ($rows as $r) {
                $out['str'][$r->phrase] = $r->str;
                $out['en'][$r->phrase] = $r->trans_en;
            }

            return $out;
        });
        $this->langdata[$loc] = $data['str'];
        $this->enlangdata[$loc] = $data['en'];
    }

    public function getstr(string $phrase, string $loc = 'main'): string
    {
        if (! isset($this->loaded[$loc])) {
            $this->addPhrases($loc);
        }
        $str = $this->langdata[$loc][$phrase] ?? null;
        if ($str !== null && $str !== '') {
            return $str;
        }
        $en = $this->enlangdata[$loc][$phrase] ?? null;
        if ($en !== null && $en !== '') {
            if ($this->isTranslator) {
                return "<font title=\"This phrase is ready for translation! Section: {$loc}\" style=\"border-bottom: 1px dashed red\">{$en}</font>";
            }

            return $en;
        }

        return $phrase;
    }

    /** Blade-friendly alias. */
    public function t(string $phrase, string $loc = 'main'): string
    {
        return $this->getstr($phrase, $loc);
    }
}
