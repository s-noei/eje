<?php

namespace App\Game\Support;

/**
 * Builds the pretty ".html" URLs exactly as the legacy Functions::getURL did,
 * always suffixed with the active language ("-en.html").
 */
class Url
{
    public function __construct(protected string $lang = 'en')
    {
    }

    public function setLang(string $lang): static
    {
        $this->lang = $lang;

        return $this;
    }

    /** selURL — append "-{lang}" before ".html". */
    public function selURL(string $noseo, string $seo): string
    {
        $loc = stripos($seo, '.html');

        return '/'.substr($seo, 0, $loc === false ? strlen($seo) : $loc).'-'.$this->lang.'.html';
    }

    public function getURL(string $type, $p1 = '', $p2 = '', $p3 = '', $p4 = '', $p5 = '', $p6 = ''): string
    {
        $s = fn (string $seo) => $this->selURL('', $seo);
        switch ($type) {
            case 'home':
                return $s('index.html');
            case 'ads':
                if (! $p1) {
                    return $s('ads.html');
                }

                return $p2 ? $s("ads-$p1-$p2.html") : $s("ads-$p1.html");
            case 'admin':
                if (! $p1) {
                    return $s('admin.html');
                }

                return $p4 ? $s("admin-$p1-$p2-$p3-$p4.html") : $s("admin-$p1-$p2-$p3.html");
            case 'activation':
                return $s("activation-$p1.html");
            case 'adminprocess':
                return $s('admin-adminprocess.html');
            case 'army':
                return $s('army.html');
            case 'article':
                return $p2 ? $s("article-$p1-$p2.html") : $s("article-$p1.html");
            case 'battle':
                if ($p2) {
                    if (in_array($p2, ['stats', 'att', 'def'], true)) {
                        return $s("battle-$p1-$p2.html");
                    }

                    return $s("battle-$p1-$p2-$p3.html");
                }

                return $s("battle-$p1.html");
            case 'chancebox':
                if (! $p1) {
                    return $s('chancebox.html');
                }

                return $p2 ? $s("chancebox-$p1-$p2.html") : $s("chancebox-$p1.html");
            case 'clicense':
                return $s("company-license-$p1.html");
            case 'cmarket':
                return ($p1 === '' || $p1 === null) ? $s('company_market.html') : $s("company_market-$p1-$p2.html");
            case 'company':
                if (! $p1) {
                    return $s('company.html');
                }
                if (! $p2) {
                    return $s("company-$p1.html");
                }

                return $p3 ? $s("company-$p1-$p2-$p3.html") : $s("company-$p1-$p2.html");
            case 'contact':
                if ($p1) {
                    return $p2 ? $s("contact-$p1-$p2.html") : $s("contact-$p1.html");
                }

                return $s('contact.html');
            case 'congress':
                return $s("congress-$p1-".($p2 ?: 1).'.html');
            case 'country':
                if (! $p2) {
                    return $s("country-$p1.html");
                }

                return $p2 === 'congress' ? $s("country-$p1-$p2-".($p3 ?: 1).'.html') : $s("country-$p1-$p2.html");
            case 'create':
                return $s("create-$p1.html");
            case 'elections':
                switch ($p1) {
                    case 'pp':
                        return $s("election-pp-$p2-$p3-$p4-$p5.html");
                    case 'cp':
                        return $s("election-cp-$p2-$p4-$p5.html");
                    case 'cg':
                        return $s("election-cg-$p2-$p3-$p4-$p5.html");
                    default:
                        return $s('elections.html');
                }
            case 'exchange':
                if ($p3) {
                    return $s("exchange-$p1-$p2-$p3.html");
                }
                if (! $p1) {
                    return $s('exchange.html');
                }
                if ($p1 !== 'my') {
                    return $s("exchange-$p1-$p2.html");
                }

                return $p2 ? $s("exchange-$p1-$p2.html") : $s("exchange-$p1.html");
            case 'ejstore':
                return $p1 ? $s("ejstore-$p1.html") : $s('ejstore.html');
            case 'extra':
                return $p1 ? $s("extra-$p1.html") : $s('extra.html');
            case 'fight':
                return $s("fight-$p1.html");
            case 'finance':
                return $s("finance-$p1.html");
            case 'forgot':
                return $s('forgotpass.html');
            case 'forum':
                if ($p3) {
                    return $s("forum-$p1-$p2-$p3.html");
                }

                return $p1 ? $s("forum-$p1-$p2.html") : $s('forum.html');
            case 'imarket':
                if (! $p1) {
                    return $s('imarket.html');
                }

                return $p1 === 'my' ? $s('imarket-my.html') : $s("imarket-$p1-$p2.html");
            case 'invite':
                return $s('invite.html');
            case 'items':
                return $s('items.html');
            case 'jobs':
                if (! $p1 && ! $p2) {
                    return $s('jobs.html');
                }

                return $s("jobs-$p1-$p2-".($p3 ?: '1').'.html');
            case 'law':
                return $p2 ? $s("law-$p1-$p2.html") : $s("law-$p1.html");
            case 'laws':
                return $s('laws.html');
            case 'login':
                return '/login.html';
            case 'lottery':
                if ($p1) {
                    $p2 = $p2 ?: app(GameClock::class)->today;

                    return $s("lottery-$p1-$p2.html");
                }

                return $s('lottery.html');
            case 'mail':
                return $p1 ? $s("mail-$p1-$p2.html") : $s('mail.html');
            case 'map':
                return $s('map.html');
            case 'market':
                return $p1 ? $s("market-$p1-$p2-$p3.html") : $s('market.html');
            case 'media':
                $p1 = $p1 ?: '0';
                $p2 = $p2 ?: 'top';
                $p3 = $p3 ?: '1';

                return $s("media-$p1-$p2-$p3.html");
            case 'mines':
                return $s('mines.html');
            case 'military-unit':
                return $p1 ? $s("military-unit-$p1.html") : $s('military-unit.html');
            case 'newspaper':
                if (! $p1) {
                    return $s('newspaper.html');
                }
                if (! $p2) {
                    return $s("newspaper-$p1.html");
                }
                if (is_numeric($p2) && $p2 > 0) {
                    return $s("newspaper-$p1-page-$p2.html");
                }

                return $s("newspaper-$p1-$p2.html");
            case 'online':
                if (! $p1) {
                    return $s('online.html');
                }

                return $p2 ? $s("online-$p1-$p2.html") : $s("online-$p1.html");
            case 'party':
                if (! $p1) {
                    return $s('party.html');
                }

                return $p2 ? $s("party-$p1-$p2.html") : $s("party-$p1.html");
            case 'profile':
                if ($p3) {
                    return $s("profile-$p1-$p2-$p3.html");
                }

                return $p2 ? $s("profile-$p1-$p2.html") : $s("profile-$p1.html");
            case 'ranking':
                $p1 = $p1 ?: 'citizens';
                $p2 = $p2 ?: '1';

                $p3 = $p3 ?: '0';

                return $p4 ? $s("ranking-$p1-$p2-$p3-$p4.html") : $s("ranking-$p1-$p2-$p3.html");
            case 'register':
                return $p1 ? $s("register-$p1.html") : $s('register.html');
            case 'region':
                return $s("region-$p1.html");
            case 'revive':
                return $s('revive.html');
            case 'search':
                return $s('search.html');
            case 'sms':
                return $p1 ? $s("sms-$p1.html") : $s('sms.html');
            case 'special':
                return $s('special.html');
            case 'trans':
                return $p1 ? $s("trans-$p1.html") : $s('trans.html');
            case 'war':
                return $p2 ? $s("war-$p1-$p2.html") : $s("war-$p1.html");
            case 'wars':
                $p1 = $p1 ?: '0';
                $p2 = in_array($p2, ['act', 'end', 'all'], true) ? $p2 : 'all';
                $p3 = in_array($p3, ['war', 'rev', 'all'], true) ? $p3 : 'all';
                $p4 = $p4 ?: '1';

                return $s("wars-$p1-$p2-$p3-$p4.html");
            case 'workers':
                return $s("workers-$p1.html");
            default:
                return '';
        }
    }
}
