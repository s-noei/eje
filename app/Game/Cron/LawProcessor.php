<?php

namespace App\Game\Cron;

use App\Game\Services\Economy;
use App\Game\Services\Elections;
use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use App\Game\Services\Military;
use App\Game\Services\Politics;
use App\Game\Support\Url;
use Illuminate\Support\Facades\Hash;

/** Port of include/cron/laws.php — applies congress laws whose voting period has ended. */
class LawProcessor
{
    public function __construct(
        protected GameDatabase $database, protected Politics $politics, protected Elections $elections,
        protected Economy $eco, protected Military $war, protected Url $url, protected GameContext $session,
    ) {
    }

    /** @return int number of laws processed */
    public function run(): int
    {
        $db = $this->database;
        $laws = $db->rows("SELECT laws.*, country.cName FROM laws JOIN country ON country.CountryID = laws.CountryID WHERE dTime < ? AND Status = '0' ORDER BY dTime", [time()]);
        foreach ($laws as $law) {
            $v = $this->politics->getVotes($law['lawID']);
            $rejected = (($v['YES'] <= $v['NO'] && $law['Type'] !== 'Impeach' && $law['Type'] !== 'Prefix')
                || ($v['YES'] < ($v['NO'] * 2) && $law['Type'] === 'Impeach')
                || ($v['YES'] < ($v['NO'] * 3) && $law['Type'] === 'Prefix'))
                && ($v['YES'] > 0 || $v['NO'] > 0);
            if ($rejected) {
                $db->exec("UPDATE laws SET Status = '2' WHERE lawID = ?", [$law['lawID']]);
                $this->reject($law);
            } else {
                $db->exec("UPDATE laws SET Status = '1' WHERE lawID = ?", [$law['lawID']]);
                $this->approve($law);
            }
        }

        return count($laws);
    }

    protected function reject(array $law): void
    {
        $db = $this->database;
        $p = explode(',', (string) $law['Params']);
        $tag = "> Law {$law['lawID']} rejected <";
        switch ($law['Type']) {
            case 'Issue':
                $db->addMoney(1, ((float) $law['Params']) * 0.02, $law['CountryID'], 'country', 1, $tag);
                break;
            case 'Donate':
                $db->addMoney((int) $p[1], (float) $p[0], $law['CountryID'], 'country', 1, $tag);
                break;
            case 'BuyClinic':
            case 'BuyMunic':
                $comp = $db->getCompany($p[2]);
                $curID = $law['Type'] === 'BuyClinic' ? $law['CountryID'] : $db->getCurrencyID($law['CountryID']);
                $db->addMoney($curID, (float) $p[1], $law['CountryID'], 'country', 1, $tag);
                if ($comp) {
                    $db->updateCompanyField($p[2], 'Stock', $comp['Stock'] + 1);
                }
                break;
            case 'DeclareWar':
                $db->addMoney(1, (float) ($p[2] ?? 0), $law['CountryID'], 'country', 1, $tag);
                break;
            case 'Prefix':
                $db->addMoney(1, 5, $law['CountryID'], 'country', 1, $tag);
                break;
            case 'ProposePeace':
                [$price, , $tarID, $supLaw] = array_pad($p, 4, null);
                if ($supLaw) {
                    $db->addMoney(1, (float) $price, $tarID, 'country', 1, $tag);
                }
                break;
            case 'Warca':
                $db->exec('UPDATE citizens SET active = 1 WHERE CitizenID = ?', [(int) $p[0]]);
                break;
        }
    }

    protected function approve(array $law): void
    {
        $db = $this->database;
        $p = explode(',', (string) $law['Params']);
        $coun = $law['CountryID'];
        $tag = "> Law {$law['lawID']} approved <";
        switch ($law['Type']) {
            case 'Tax':
                $db->exec('UPDATE taxes SET Income = ?, Import = ?, VAT = ? WHERE CountryID = ? AND IndustryID = ?', [(int) $p[1], (int) $p[2], (int) $p[3], $coun, (int) $p[0]]);
                $db->setMarketOffers($coun, (int) $p[0]);
                break;
            case 'Fee':
                $db->exec('UPDATE country SET cFee = ? WHERE CountryID = ?', [(int) $p[1], $coun]);
                break;
            case 'nFee':
                $db->exec('UPDATE country SET nFee = ? WHERE CountryID = ?', [(int) $p[1], $coun]);
                break;
            case 'Prefix':
                $db->exec('UPDATE country SET Prefix = ? WHERE CountryID = ?', [$p[1] ?? '', $coun]);
                break;
            case 'WelMsg':
                $db->exec('UPDATE country SET welcome_message = ? WHERE CountryID = ?', [(string) $law['Params'], $coun]);
                break;
            case 'Issue':
                $db->addMoney($db->getCurrencyID($coun), (float) $law['Params'], $coun, 'country', 1, $tag);
                break;
            case 'Donate':
                $db->addMoney((int) $p[1], (float) $p[0], (int) $p[2], 'citizen', 1, $tag);
                break;
            case 'Impeach':
                $elec = $this->elections->getLastElection('CP');
                $eDat = !empty($elec['timestamp']) ? $this->elections->getElectionCP($coun, (int) date('Y', $elec['timestamp']), (int) date('m', $elec['timestamp'])) : [];
                $cp = $this->politics->getCP($coun);
                $newCPID = $cp['CitizenID'] ?? '';
                if ($newCPID) {
                    foreach ($eDat as $cand) {
                        if (!$newCPID) {
                            $newCPID = $cand['CandidateID'];
                        } elseif ($cand['CandidateID'] == $newCPID) {
                            $newCPID = '';
                        }
                    }
                }
                if ($newCPID == ($cp['CitizenID'] ?? '')) {
                    $newCPID = '';
                }
                $this->politics->setCP($coun, (int) $newCPID);
                break;
            case 'Notrade':
                if (!$this->eco->getEmbargoes($coun, 'trade', (int) $p[1])) {
                    $this->eco->addEmbargo($coun, (int) $p[1], 'trade');
                    $this->eco->stopTrade($coun, (int) $p[1]);
                }
                break;
            case 'Notravel':
                if (!$this->eco->getEmbargoes($coun, 'travel', (int) $p[1])) {
                    $this->eco->addEmbargo($coun, (int) $p[1], 'travel');
                }
                break;
            case 'Alliance':
                [, $cID, $dur, $price, $supLaw] = array_pad($p, 5, null);
                if ($supLaw) {
                    $sup = $this->politics->getLaw($supLaw);
                    if ($sup && $sup['Status'] == 1 && !$this->war->haveWar($coun, $cID)) {
                        $this->war->addAlly($coun, $cID, (int) $dur);
                    } else {
                        $db->addMoney(1, (float) $price, $cID, 'country', 1, "> Law $supLaw rejected <");
                        $db->addMoney(1, (float) $price, $coun, 'country', 1, "> Law $supLaw rejected <");
                    }
                }
                break;
            case 'Ministry':
                $mpost = strtolower((string) ($p[2] ?? ''));
                if (in_array($mpost, ['war', 'fa', 'e'], true)) {
                    $db->exec("UPDATE country SET minister_$mpost = ? WHERE CountryID = ?", [(int) $p[0], $coun]);
                    $mposts = ['war' => 'war', 'fa' => 'foreign affairs', 'e' => 'economy'];
                    $db->sendNote((int) $p[0], '', "You have been approved to rule your country's ministry of {$mposts[$mpost]}, congratulations! "
                        .'You can view the congress votes <a href="'.$this->url->getURL('law', $law['lawID']).'">here</a>');
                }
                break;
            case 'DeclareWar':
                $counID = (int) $p[1];
                if (!$this->war->haveWar($coun, $counID) && !$this->war->isAlly($coun, $counID)) {
                    $db->addEvent('dec', $coun, $counID, $law['lawID']);
                    $this->war->declareWar($coun, $counID);
                }
                break;
            case 'ProposePeace':
                [$price, , $cID, $supLaw] = array_pad($p, 4, null);
                if ($supLaw) {
                    $sup = $this->politics->getLaw($supLaw);
                    if ($sup && $sup['Status'] == 1) {
                        $db->addMoney(1, (float) $price, $coun, 'country', 1, "> Law {$law['lawID']} rejected <");
                        $this->war->endWar($cID, $coun);
                    } else {
                        $db->addMoney(1, (float) $price, $cID, 'country', 1, "> Law $supLaw approved <");
                    }
                }
                break;
            case 'BuyClinic':
                $db->addMoney($coun, (float) $p[1], (int) $p[2], 'company', 1, $tag);
                $db->exec('UPDATE region SET Clinic = Clinic + ? WHERE RegionID = ?', [((int) $p[0]) * 50000, (int) $p[4]]);
                break;
            case 'BuyMunic':
                $db->addMoney($db->getCurrencyID($coun), (float) $p[1], (int) $p[2], 'company', 1, $tag);
                $db->exec('UPDATE region SET Munic = ? WHERE RegionID = ?', [(int) $p[0], (int) $p[4]]);
                break;
            case 'Warca':
                $citID = (int) $p[0];
                $db->exec('UPDATE country SET nca_war = ? WHERE CountryID = ?', [$citID, $coun]);
                $mw = $db->row('SELECT citizens.* FROM country JOIN citizens ON country.minister_war = citizens.CitizenID WHERE CountryID = ?', [$coun]);
                $pass = $this->session->generateRandStr(8);
                $db->exec("UPDATE citizens SET active = 1, password = ?, accType = 'nca', accOwner = ?, secu_question = 'This is a national CA and you cannot recover password.', secu_answer = ? WHERE CitizenID = ?",
                    [Hash::make($pass), $coun, (string) random_int(0, 1000), $citID]);
                if ($mw) {
                    $db->sendPM(1, $mw['CitizenID'], 'War NCA login details', "<b>NCA name:</b> {$p[1]}<br><b>Password:</b> $pass<br><br>Please store this information!");
                }
                break;
            case 'Inds':
                $inds = [];
                for ($i = 0; $i < 3; $i++) {
                    $ind = (int) ($p[$i] ?? 0);
                    $inds[$i] = ($ind > 0 && $ind < 11) ? $ind : 0;
                }
                $db->exec('UPDATE country SET impind1 = ?, impind2 = ?, impind3 = ? WHERE CountryID = ?', [$inds[0], $inds[1], $inds[2], $coun]);
                break;
        }
    }
}
