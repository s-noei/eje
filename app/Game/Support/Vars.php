<?php

namespace App\Game\Support;

use App\Game\Services\GameDatabase;
use App\Game\Services\Translator;

/**
 * Port of the legacy Functions class ($vars): URL/asset helpers, number
 * formatting, avatar/puberty widgets used all over the templates.
 */
class Vars
{
    public array $sets = [];

    public string $resURL = '';

    public array $browser = [];

    public function __construct(
        protected GameDatabase $database,
        protected Translator $lang,
        protected Url $url,
    ) {
        $this->sets = $this->database->getSettings() ?: [];
        $this->resURL = rtrim((string) config('app.url'), '/');
        $this->browser = $this->getBrowser();
    }

    public function getURL(string $type, $p1 = '', $p2 = '', $p3 = '', $p4 = '', $p5 = '', $p6 = ''): string
    {
        return $this->url->getURL($type, $p1, $p2, $p3, $p4, $p5, $p6);
    }

    public function selURL(string $noseo, string $seo): string
    {
        return $this->url->selURL($noseo, $seo);
    }

    public function selText($cond, $logged_in, $logged_out)
    {
        return $cond ? $logged_in : $logged_out;
    }

    public function getBrowser(): array
    {
        $u_agent = (string) request()->userAgent();
        $bname = 'Unknown';
        $platform = 'Unknown';
        $ub = 'other';
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        if (preg_match('/MSIE/i', $u_agent) && ! preg_match('/Opera/i', $u_agent)) {
            [$bname, $ub] = ['Internet Explorer', 'MSIE'];
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            [$bname, $ub] = ['Mozilla Firefox', 'Firefox'];
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            [$bname, $ub] = ['Google Chrome', 'Chrome'];
        } elseif (preg_match('/Safari/i', $u_agent)) {
            [$bname, $ub] = ['Apple Safari', 'Safari'];
        } elseif (preg_match('/Opera/i', $u_agent)) {
            [$bname, $ub] = ['Opera', 'Opera'];
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            [$bname, $ub] = ['Netscape', 'Netscape'];
        }
        $pattern = '#(?<browser>Version|'.preg_quote($ub, '#').'|other)[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        $version = '?';
        if (preg_match_all($pattern, $u_agent, $matches) && count($matches['version'])) {
            $version = count($matches['browser']) !== 1 && strripos($u_agent, 'Version') < strripos($u_agent, $ub)
                ? ($matches['version'][0] ?? '?')
                : ($matches['version'][count($matches['browser']) !== 1 ? 1 : 0] ?? '?');
            $version = $version ?: '?';
        }

        return ['userAgent' => $u_agent, 'name' => $bname, 'version' => $version, 'platform' => $platform, 'pattern' => $pattern];
    }

    public function checkCaptcha(?string $captcha): int
    {
        return (md5((string) $captcha) === session('captcha')) ? 1 : 0;
    }

    /** Asset folder for a kind of image. */
    public function getImgLoc(string $what): string
    {
        $loc = match ($what) {
            'CitizenAvatar' => 'uploads/avatars/citizen/',
            'CompanyAvatar' => 'uploads/avatars/company/',
            'CountryAFlag' => 'images/flags/animated/',
            'CountryFlag' => 'images/flags/l/',
            'CurrencyIcon' => 'images/flags/s/',
            'Icon' => 'images/icons/',
            'NPAvatar', 'npAvatar' => 'uploads/avatars/newspaper/',
            'PartyLogo' => 'uploads/avatars/party/',
            'MULogo' => 'uploads/avatars/military-unit/',
            default => '',
        };

        return '/'.$loc;
    }

    /** Avatar with puberty badge (HTML). */
    public function getAvatar(array $cit, string $class, $border = 1): string
    {
        $rgb = 'rgb(0, 100, 0)';
        $title = '';
        $lvl = 1;
        if (($cit['accType'] ?? '') === 'co-account') {
            $rgb = 'rgb(229, 227, 28)';
            $citOw = $this->database->getUserInfoFromID($cit['accOwner'], 0);
            $title = 'Co-Account for '.e($citOw['name'] ?? '');
            $lvl = 'CA';
        } else {
            for ($i = 0; $i < count(Constants::PUB_RANKS); $i++) {
                if (($cit['ep'] ?? 0) >= Constants::PUB_EPS[$i]) {
                    $title = Constants::PUB_RANKS[$i];
                    $lvl = $i + 1;
                }
            }
        }
        $tit = 'cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]';
        $title_body = $this->lang->getstr('living_in').': '.($cit['RegionName'] ?? '').', '.($cit['cName'] ?? '')
            .((($cit['accType'] ?? '') !== 'co-account') && (($cit['accType'] ?? '') !== 'nca') ? '<br>'.$this->lang->getstr('ep').': '.($cit['ep'] ?? 0) : '').'<br>'
            .((($cit['accType'] ?? '') === 'co-account') ? $title : ((($cit['accType'] ?? '') === 'nca') ? 'National CA' : $this->lang->getstr('puberty').': '.$title));

        return '<div title="'.sprintf($tit, e($cit['name'] ?? ''), $title_body).'" style="display: inline-block;">'
            .'<div style="position: absolute; margin-left: -5px; margin-top: -5px; color: white; padding: 3px 0px; width: 20px; height: 14px; font-weight: bold; font-size: 8pt; border-radius: 5px; text-align:center; background: '.$rgb.'">'.$lvl.'</div>'
            .'<img src="'.$this->getImgLoc('CitizenAvatar').($cit['Avatar'] ?? 'noavatar.gif').'" class="'.$class.'" align="absmiddle"'
            .($border ? ' style="border: 2px solid '.$rgb.'; border-radius: 5px;"' : '').' alt="'.e($cit['name'] ?? '').'"></div>';
    }

    public function getAccPaid(array $cit): string
    {
        $time = time();
        $title = '';
        $exp = 0;
        if (($cit['proExpire'] ?? 0) >= $time) {
            $title = $this->lang->getstr('pro_account');
            $exp = $cit['proExpire'];
        } elseif (($cit['plusExpire'] ?? 0) >= $time) {
            $title = $this->lang->getstr('plus_account');
            $exp = $cit['plusExpire'];
        }
        if (! $title) {
            return '';
        }

        return sprintf($this->lang->getstr('account_validity'), $title, app(\App\Game\Services\GameContext::class)->getDiffF($exp));
    }

    public function getShortText(string $longString, int $len = 25): string
    {
        $separator = '...';
        $maxlength = $len - strlen($separator);
        $start = (int) ($maxlength / 2);
        $trunc = strlen($longString) - $maxlength;

        return $trunc > 0 ? substr_replace($longString, $separator, $start, $trunc) : $longString;
    }

    public function getWikiLink(string $type, string $name, string $title = 'read_wiki', string $icon = 'wiki-icon.gif', int|string $size = 24): string
    {
        $title = $this->lang->getstr($title);
        $wiki = config('ejahan.wiki_url').'/content';
        $type = str_replace(' ', '_', $type);
        $name = str_replace(' ', '_', $name);

        return "<a href=\"{$wiki}/{$type}_{$name}\" target=\"_blank\"><img src=\"/images/{$icon}\" height=\"{$size}\" class=\"inlineIMGs\" align=\"absmiddle\" title=\"{$title}\"></a>";
    }

    public function between($var, $min, $max): bool
    {
        return $var <= $max && $var > $min;
    }

    public function lenTrim(?string $str, int $len = 20, string $type = 'char', $end = 1, $title = 0): string
    {
        $str = (string) $str;
        $newstr = $str;
        if (strlen($str) > $len) {
            $newstr = $end ? substr($str, 0, $len - 6).'&hellip;'.substr($str, -3) : substr($str, 0, $len - 3).'&hellip;';
        }
        if ($title && $newstr !== $str) {
            $newstr = "<font title=\"cssbody=[bodydiv] cssheader=[headdiv] header=[{$title}] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;{$str}&lt;/div&gt;]\">{$newstr}</font>";
        }

        return $newstr;
    }

    public function convert_urls(string $str): string
    {
        return preg_replace('/(http:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $str);
    }

    /** Persian/Arabic digit rendering for fa/ar. */
    public function formatnumbers($str, string $ln = ''): string
    {
        $str = (string) $str;
        $ln = $ln ?: $this->lang->lang;
        if ($ln !== 'fa' && $ln !== 'ar') {
            return $str;
        }
        $start = $ln === 'fa' ? 1776 : 1632;
        $out = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $p = $str[$i];
            $out .= ctype_digit($p) ? '&#'.($start + (int) $p).';' : $p;
        }

        return $out;
    }

    public function viewIndicator($start, $end, $pos, $width, $col, $title, $descr = '%s', $subs = 1): string
    {
        $dist1 = $pos - ($subs ? $start : 0);
        $dist2 = $end - ($subs ? $start : 0);
        $tit = 'cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]';
        $desc = sprintf($descr, "$dist1/$dist2");
        $pct = $dist2 ? round($dist1 * 100 / $dist2, 2) : 0;

        return '<div class="indicator-e" style="width: '.$width.'px; border-color: '.$col.'" title="'.sprintf($tit, $title, $desc).'">'
            .'<div class="indicator-f" style="width: '.$pct.'%; background: '.$col.'"></div>'
            .'<div class="indicator-u" style="width: '.$pct.'%"></div></div>';
    }

    public function viewPub(array $cit, $small = 0): string
    {
        $ep = $cit['ep'] ?? 0;
        $cpub = (int) ($cit['puberty'] ?? 0);
        $npub = $cpub + 1;
        $crpub = Constants::PUB_EPS[$cpub] ?? 0;
        $nxpub = Constants::PUB_EPS[$npub] ?? $crpub;
        $pubname = Constants::PUB_RANKS[$cpub] ?? '';
        $dist1 = $ep - $crpub;
        $dist2 = $nxpub - $crpub;
        $dist3 = $nxpub - $ep;
        $col = Constants::PUB_COLORS[$cpub] ?? 'rgb(0,100,0)';
        $tit = 'cssbody=[bodydiv] cssheader=[headdiv] header=[%s] fade=[on] fadespeed=[0.1] body=[&lt;div&gt;%s&lt;/div&gt;]';
        $desc = "EP: $ep<br>Next puberty on: $nxpub<br>EP to next puberty: $dist3";
        $addit = $small ? 'erty' : '';
        $pct = $dist2 ? round($dist1 * 100 / $dist2, 2) : 100;

        return '<div class="pub'.$addit.'-e" style="border-color: '.$col.'" title="'.sprintf($tit, $pubname, $desc).'">'
            .'<div class="pub'.$addit.'-f" style="width: '.$pct.'%; background: '.$col.'"></div>'
            .'<div class="pub'.$addit.'-u" style="width: '.$pct.'%"></div></div>';
    }

    public function utfcorrect($str)
    {
        return $str;
    }
}
