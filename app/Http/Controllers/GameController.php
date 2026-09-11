<?php

namespace App\Http\Controllers;

use App\Game\Services\Chat;
use App\Game\Services\Economy;
use App\Game\Services\Elections;
use App\Game\Services\Friendship;
use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Military;
use App\Game\Services\Moderation;
use App\Game\Services\Payment;
use App\Game\Services\Politics;
use App\Game\Services\Rankings;
use App\Game\Services\Translator;
use App\Game\Support\Vars;
use Illuminate\Contracts\View\View;

/**
 * Base for all game pages: exposes the legacy service objects under their
 * original names and renders a content view inside the game layout.
 */
abstract class GameController extends Controller
{
    protected GameDatabase $database;

    protected GameContext $session;

    protected Translator $lang;

    protected Vars $vars;

    /** @var array<string,mixed> */
    protected array $citInfo = [];

    public function __construct()
    {
        $this->database = app(GameDatabase::class);
        $this->session = app(GameContext::class);
        $this->lang = app(Translator::class);
        $this->vars = app(Vars::class);
        $this->citInfo = $this->session->userinfo;
    }

    protected function war(): Military
    {
        return app(Military::class);
    }

    protected function eco(): Economy
    {
        return app(Economy::class);
    }

    protected function politics(): Politics
    {
        return app(Politics::class);
    }

    protected function elections(): Elections
    {
        return app(Elections::class);
    }

    protected function ranks(): Rankings
    {
        return app(Rankings::class);
    }

    protected function pays(): Payment
    {
        return app(Payment::class);
    }

    protected function mod(): Moderation
    {
        return app(Moderation::class);
    }

    protected function friendship(): Friendship
    {
        return app(Friendship::class);
    }

    protected function chat(): Chat
    {
        return app(Chat::class);
    }

    protected function loggedIn(): bool
    {
        return $this->session->logged_in;
    }

    protected function citID(): int
    {
        return (int) ($this->session->CitID ?? 0);
    }

    protected function isCA(): bool
    {
        return in_array($this->citInfo['accType'] ?? '', ['co-account', 'nca'], true);
    }

    protected function isNCA(): bool
    {
        return ($this->citInfo['accType'] ?? '') === 'nca';
    }

    protected function havePro(): bool
    {
        return ($this->citInfo['proExpire'] ?? 0) >= time();
    }

    protected function havePlus(): bool
    {
        return ($this->citInfo['plusExpire'] ?? 0) >= time();
    }

    /** Access flags (port of include/accesses.php). */
    protected function accesses(): array
    {
        $c = $this->citInfo;
        $level = (int) ($c['access'] ?? 0);
        $supermod = 7;
        $out = [
            'level' => $level,
            'is_supermod' => $level >= $supermod,
            'is_police' => $level === 6,
            'is_forummod' => $level === 2,
            'is_admin' => $level === 9,
            'is_supportman' => false,
        ];
        $out['can_view_lens'] = $out['is_police'] || $level >= $supermod;
        $out['can_access_lens'] = $out['can_view_lens'];
        $out['can_open_pp_elections'] = $level >= $supermod;
        $out['can_moderate_forum'] = $out['is_forummod'] || $level >= $supermod;
        $out['can_moderate_articles'] = $out['is_police'] || $level >= $supermod;
        $out['can_ban_citizens'] = ($c['at_punishment'] ?? 0) >= 1;
        $out['can_view_pms'] = $level >= 8;
        $out['can_view_appeals'] = $out['is_police'] || $level >= $supermod;
        $out['can_view_citizen_comments'] = $level >= $supermod;
        $out['can_view_private_data'] = (bool) ($c['at_mods'] ?? 0);
        $out['can_reply_tickets'] = $level >= $supermod;
        $out['can_define_mods'] = ($c['at_mods'] ?? 0) == 1;
        $out['is_war_minister'] = isset($c['CitizenID'], $c['minister_war']) && (int) $c['CitizenID'] === (int) $c['minister_war'];
        $out['is_nca_war'] = isset($c['CitizenID'], $c['nca_war']) && (int) $c['CitizenID'] === (int) $c['nca_war'];

        return $out;
    }

    /**
     * Render a page inside the game layout.
     *
     * @param  array  $layout  title, coltype (1|2|3), hideups (bool), ambient (default|company|army|war|main-out), actiontype, styles[], scripts[]
     */
    protected function page(string $view, array $data = [], array $layout = []): View
    {
        $layout += [
            'title' => '',
            'coltype' => 3,
            'hideups' => false,
            'ambient' => 'default',
            'actiontype' => 'page',
            'bar_title' => '',
        ];
        $data += [
            'citInfo' => $this->citInfo,
            'cit' => $this->citInfo,
            'logged' => $this->loggedIn(),
            'isCA' => $this->isCA(),
            'isNCA' => $this->isNCA(),
            'havePro' => $this->havePro(),
            'havePlus' => $this->havePlus(),
            'acc' => $this->accesses(),
            'now' => $this->session->getTodayArray(),
        ];

        return view($view, $data)->with('layout', $layout);
    }
}
