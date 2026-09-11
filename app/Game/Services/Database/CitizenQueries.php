<?php

namespace App\Game\Services\Database;

use App\Game\Support\Constants;

/**
 * Citizen account / profile / inventory / messaging queries (port of MySQLDB).
 */
trait CitizenQueries
{
    private int $numMembers = -1;

    public int $numActiveUsers = 0;

    public int $numActiveGuests = 0;

    /** 0 = ok, 1 = no such user, 2 = bad password. Accepts legacy MD5 or bcrypt. */
    public function confirmUserPass(string $username, string $plainPassword): int
    {
        $row = $this->row('SELECT password FROM citizens WHERE name = ?', [$username]);
        if (! $row) {
            return 1;
        }
        $stored = (string) $row['password'];
        if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            return hash_equals(strtolower($stored), md5($plainPassword)) ? 0 : 2;
        }

        return password_verify($plainPassword, $stored) ? 0 : 2;
    }

    public function confirmUserID(string $username, string $userid, string $userip): int
    {
        $row = $this->row('SELECT userid, userip FROM citizens WHERE name = ?', [$username]);
        if (! $row) {
            return 1;
        }
        $ipHash = md5($userip.config('ejahan.salts.ip'));

        return ($userid === (string) $row['userid'] && $ipHash === (string) $row['userip']) ? 0 : 2;
    }

    public function idTaken(int|string $userid): bool
    {
        return $this->count('SELECT CitizenID FROM citizens WHERE CitizenID = ?', [$userid]) > 0;
    }

    public function checkRegion(int|string $regionid): bool
    {
        return $this->count('SELECT RegionID FROM region WHERE RegionID = ?', [$regionid]) > 0;
    }

    public function usernameTaken(string $username): bool
    {
        return $this->count('SELECT name FROM citizens WHERE name = ?', [$username]) > 0;
    }

    /** @return array{type:string,reason:string}|null */
    public function usernameBanned(int|string $citID): ?array
    {
        $row = $this->row('SELECT * FROM citizens WHERE CitizenID = ? AND ban_due >= ?', [$citID, time()]);
        if (! $row) {
            return null;
        }

        return [
            'type' => $row['ban_due'] === 'PERMANENTLY' ? '2' : '1',
            'reason' => $row['ban_reason'],
        ];
    }

    /** Death is disabled in the legacy game. */
    public function usernameDead(int|string $cID): int
    {
        return 0;
    }

    public function usernameHibernated(int|string $cID): int
    {
        $row = $this->row('SELECT wellness, dDeath, CitizenID FROM citizens WHERE CitizenID = ?', [$cID]);
        if ($row && (float) $row['wellness'] == 0) {
            if (! $row['dDeath']) {
                $this->updateUserFieldID($row['CitizenID'], 'dDeath', $this->getToday());
            }

            return 1;
        }

        return 0;
    }

    /**
     * addNewUser — registers a citizen. $avatarTmpPath is an uploaded file path (or null).
     * Returns the new user's info array or null.
     */
    public function addNewUser(string $username, string $passwordHash, string $email, $female, $uRegionID, $uCountryID, ?string $avatarTmpPath, string $invID = '', string $uType = 'citizen', $uOwner = 0): ?array
    {
        $time = time();
        $ulevel = strcasecmp($username, config('ejahan.admin_name')) === 0 ? Constants::ADMIN_LEVEL : Constants::USER_LEVEL;
        $joined = $this->getToday();
        $avatar = 'no-avatar-'.($uType === 'co-account' ? 'c' : ($female ? 'f' : 'm')).'.jpg';

        $cID = $this->insertGetId(
            'INSERT INTO citizens (name, password, accType, accOwner, userid, nationality, userlevel, email, timestamp, female, regionID, joined, Avatar, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$username, $passwordHash, $uType, $uOwner, '0', $uCountryID, $ulevel, $email, $time, $female, $uRegionID, $joined, $avatar, '1']
        );

        if ($avatarTmpPath && is_file($avatarTmpPath)) {
            $fName = md5($cID.config('ejahan.salts.citizen_avatar')).'.jpg';
            @copy($avatarTmpPath, public_path('uploads/avatars/citizen/'.$fName));
            $this->updateUserFieldID($cID, 'Avatar', $fName);
        }

        $countID = $this->getCountryID($uRegionID);
        $cAmount = $uType === 'citizen' ? $this->getCountryFee($countID) : 0;

        $this->exec('INSERT INTO citizen_money (CitID, CurID, Amount) VALUES (?, 1, 0)', [$cID]);

        $currenID = $this->getCurrencyID($countID);

        if ($uType === 'citizen') {
            $coun = $this->row('SELECT country.* FROM region JOIN country ON region.CountryID = country.CountryID WHERE region.RegionID = ?', [$uRegionID]);
            $counID = $coun['CountryID'] ?? $countID;
            $this->transferMoney($currenID, $cAmount, $counID, 'country', $cID, 'citizen', 'Fee');
            $this->sendPM(1, $cID, 'Welcome to eJahan', (string) ($coun['welcome_message'] ?? ''));

            for ($i = 0; $i < 3; $i++) {
                $inv2ID = md5(random_int(0, PHP_INT_MAX).$username.microtime());
                $this->exec('INSERT INTO invites (inviteID, byID) VALUES (?, ?)', [$inv2ID, $cID]);
            }

            $this->exec('INSERT INTO log (`Type`, CitID, Param, `Desc`, timestamp) VALUES (?, ?, ?, ?, ?)',
                ['Register', $cID, request()->ip() ?? '', '', time()]);

            if ($invID !== '') {
                $this->exec('UPDATE invites SET toID = ?, emailID = ? WHERE inviteID = ?', [$cID, $email, $invID]);
                $note = 'The citizen <a href="'.$this->url('profile', $cID)."\">{$username}</a> has been registered with your invite."
                    ."If he/she reaches the Social Puberty Level 2, you'll receive 10 TALA as a reward!";
                $this->sendNote($invID, '', $note);
                $this->exec('UPDATE citizens SET referrer = ? WHERE CitizenID = ?', [$invID, $cID]);
                $this->exec('INSERT INTO friendship (Part1, Part2, AddedBy, Accepted) VALUES (?, ?, ?, 1), (?, ?, ?, 1)',
                    [$cID, $invID, $cID, $invID, $cID, $cID]);
            }
            if ((string) $invID !== '1') {
                $this->exec('INSERT INTO friendship (Part1, Part2, AddedBy, Accepted) VALUES (?, 1, ?, 1)', [$cID, $cID]);
            }
        }

        return $this->getUserInfo($username);
    }

    public function updateUserField(string $username, string $field, mixed $value): int
    {
        return $this->exec('UPDATE citizens SET '.$this->col($field).' = ? WHERE name = ?', [$value, $username]);
    }

    public function updateUserFieldID(int|string $userid, string $field, mixed $value): int
    {
        $n = $this->exec('UPDATE citizens SET '.$this->col($field).' = ? WHERE CitizenID = ?', [$value, $userid]);
        $ctx = $this->context();
        if ($ctx && (int) $ctx->CitID === (int) $userid) {
            $ctx->updateInfo([$field => $value]);
        }

        return $n;
    }

    public function updateUserMoney(int|string $userID, int|string $curID, float|int|string $value): void
    {
        $this->exec('UPDATE citizen_money SET Amount = ? WHERE CitID = ? AND CurID = ?', [$value, $userID, $curID]);
    }

    public function getUserInfo(string $username): ?array
    {
        return $this->row('SELECT citizens.*, IFNULL(lens_info.Access, 1) AS access, lens_info.ModID
            FROM citizens LEFT JOIN lens_info ON citizens.CitizenID = lens_info.AssignedTo
            WHERE name = ?', [$username]);
    }

    /** Full citizen record with region/country/party/company/lens joins (ext=1) or the bare row. */
    public function getUserInfoFromID(int|string $userid, $ext = '1'): ?array
    {
        if ($ext) {
            $q = 'SELECT citizens.*, lens_info.*, party_members.PartyID, region.RegionID, region.rName AS RegionName, region.Clinic, region.stat_cfactor, country.*,
                    country.flag AS CountryFlag, company_workers.CompanyID, congressmen.cgCountryID, citOwner.name AS accOwnerName, company_workers.Salary,
                    company_workers.curID AS SalaryCurID, log_consume.change AS wChange, citizens.CitizenID AS CitizenID, citizens.name AS name
                FROM (SELECT * FROM citizens WHERE CitizenID = ?) citizens
                LEFT JOIN lens_info ON citizens.CitizenID = lens_info.AssignedTo
                LEFT JOIN log_consume ON citizens.CitizenID = log_consume.citID AND log_consume.day = ?
                LEFT JOIN citizens AS citOwner ON citizens.accOwner = citOwner.CitizenID
                LEFT JOIN party_members ON citizens.CitizenID = party_members.CitizenID
                LEFT JOIN company_workers ON citizens.CitizenID = company_workers.CitizenID
                LEFT JOIN region ON citizens.regionID = region.RegionID
                LEFT JOIN country ON country.CountryID = region.CountryID
                LEFT JOIN congressmen ON citizens.CitizenID = congressmen.cgCitizenID
                WHERE citizens.CitizenID = ?
                GROUP BY region.CountryID';
            $row = $this->row($q, [$userid, $this->today, $userid]);
        } else {
            $row = $this->row('SELECT * FROM citizens WHERE CitizenID = ?', [$userid]);
        }
        if (! $row) {
            return null;
        }
        if ($ext) {
            $row['access'] = $row['Access'] ?? 1;
            if ($row['access'] === null) {
                $row['access'] = 1;
            }
            $gd = $this->row("SELECT SUM(IF(goddessType = '1', 1, 0)) AS gd_darkness,
                    SUM(IF(goddessType = '2', 1, 0)) AS gd_war,
                    SUM(IF(goddessType = '3', 1, 0)) AS gd_work,
                    SUM(IF(goddessType = '4', 1, 0)) AS gd_life,
                    SUM(IF(goddessType = '5', 1, 0)) AS gd_chance,
                    SUM(IF(goddessType = '6', 1, 0)) AS gd_love
                FROM region WHERE CountryID = ?", [$row['CountryID'] ?? 0]);
            foreach (($gd ?? []) as $k => $v) {
                $row[$k] = (int) $v;
            }
        }

        return $row;
    }

    public function getNumMembers(): int
    {
        if ($this->numMembers < 0) {
            $this->numMembers = (int) $this->value('SELECT SUM(pop) AS Tpop FROM stat_country', [], 0);
        }

        return $this->numMembers;
    }

    public function getAvgEP(): int
    {
        return (int) $this->value('SELECT ROUND(AVG(ep)) AS AvgEP FROM citizens WHERE wellness > 0', [], 0);
    }

    public function calcNumActiveUsers(): void
    {
        $this->numActiveUsers = $this->count('SELECT username FROM active_users');
    }

    public function calcNumActiveGuests(): void
    {
        $this->numActiveGuests = $this->count('SELECT ip FROM active_guests');
    }

    public function addActiveUser(string $username, int $time): void
    {
        $this->exec('UPDATE citizens SET timestamp = ? WHERE name = ?', [$time, $username]);
        if (! config('ejahan.track_visitors')) {
            return;
        }
        $this->exec('REPLACE INTO active_users VALUES (?, ?)', [$username, $time]);
        $this->calcNumActiveUsers();
    }

    public function addActiveGuest(string $ip, int $time): void
    {
        if (! config('ejahan.track_visitors')) {
            return;
        }
        $this->exec('REPLACE INTO active_guests VALUES (?, ?)', [substr($ip, 0, 15), $time]);
        $this->calcNumActiveGuests();
    }

    public function removeActiveUser(string $username): void
    {
        $this->exec('DELETE FROM active_users WHERE username = ?', [$username]);
        $this->calcNumActiveUsers();
    }

    public function removeActiveGuest(string $ip): void
    {
        $this->exec('DELETE FROM active_guests WHERE ip = ?', [substr($ip, 0, 15)]);
        $this->calcNumActiveGuests();
    }

    public function removeInactiveUsers(): void
    {
        $this->exec('DELETE FROM active_users WHERE timestamp < ?', [time() - config('ejahan.user_timeout') * 60]);
        $this->calcNumActiveUsers();
    }

    public function removeInactiveGuests(): void
    {
        $this->exec('DELETE FROM active_guests WHERE timestamp < ?', [time() - config('ejahan.guest_timeout') * 60]);
        $this->calcNumActiveGuests();
    }

    /* ------------------------------------------------------------------ */
    /*  Experience, puberty, chanceboxes, diaries, medals                   */
    /* ------------------------------------------------------------------ */

    public function addEP(int|string $citID, int|float $amount, string $why = 'Unknown/Unprogrammed'): void
    {
        $cit = $this->getUserInfoFromID($citID);
        if (! $cit) {
            return;
        }
        $ep = $cit['ep'] + $amount;
        $this->updateUserFieldID($citID, 'ep', $ep);
        if ($ep >= 250 && ($ep - $amount) < 250) {
            $this->payments()->extendAcc($citID, 'pro', 1 / 3);
            $this->sendNote($citID, '', 'Your citizen account is upgraded to PRO account for 10 days FOR FREE because you activated your account!');
        }
        $this->exec('INSERT INTO log_ep_gains (citID, Amount, Why, timestamp) VALUES (?, ?, ?, ?)', [$citID, $amount, substr($why, 0, 25), time()]);

        if ($cit['accType'] !== 'citizen') {
            return;
        }
        for ($i = 0; $i < 7; $i++) {
            $cit = $this->getUserInfoFromID($citID);
            $ep = $cit['ep'];
            $pub = (int) $cit['puberty'];
            $pubep = Constants::PUB_EPS[$pub + 1] ?? null;
            if ($pubep !== null && $pubep <= $ep && $pubep) {
                $pub++;
                $pubtitle = Constants::PUB_RANKS[$pub];
                $pubreward = Constants::PUB_REWARDS[$pub] * 10;
                $pubNextTitle = Constants::PUB_RANKS[$pub + 1] ?? null;
                $pubNextEP = Constants::PUB_EPS[$pub + 1] ?? null;
                $this->updateUserFieldID($citID, 'puberty', $pub);
                $note = "Congratulations! You've passed a puberty level! Your new level is {$pubtitle}. Check your unlocked features and rewards "
                    .'<a href="'.config('ejahan.wiki_url').'/index.php/Puberty">here</a>!';
                if ($pubreward) {
                    $this->addCB($citID, 'passlevel', $pubreward);
                    $note .= " In addition, you received a chancebox with {$pubreward} points as a reward!";
                }
                if ($pubNextTitle) {
                    $note .= " To pass this level and goto {$pubNextTitle}, you need {$pubNextEP} EPs.";
                }
                $this->sendNote($citID, '', $note);

                if ($pub === 5) {
                    $this->rewardReferrers($cit, $citID);
                }
            }
        }
    }

    /** Referral rewards when a citizen reaches puberty level 5 (150 EP). */
    private function rewardReferrers(array $cit, int|string $citID): void
    {
        $r1 = $cit['referrer'];
        if (! $r1 || (int) $r1 === 1) {
            return;
        }
        $link = '<a href="'.$this->url('profile', $citID).'">'.$cit['name'].'</a>';
        $this->sendNote($r1, '', "Citizen {$link} who was registered with your L1 invite reached 150 EP and so you received 10 TALA as a reward!");
        $this->addMoney(1, 10, $r1, 'citizen', 1, '> L1 reached 150 EP <');

        $meds = (int) floor($this->count('SELECT CitizenID FROM citizens WHERE referrer = ? AND ep > 150', [$r1]) / 15);
        if ($meds && $this->addMedal($r1, 'gg', '', $meds)) {
            $this->sendNote($r1, '', 'Congratulations, you successfully invited 15 citizens who received 150 EP, and you received 5 Tala and a Golden Genealogy trophy!');
        }

        $refer = $this->getUserInfoFromID($r1);
        $r2 = $refer['referrer'] ?? 0;
        if ($r2 && (int) $r2 !== (int) $r1 && (int) $r2 !== 1) {
            $this->sendNote($r2, '', "Citizen {$link} who was registered with your L2 invite reached 150 EP and so you received 5 TALA as a reward!");
            $this->addMoney(1, 5, $r2, 'citizen', 1, '> L2 reached 150 EP <');
            $refer1 = $this->getUserInfoFromID($r2);
            $r3 = $refer1['referrer'] ?? 0;
            if ($r3 && (int) $r3 !== (int) $r2 && (int) $r3 !== (int) $r1 && (int) $r3 !== 1) {
                $this->sendNote($r3, '', "Citizen {$link} who was registered with your L3 invite reached 150 EP and so you received 2 TALA as a reward!");
                $this->addMoney(1, 2, $r3, 'citizen', 1, '> L3 reached 150 EP <');
            }
        }
    }

    public function addCB(int|string $citID, string $reason, int|float $points = 50, $getTala = 1): void
    {
        $this->exec('INSERT INTO chanceboxes (toID, reason, day, points, canGetTala) VALUES (?, ?, ?, ?, ?)',
            [$citID, substr($reason, 0, 10), $this->today, $points, (string) $getTala]);
        $this->sendNote($citID, 'cb_rcv', "{$points}|{$reason}");
    }

    public function addDiary(int|string $citID, string $type, string $param): void
    {
        $this->exec('INSERT INTO citizen_diaries (citID, dType, Day, Param) VALUES (?, ?, ?, ?)', [$citID, substr($type, 0, 7), $this->today, $param]);
    }

    public function addEvent(string $type, $c1, $c2, $ref): void
    {
        $this->exec('INSERT INTO events (Type, Country1, Country2, refID, timestamp) VALUES (?, ?, ?, ?, ?)', [$type, (int) $c1, (int) $c2, (string) $ref, time()]);
    }

    /** Adds a medal (limit = max count); returns 1 if awarded. */
    public function addMedal(int|string $citID, string $type, string $param = '', $limit = '', $prize = 5): int
    {
        $col = 'medals_'.$type;
        $med = (int) $this->value('SELECT '.$this->col($col).' FROM citizens WHERE CitizenID = ?', [$citID], 0);
        if ($med < (int) $limit || ! $limit) {
            $med++;
            $this->updateUserFieldID($citID, $col, $med);
            $this->addCB($citID, 'trophy', $prize * 10);
            $this->addDiary($citID, $type, $param);

            return 1;
        }

        return 0;
    }

    /** Occupation timer — disabled in the legacy game (always returns 1). */
    public function addOcc(int|string $citID, int|float $dur, $force = 0): int
    {
        return 1;
    }

    public function addSP(int|string $citID, $amount, $type): void
    {
        // no-op in legacy
    }

    public function delMedal(int|string $citID, string $type): void
    {
        $col = 'medals_'.$type;
        $med = (int) $this->value('SELECT '.$this->col($col).' FROM citizens WHERE CitizenID = ?', [$citID], 0);
        if ($med > 0) {
            $this->updateUserFieldID($citID, $col, $med - 1);
            $this->addMoney(1, 5, $citID, 'citizen', 1);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Mines / training                                                    */
    /* ------------------------------------------------------------------ */

    /** Consume food items of given star levels; returns wellness restored (sum of stars). */
    private function consumeFoods(int|string $citID, array $foods): int
    {
        $sum = 0;
        foreach ($foods as $stars => $qty) {
            $stars = (int) $stars;
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }
            $ids = array_column($this->rows('SELECT pID, Stars FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = 1 AND Stars = ? LIMIT '.$qty, [$citID, $stars]), 'pID');
            if (! $ids) {
                continue;
            }
            $sum += $stars * count($ids);
            $this->exec('UPDATE inventory SET Usable = 0 WHERE pID IN ('.implode(',', array_map('intval', $ids)).')');
        }

        return $sum;
    }

    public function doExplore(array $cit, int $dur, array $foods): array
    {
        $citID = $cit['CitizenID'];
        $gds = min((int) ($cit['gd_chance'] ?? 0), 10);
        $gdmin = [0.02, 0.4, 0.7, 0.9, 0.95, 1, 1.05, 1.1, 1.15, 1.2, 1.2];
        $cMin = (int) ($gdmin[$gds] * 100);

        $A = $dur;
        $B = $cit['wellness'] / 10;
        $C = random_int($cMin, 200) / 100;
        $advance = round($A * $B * $C, 2);

        $wChange = $A * 2;
        $gl = min((int) ($cit['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $wChange -= abs(round($wChange * $gdpercs[$gl]));

        if ((int) $cit['puberty'] === 0) {
            $EP = 1;
        } elseif (round($advance / 4) > 25) {
            $EP = 25;
        } else {
            $EP = (int) round($advance / 4);
        }
        if ($EP == 0) {
            $EP = 1;
        }
        $adv = $cit['mines_advance'] + $advance;
        $tried = $cit['mines_tried'] + 1;
        $don = $cit['mines_done'];
        $reward = 0;
        if ($adv > 1600) {
            $adv = round($adv - 1600, 2);
            $don++;
            $tr2 = $tried;
            $tried = max(10, min(29, $tried));
            $reward = round(random_int(20, 100) / ($tried - 9), 2);
            $msg = "<font color=\"red\">You've found {$reward} Points!</font>";
            $note = "Congratulations! You've finished an explore session and found a chance box in the mines and your action helped ".($reward / 10).' Tala '
                ."to your national country's treasury.";
            $this->addCB($citID, 'mines', (int) round($reward * 10));
            if ($tr2 < 30) {
                $note .= ' Also, you received a Lucky Miner trophy because you finished an explore session in less than 30 tries!';
                $this->addMedal($citID, 'lm');
            }
            $this->sendNote($citID, '', $note);
            $this->addMoney(1, round($reward * 0.1, 3), $cit['nationality'], 'country', 1, '> Explore mines <');
            $tried = 1;
        } else {
            $msg = 'You need '.(1600 - $adv).' extra points to find Tala!';
        }

        $this->addEP($citID, $EP, 'Explore');

        $sum = $this->consumeFoods($citID, $foods);
        if ($wChange < $sum) {
            $sum = $wChange;
        }
        $B2 = (int) round($cit['wellness'] - ($wChange - $sum));

        $this->exec('UPDATE citizens SET LastExplored = ?, mines_advance = ?, mines_tried = ?, mines_done = ?, wellness = ? WHERE CitizenID = ?',
            [$this->today, $adv, $tried, $don, $B2, $citID]);
        $this->exec('INSERT INTO log_exploring (CitizenID, Day, timestamp, chance, wellness, duration, ep, points, totpoints, received) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$citID, $this->today, time(), $C, "{$cit['wellness']}|{$wChange}|{$sum}", $A, "{$cit['ep']}|{$EP}", $advance, $cit['mines_advance'], $reward]);

        return ['Dur' => "$A", 'WInf' => "$B", 'Well' => "$B2", 'EP' => "$EP", 'Random' => "$C", 'Advance' => "$advance", 'Msg' => "$msg"];
    }

    /**
     * One training session per day: weights (+1 strength) or cardio (+1 stamina), 0..SHAPE_MAX.
     * Missed days are decayed by the daily cron. Legacy skill points keep accumulating in the
     * background (rankings, IS trophy) but no longer affect damage.
     */
    public function doTrain(array $cit, int $ttype, array $foods): array
    {
        $citID = $cit['CitizenID'];
        $ttype = $ttype === Constants::TRAIN_CARDIO ? Constants::TRAIN_CARDIO : Constants::TRAIN_WEIGHTS;
        $A2 = $this->getChangedSkill($cit, 1, 1);

        $wChange = Constants::TRAIN_WELLNESS[$ttype];
        $gl = min((int) ($cit['gd_life'] ?? 0), 9);
        $gdpercs = [0, 0.1, 0.14, 0.17, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25];
        $wChange -= abs(round($wChange * $gdpercs[$gl]));

        $sum = $this->consumeFoods($citID, $foods);
        if ($wChange < $sum) {
            $sum = $wChange;
        }
        $B2 = max(0, $cit['wellness'] - $wChange + $sum);
        $EP2 = $cit['ep'] + 1;

        $streak = ((int) $cit['LastTrained'] === $this->today - 1) ? (int) $cit['train_streak'] + 1 : 1;
        $strength = (int) ($cit['strength'] ?? 0);
        $stamina = (int) ($cit['stamina'] ?? 0);
        if ($ttype === Constants::TRAIN_WEIGHTS) {
            $strength = min(Constants::SHAPE_MAX, $strength + 1);
        } else {
            $stamina = min(Constants::SHAPE_MAX, $stamina + 1);
        }

        $this->updateUserFieldID($citID, 'LastTrained', $this->today);
        $gl = min((int) ($cit['gd_love'] ?? 0), 10);
        $gdmin = [1, 0.95, 0.9, 0.85, 0.8, 0.75, 0.7, 0.65, 0.6, 0.55, 0.5];
        $this->addOcc($citID, ($ttype === Constants::TRAIN_WEIGHTS ? 120 : 90) * $gdmin[$gl]);

        $this->addEP($citID, 1, 'Training');

        $mod1 = $cit['mSP'] % 7500;
        $mod2 = ($cit['mSP'] + $A2) % 7500;
        if ($mod2 < $mod1) {
            $this->addMedal($citID, 'is');
            $this->sendNote($citID, 'medal_is', '');
        }
        $mSP = $cit['mSP'] + $A2;
        $newSkill = (int) $cit['mSkill'];
        if (($this->spCheckpoint($newSkill + 1)) < $mSP) {
            $newSkill++;
        }
        $this->exec('UPDATE citizens SET mSP = ?, mSkill = ?, strength = ?, stamina = ?, train_streak = ?, wellness = ? WHERE CitizenID = ?',
            [$mSP, $newSkill, $strength, $stamina, $streak, round($B2), $citID]);

        $this->exec('INSERT INTO log_training (CitizenID, Day, timestamp, type, wellness, skill, ep, received) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$citID, $this->today, time(), (string) $ttype, "{$cit['wellness']}|{$wChange}|{$sum}", "{$strength}|{$stamina}|{$streak}", $cit['ep'], $ttype === Constants::TRAIN_WEIGHTS ? $strength : $stamina]);

        return ['Type' => $ttype, 'Strength' => $strength, 'Stamina' => $stamina, 'Streak' => $streak, 'Well' => "$B2", 'EP' => "$EP2"];
    }

    /** Daily cron: a missed training day costs one stage of strength and stamina and breaks the streak. */
    public function decayBodyShape(int $today): int
    {
        return $this->exec("UPDATE citizens SET strength = GREATEST(0, strength - 1), stamina = GREATEST(0, stamina - 1), train_streak = 0
            WHERE LastTrained < ? AND accType = 'citizen' AND (strength > 0 OR stamina > 0 OR train_streak > 0)", [$today - 1]);
    }

    private function spCheckpoint(int $skill): int
    {
        return Constants::SP_CPS[$skill] ?? PHP_INT_MAX;
    }

    public function getChangedSkill(array $cit, int $type, int $army = 0): int
    {
        $skill = $army ? $cit['mSkill'] : $cit['wSkill'];
        $S = $skill / 10 + 1;
        $W = 25;
        $T = ($type + 1) / 2;

        return (int) round($S * $W * $T * ($army / 5 + 1));
    }

    /* ------------------------------------------------------------------ */
    /*  Citizen lookups                                                     */
    /* ------------------------------------------------------------------ */

    public function getCitizenFreeIS(int|string $citID): int
    {
        $cit = $this->getUserInfoFromID($citID);
        $time = time();
        $max = $cit['accType'] === 'nca' ? 400 : (($cit['proExpire'] >= $time) ? 200 : (($cit['plusExpire'] >= $time) ? 100 : 40));

        return $max - $this->getCitizenInventory($citID, $max, 1);
    }

    public function getCitizenID(string $citname): int
    {
        return (int) $this->value('SELECT CitizenID FROM citizens WHERE name = ?', [$citname], 0);
    }

    /** Inbox rows, or the total count when $start = -1. */
    public function getCitizenInbox(int|string $CitID, int $start = 0, int $num = 10): array|int
    {
        $sql = "SELECT pm.*, citizens.name AS fromName, citizens.Avatar AS fromAvatar
            FROM pm LEFT JOIN citizens ON citizens.CitizenID = pm.fromID
            WHERE (toID = ?) AND (rem_inbox != '1') ORDER BY isRead, timestamp DESC";
        if ($start === -1) {
            return $this->count($sql, [$CitID]);
        }

        return $this->rows($sql.' LIMIT '.(int) $start.', '.(int) $num, [$CitID]);
    }

    /** Grouped inventory rows (Type/Stars/Amount/Icon) or the item count when $getnum. */
    public function getCitizenInventory(int|string $CitID, int $max = 50, int $getnum = 0): array|int
    {
        if ($getnum) {
            return $this->count('SELECT inventory.pID FROM inventory JOIN industry ON industry.IndustryID = inventory.Type WHERE Owner = ? AND Usable = 1', [$CitID]);
        }
        $max = (int) $max;

        return $this->rows("SELECT inventory.*, industry.Icon, COUNT(Type) Amount
            FROM (SELECT * FROM inventory WHERE Owner = ? AND Usable = 1 LIMIT {$max}) inventory
            JOIN industry ON industry.IndustryID = inventory.Type
            WHERE Owner = ? AND Usable = 1
            GROUP BY Type, Stars
            ORDER BY Stars DESC, pID DESC LIMIT {$max}", [$CitID, $CitID]);
    }

    public function getCitizenNotes(int|string $CitID, int $start = 0, int $num = 10): array|int
    {
        $sql = 'SELECT * FROM notes WHERE toID = ? ORDER BY isRead, timestamp DESC';
        if ($start === -1) {
            return $this->count($sql, [$CitID]);
        }

        return $this->rows($sql.' LIMIT '.(int) $start.', '.(int) $num, [$CitID]);
    }

    public function getCitizenParty(int|string $cit): int
    {
        return (int) $this->value('SELECT PartyID FROM party_members WHERE CitizenID = ?', [$cit], 0);
    }

    public function getCitizenRequests(array $cit): array
    {
        return $this->rows('SELECT * FROM friendship WHERE Part2 = ? AND Accepted = 0', [$cit['CitizenID']]);
    }

    public function getCitizenSent(int|string $CitID, int $start = 0, int $num = 10): array|int
    {
        $sql = "SELECT pm.*, citizens.name AS toName, citizens.Avatar AS toAvatar
            FROM pm LEFT JOIN citizens ON citizens.CitizenID = pm.toID
            WHERE (fromID = ?) AND (NOT(rem_sent = '1')) ORDER BY timestamp DESC";
        if ($start === -1) {
            return $this->count($sql, [$CitID]);
        }

        return $this->rows($sql.' LIMIT '.(int) $start.', '.(int) $num, [$CitID]);
    }

    public function getCoAccounts(int|string $citID): array
    {
        return $this->rows("SELECT * FROM citizens WHERE accType = 'co-account' AND accOwner = ?", [$citID]);
    }

    public function getNewMSGs(int|string $CitID): int
    {
        return $this->count("SELECT pmID FROM pm WHERE toID = ? AND isRead = '0'", [$CitID]);
    }

    public function getNewNotes(int|string $CitID): int
    {
        return $this->count("SELECT NoteID FROM notes WHERE toID = ? AND isRead = '0'", [$CitID]);
    }

    public function getNewReqs(int|string $citID): int
    {
        return $this->count('SELECT fID FROM friendship WHERE Part2 = ? AND Accepted = 0 AND Part1 > 0', [$citID]);
    }

    public function getPendingFriendRequests(int|string $citID): array
    {
        return $this->rows('SELECT friendship.fID, citizens.name, citizens.avatar, citizens.CitizenID
            FROM friendship JOIN citizens ON friendship.Part1 = citizens.CitizenID
            WHERE Part2 = ? AND AddedBy != ? AND Accepted = 0', [$citID, $citID]);
    }

    public function getOnlineCitizensCount(int|string $counID = ''): int
    {
        $sql = 'SELECT citizens.CitizenID, country.CountryID FROM active_users
            LEFT JOIN citizens ON active_users.username = citizens.name
            LEFT JOIN region ON citizens.RegionID = region.RegionID
            LEFT JOIN country ON region.CountryID = country.CountryID';
        if ($counID !== '' && $counID !== null) {
            return $this->count($sql.' WHERE country.CountryID = ?', [$counID]);
        }

        return $this->count($sql);
    }

    public function getCitizenAvatar(int|string $CitID, string $avaLoc): string|int
    {
        $row = $this->row('SELECT Avatar FROM citizens WHERE CitizenID = ?', [$CitID]);
        if (! $row) {
            return -1;
        }

        return $avaLoc.($row['Avatar'] === '' ? 'noavatar.gif' : $row['Avatar']);
    }

    /** Money map [CurID => Amount] or a single amount when $curID given. */
    public function getCitizenMoney(int|string $citID, int|string $curID = ''): array|float|int
    {
        $sql = 'SELECT CurID, Amount FROM citizen_money JOIN citizens ON citizens.CitizenID = citizen_money.CitID WHERE citizen_money.CitID = ?';
        $b = [$citID];
        if ($curID !== '' && $curID !== null) {
            $sql .= ' AND citizen_money.CurID = ?';
            $b[] = $curID;
            $row = $this->row($sql, $b);

            return $row ? (float) $row['Amount'] : 0;
        }
        $out = [];
        foreach ($this->rows($sql, $b) as $r) {
            $out[$r['CurID']] = (float) $r['Amount'];
        }

        return $out;
    }

    public function getCitizenMoneyID(int|string $citID, int|string $curID): int
    {
        return (int) $this->value('SELECT ID FROM citizen_money WHERE CitID = ? AND CurID = ?', [$citID, $curID], 0);
    }

    public function getCitizenName(int|string $CitID): string|int
    {
        $v = $this->value('SELECT name FROM citizens WHERE CitizenID = ?', [$CitID]);

        return $v === null ? -1 : $v;
    }

    public function getCitizenNP(int|string $CitID): int
    {
        $v = $this->value('SELECT npID FROM citizens WHERE CitizenID = ?', [$CitID]);

        return $v === null ? -1 : (int) $v;
    }

    public function getTodayBorns(int|string $counID = ''): int
    {
        return $this->bornsOn($this->getToday(), $counID);
    }

    public function getTodayBorns2(int|string $counID = ''): int
    {
        return $this->bornsOn($this->getToday() - 1, $counID);
    }

    private function bornsOn(int $day, int|string $counID): int
    {
        $sql = 'SELECT citizens.CitizenID FROM citizens LEFT JOIN region ON citizens.RegionID = region.RegionID
            LEFT JOIN country ON region.CountryID = country.CountryID WHERE citizens.joined = ?';
        $b = [$day];
        if ($counID !== '' && $counID !== null) {
            $sql .= ' AND country.CountryID = ?';
            $b[] = $counID;
        }

        return $this->count($sql, $b);
    }

    public function getTodayHibs(int|string $counID = ''): int
    {
        $sql = 'SELECT citizens.CitizenID FROM citizens LEFT JOIN region ON citizens.RegionID = region.RegionID
            LEFT JOIN country ON region.CountryID = country.CountryID WHERE citizens.dDeath = ?';
        $b = [$this->getToday()];
        if ($counID !== '' && $counID !== null) {
            $sql .= ' AND country.CountryID = ?';
            $b[] = $counID;
        }

        return $this->count($sql, $b);
    }

    public function hasFood(int|string $citID): int
    {
        return $this->count('SELECT pID FROM inventory WHERE Owner = ? AND Usable = 1', [$citID]) ? 1 : 0;
    }

    /** Search citizens (default) or companies by name. */
    public function search(string $str, string $field = 'citizen'): array
    {
        $like = '%'.strip_tags($str).'%';
        if ($field === 'company') {
            return $this->rows('SELECT company.*, region.rName AS RegionName, country.* FROM company
                LEFT JOIN region ON company.RegionID = region.RegionID
                LEFT JOIN country ON region.CountryID = country.CountryID
                WHERE Name LIKE ? ORDER BY Name', [$like]);
        }

        return $this->rows('SELECT citizens.*, region.rName AS RegionName, country.* FROM citizens
            LEFT JOIN region ON citizens.RegionID = region.RegionID
            LEFT JOIN country ON region.CountryID = country.CountryID
            WHERE name LIKE ? ORDER BY citizens.ep DESC, citizens.name', [$like]);
    }

    /* ------------------------------------------------------------------ */
    /*  Notes & private messages                                            */
    /* ------------------------------------------------------------------ */

    public function sendNote(int|string $to, string $type, string $body): void
    {
        if ($type === '') {
            $type = '0';
        }
        $this->exec('INSERT INTO notes (toID, Type, Body, timestamp) VALUES (?, ?, ?, ?)', [$to, substr($type, 0, 10), $body, time()]);
    }

    public function sendPM(int|string $from, int|string $to, string $subject, string $body): int
    {
        if ($from === '' || $to === '' || $body === '') {
            return 0;
        }
        if ($subject === '') {
            $subject = 'no subject';
        }
        $this->exec('INSERT INTO pm (fromID, toID, Subject, Body, timestamp) VALUES (?, ?, ?, ?, ?)', [$from, $to, mb_substr($subject, 0, 40), $body, time()]);

        return 1;
    }

    public function getNote(int|string $noteID): ?array
    {
        return $this->row('SELECT * FROM notes WHERE NoteID = ?', [$noteID]);
    }

    public function getPM(int|string $pmID): ?array
    {
        return $this->row('SELECT pm.*, fromm.name AS fromName, fromm.Avatar AS fromAvatar, too.name AS toName, too.Avatar AS toAvatar
            FROM pm JOIN citizens AS fromm ON fromm.CitizenID = pm.fromID
            JOIN citizens AS too ON too.CitizenID = pm.toID WHERE pmID = ?', [$pmID]);
    }

    public function deleteNote(int|string $noteID, int|string $citID): int
    {
        $note = $this->getNote($noteID);
        if (! $note || (int) $note['toID'] !== (int) $citID) {
            abort(403, 'Spy?!!');
        }

        return $this->exec('DELETE FROM notes WHERE NoteID = ?', [$noteID]) ? 1 : 0;
    }

    public function deletePM(int|string $pmID, int|string $citID, string $where = 'inbox'): int
    {
        $pm = $this->getPM($pmID);
        if (! $pm || ! in_array($where, ['inbox', 'sent'], true)) {
            abort(403, 'Spy?!!');
        }
        $owner = $where === 'inbox' ? $pm['toID'] : $pm['fromID'];
        if ((int) $owner !== (int) $citID) {
            abort(403, 'Spy?!!');
        }

        return $this->exec('UPDATE pm SET '.$this->col('rem_'.$where)." = '1', isRead = '1' WHERE pmID = ?", [$pmID]) ? 1 : 0;
    }

    public function setReadNote(int|string $NoteID): void
    {
        $this->exec("UPDATE notes SET isRead = '1' WHERE NoteID = ?", [$NoteID]);
    }

    public function setReadPM(int|string $pmID): void
    {
        $this->exec("UPDATE pm SET isRead = '1' WHERE pmID = ?", [$pmID]);
    }

    /* ------------------------------------------------------------------ */
    /*  Invites                                                             */
    /* ------------------------------------------------------------------ */

    public function checkInvite(string $id): int
    {
        return $this->count("SELECT inviteID FROM invites WHERE inviteID = ? AND toID = '0' AND emailID != ''", [$id]) ? 1 : 0;
    }

    public function getInvite(string $id): ?array
    {
        return $this->row("SELECT invites.*, citizens.name FROM invites JOIN citizens ON invites.byID = citizens.CitizenID
            WHERE invites.inviteID = ? AND (invites.toID = '' OR invites.toID = 0)", [$id]);
    }
}
