<?php

namespace App\Game\Services;

use App\Game\Support\Url;

/**
 * Port of include/noteparser.php ($nparser): renders typed notifications.
 */
class NoteParser
{
    public function __construct(protected Translator $lang, protected Url $url)
    {
        $this->lang->addPhrases('regions');
        $this->lang->addPhrases('notes');
    }

    public function parseNote(array $note): string
    {
        $ctx = app(GameContext::class);
        $type = (string) ($note['Type'] ?? '');
        $holders = explode('|', (string) $note['Body']);
        switch ($type) {
            case '':
            case '0':
                return (string) $note['Body'];
            case 'addreq':
                $h1 = $holders;
                $h1[0] = '<a href="'.$this->url->getURL('profile', $holders[0]).'">'.($holders[1] ?? '').'</a>';
                $h1[1] = ($holders[2] ?? '') ? $this->lang->getstr('her') : $this->lang->getstr('his');
                $h1[2] = '<a href="'.$this->url->getURL('profile', $ctx->CitID, 'accept', $holders[0]).'">'.$this->lang->getstr('yes').'</a> <a href="'
                    .$this->url->getURL('profile', $ctx->CitID, 'reject', $holders[0]).'">'.$this->lang->getstr('no').'</a>';

                return $this->fmt('note_addreq', $h1);
            case 'cb_rcv':
                $this->lang->addPhrases('chancebox');

                return $this->fmt('note_cb_rcv', [$holders[0] ?? '', $this->lang->getstr('cb_reason_'.($holders[1] ?? ''), 'chancebox')]);
            case 'cb_won':
                $this->lang->addPhrases('chancebox');

                return $this->fmt('note_cb_won', [sprintf($this->lang->getstr('cb_prize_'.($holders[0] ?? ''), 'chancebox'), $holders[1] ?? '')]);
            case 'cpwin':
                return $this->fmt('note_cpwin', [$holders[0] ?? '', $this->lang->getstr($holders[1] ?? '', 'country')]);
            case 'rageshot':
                return $this->fmt('note_rageshot', [
                    $this->url->getURL('country', $holders[0] ?? ''),
                    $this->lang->getstr($holders[1] ?? '', 'country'),
                    $holders[2] ?? '',
                    $this->url->getURL('region', $holders[3] ?? ''),
                    $this->lang->getstr('region_'.($holders[3] ?? ''), 'regions'),
                ]);
            case 'beCG':
                return $this->fmt('note_beCG', [
                    '<a href="'.$this->url->getURL('region', $holders[0] ?? '').'">'.$this->lang->getstr('region_'.($holders[0] ?? ''), 'regions').'</a>',
                    '<a href="'.$this->url->getURL('party', $holders[1] ?? '').'">'.($holders[2] ?? '').'</a>',
                ]);
            case 'rank_up':
                return 'Congratulations! You reached the military rank of <b>'.($holders[0] ?? '').'</b>. You received '.($holders[1] ?? '').' Tala and a 5-star food.';
            default:
                return $this->fmt('note_'.$type, $holders);
        }
    }

    private function fmt(string $phrase, array $args): string
    {
        $tpl = $this->lang->getstr($phrase, 'notes');
        $needed = preg_match_all('/%[sd]/', $tpl);
        $args = array_pad(array_values($args), $needed, '');

        return @vsprintf($tpl, $args) ?: $tpl;
    }
}
