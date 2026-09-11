<?php

namespace App\View\Composers;

use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Translator;
use App\Game\Support\LegacyForm;
use App\Game\Support\Vars;
use Illuminate\View\View;

/**
 * Shares the legacy globals ($database, $session, $lang, $vars, $form, $citInfo…)
 * with every view, and computes the top-bar counters for the layout.
 */
class GameLayoutComposer
{
    public function __construct(
        protected GameDatabase $database,
        protected GameContext $session,
        protected Translator $lang,
        protected Vars $vars,
    ) {
    }

    public function compose(View $view): void
    {
        $cit = $this->session->userinfo;
        $isCA = in_array($cit['accType'] ?? '', ['co-account', 'nca'], true);
        $data = [
            'database' => $this->database,
            'session' => $this->session,
            'lang' => $this->lang,
            'vars' => $this->vars,
            'form' => app(LegacyForm::class),
            'citInfo' => $cit,
            'logged' => $this->session->logged_in,
            'isCA' => $isCA,
            'isNCA' => ($cit['accType'] ?? '') === 'nca',
            'sets' => $this->database->setting + config('ejahan.prices'),
            'is_local' => app()->environment('local'),
        ];
        if ($this->session->logged_in) {
            $citID = (int) $cit['CitizenID'];
            $data['citMoney'] = $this->database->getCitizenMoney($citID);
            $data['newPM'] = $this->database->getNewMSGs($citID);
            $data['newNote'] = $this->database->getNewNotes($citID);
            $data['friendRequests'] = (! $isCA && ($cit['active'] ?? 0)) ? $this->database->getPendingFriendRequests($citID) : [];
            $data['can_access_lens'] = ((int) ($cit['access'] ?? 0)) >= 6;
        }
        foreach ($data as $k => $v) {
            if (! array_key_exists($k, $view->getData())) {
                $view->with($k, $v);
            }
        }
    }
}
