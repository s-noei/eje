<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/** lens/include/trans/* — transaction tracker with refund. id is "{id}" or "{id}-{curID}". */
class TransController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null, ?int $page = 1)
    {
        if (!$this->logged() || !$this->mod['at_transactions']) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        if ($request->filled('trackid')) {
            $tt = in_array($request->input('tracktype'), ['citizen', 'company', 'country', 'party'], true) ? $request->input('tracktype') : 'citizen';
            $tid = (int) $request->input('trackid');
            $cur = (int) $request->input('trackcur');

            return redirect($cur ? self::url('trans', $tt, "$tid-$cur", 1) : self::url('trans', $tt, $tid));
        }
        $type = in_array($type, ['citizen', 'company', 'country', 'party'], true) ? $type : 'citizen';
        [$id, $id2] = array_pad(explode('-', (string) $id), 2, 0);
        $id = (int) $id;
        $id2 = (int) $id2;
        $level = (int) $this->mod['at_transactions'];
        $page = max(1, (int) $page);
        $count = 20;
        $start = ($page - 1) * $count;
        $data = ['type' => $type, 'id' => $id, 'id2' => $id2, 'page' => $page, 'start' => $start, 'count' => $count, 'level' => $level,
            'currencies' => $db->rows('SELECT CountryID, curName, IF(CountryID = 1, 1, 0) AS aFlag FROM country ORDER BY aFlag DESC, curName'), 'rows' => [], 'hasNext' => false];

        if ($id) {
            if ($request->input('subrefund') && $level >= 3) {
                $trans = $db->row('SELECT * FROM transactions WHERE tID = ?', [(int) $request->input('refID')]);
                if ($trans && $trans['Amount'] && !$trans['refundBy']) {
                    $note = "> Refund #{$trans['tID']} by ID #{$this->mod['ModID']} <";
                    if ($trans['fromType'] !== 'N/A' && $trans['fromType'] !== '') {
                        $db->transferMoney($trans['curID'], $trans['Amount'], $trans['toID'], $trans['toType'], $trans['fromID'], $trans['fromType'], 1, $note, 0);
                    } else {
                        $db->addMoney($trans['curID'], -$trans['Amount'], $trans['toID'], $trans['toType'], 1, $note);
                    }
                    $db->exec('UPDATE transactions SET refundBy = ? WHERE tID = ?', [$this->mod['ModID'], $trans['tID']]);
                }

                return redirect($request->getRequestUri());
            }
            $sql = 'SELECT transactions.*, country.curName FROM transactions JOIN country ON country.CountryID = transactions.curID
                WHERE ((fromID = ? AND fromType = ?) OR (toID = ? AND toType = ?)) AND (Amount != 0)';
            $b = [$id, $type, $id, $type];
            if ($id2) {
                $sql .= ' AND transactions.curID = ?';
                $b[] = $id2;
            }
            $rows = $db->rows($sql." ORDER BY transactions.timestamp DESC LIMIT $start, ".($count + 1), $b);
            $data['hasNext'] = count($rows) > $count;
            $rows = array_slice($rows, 0, $count);
            foreach ($rows as &$t) {
                $t['fromCell'] = $this->party($t['fromType'], $t['fromID'], $id, $type, $id2);
                $t['toCell'] = $this->party($t['toType'], $t['toID'], $id, $type, $id2);
            }
            $data['rows'] = $rows;
            $data['link'] = match ($type) {
                'company' => $this->vars->getURL('company', $id),
                'country' => $this->vars->getURL('country', $id),
                'party' => $this->vars->getURL('party', $id),
                default => $this->vars->getURL('profile', $id),
            };
        }

        return $this->lensPage('lens.trans', $data, 'Transactions center', 'trans');
    }

    private function party(string $pType, $pID, int $id, string $type, int $id2): string
    {
        $db = $this->database;
        $nourl = false;
        switch ($pType) {
            case '':
            case 'N/A':
                $txt = '<i>GAME</i>';
                $nourl = true;
                break;
            case 'citizen':
                $txt = e($db->getCitizenName($pID) ?: '') ?: '<i>No Name</i>';
                break;
            case 'company':
                $comp = $db->getCompany($pID);
                $txt = !empty($comp['Name']) ? e($comp['Name']) : '<i>No Name</i>';
                break;
            case 'country':
                $txt = e((string) $db->getCountryC($pID));
                break;
            case 'party':
                $txt = e("$pID ($pType)");
                break;
            default:
                $txt = e("$pID ($pType)");
                $nourl = true;
        }
        if ($pID == $id && $pType === $type) {
            $nourl = true;
        }
        if ($nourl) {
            return $txt;
        }

        return '<a href="'.self::url('trans', $pType, $id2 ? "$pID-$id2" : $pID, 1).'">'.$txt.'</a>';
    }
}
