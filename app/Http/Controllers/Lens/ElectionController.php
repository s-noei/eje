<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/** lens/include/election/* (track ids are "eID_countryID_candidateID"). */
class ElectionController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_elections'] || $this->level() <= self::ACCESS_LOCAL) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        if ($type === 'actions') {
            return redirect(self::url('citizen', 'actions', $id ?: ''));
        }
        if ($type === 'track') {
            [$eid, $counid, $citid] = array_pad(explode('_', (string) $id), 3, 0);
            $eid = (int) $eid;
            $counid = (int) $counid;
            $citid = (int) $citid;
            $eInfo = $eid ? $db->row('SELECT * FROM elections_all WHERE eID = ?', [$eid]) : null;
            $data = ['eid' => $eid, 'counid' => $counid, 'citid' => $citid, 'eInfo' => $eInfo];
            if (!$counid) {
                $data['countries'] = $eid ? $db->rows('SELECT CountryID, cName, Flag FROM country WHERE CountryID > 1 ORDER BY cName') : [];
                $view = 'track-couns';
            } elseif (!$citid) {
                $data['cands'] = [];
                if ($eInfo && $eInfo['eType'] === 'CP') {
                    $data['cands'] = $db->rows("SELECT elections_cp_elections.*, party.*, citizens.name, citizens.Avatar, COUNT(elections_cp_votes.CandidateID) AS TotalVotes
                        FROM elections_all JOIN elections_cp_elections ON elections_all.eID = elections_cp_elections.ElectionID
                        LEFT JOIN citizens ON citizens.CitizenID = elections_cp_elections.CandidateID
                        LEFT JOIN party ON party.pID = elections_cp_elections.PartyID
                        LEFT JOIN elections_cp_votes ON (elections_cp_elections.CandidateID = elections_cp_votes.CandidateID) AND (elections_cp_elections.ElectionID = elections_cp_votes.ElectionID)
                        WHERE elections_all.eID = ? AND elections_all.eType = 'CP' AND elections_cp_elections.CountryID = ? GROUP BY CandidateID ORDER BY TotalVotes DESC, citizens.ep DESC", [$eid, $counid]);
                }
                $view = 'track-cits';
            } else {
                $data['voters'] = [];
                if ($eInfo && $eInfo['eType'] === 'CP') {
                    $data['voters'] = $db->rows('SELECT citizens.CitizenID, citizens.name, citizens.Avatar FROM elections_cp_votes JOIN citizens ON citizens.CitizenID = elections_cp_votes.VoterID WHERE ElectionID = ? AND CandidateID = ?', [$eid, $citid]);
                }
                $view = 'track-elec';
            }

            return $this->lensPage("lens.election.$view", $data, 'Election tools', 'election');
        }

        $groups = [];
        foreach ($db->rows('SELECT * FROM elections_all GROUP BY timestamp ORDER BY timestamp DESC') as $elec) {
            $groups[] = ['ts' => $elec['timestamp'], 'items' => $db->rows('SELECT * FROM elections_all WHERE timestamp = ? GROUP BY eType ORDER BY day', [$elec['timestamp']])];
        }

        return $this->lensPage('lens.election.home', ['groups' => $groups], 'Election tools', 'election');
    }
}
