<?php

namespace App\Http\Controllers;

use App\Game\Services\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Port of userinfo.php + include/profile/*.
 * URL: profile-{id}[-{go}[-{id2}]]-{lang}.html
 */
class ProfileController extends GameController
{
    public function show(Request $request, ?string $id = null, ?string $go = null, ?string $id2 = null)
    {
        $this->lang->addPhrases('profile');
        $go = $go ?: 'index';
        if (in_array($go, ['edit', 'move', 'activate', 'personalize'], true) && ! $id) {
            $id = $this->citID();
        }
        if (! $id || ! $this->database->idTaken($id)) {
            return redirect('/index.html');
        }
        $row = $this->database->getUserInfoFromID((int) $id, 2);
        if (! $row) {
            return redirect('/index.html');
        }
        $acc = $this->accesses();
        $view_own = $this->loggedIn() && (int) $row['CitizenID'] === $this->citID();
        $is_ca = in_array($row['accType'], ['co-account', 'nca'], true);
        $is_nca = $row['accType'] === 'nca';

        /* Forfeit system (moderators) */
        if ($request->isMethod('post') && $acc['can_ban_citizens']) {
            if ($request->input('subdelbanned')) {
                $this->mod()->unbanCitizen($row['CitizenID']);

                return redirect($request->getRequestUri());
            }
            if ($request->input('addviolation')) {
                $this->handleViolation($request, $row);

                return redirect($request->getRequestUri());
            }
        }
        if ($request->isMethod('post') && $request->input('addmodcm') && $acc['can_view_citizen_comments'] && $request->input('modcm')) {
            $this->mod()->addModCM('citizen', $row['CitizenID'], (string) $request->input('modcm'));

            return redirect($request->getRequestUri());
        }

        $hasPro = $row['proExpire'] >= time();
        $hasPlus = $row['plusExpire'] >= time();
        $bandata = $this->database->usernameBanned($row['CitizenID']) ?? ['type' => 0, 'reason' => ''];
        $modcms = $acc['can_view_citizen_comments']
            ? $this->database->rows("SELECT mod_comments.*, citizens.name AS ByName FROM mod_comments JOIN citizens ON citizens.CitizenID = mod_comments.By
                WHERE Type = 'citizen' AND TypeID = ? ORDER BY mod_comments.timestamp DESC, mod_comments.ID DESC", [$row['CitizenID']])
            : [];

        $common = [
            'row' => $row, 'req_id' => (int) $id, 'go' => $go, 'id2' => $id2,
            'view_own' => $view_own, 'is_ca' => $is_ca, 'is_nca' => $is_nca,
            'hasPro' => $hasPro, 'hasPlus' => $hasPlus, 'bandata' => $bandata,
            'online' => $this->database->count('SELECT username FROM active_users WHERE username = ?', [$row['name']]) > 0,
            'vpts' => (($view_own || $acc['can_ban_citizens']) && ! $is_nca) ? $this->mod()->getVioPoints($row['CitizenID']) : null,
            'modcms' => $modcms,
            'isFriend' => $this->loggedIn() ? $this->friendship()->isFriends($row['CitizenID'], $this->citID()) : 0,
        ];
        $layout = [
            'title' => sprintf($this->lang->getstr('title_profile', 'title'), $row['name']),
            'bar_title' => $view_own ? $this->lang->getstr('profile_title_my', 'profile') : $this->lang->getstr('profile_title_user', 'profile'),
            'actiontype' => 'profile',
        ];

        $sub = $this->subPage($request, $go, $row, $id2, $common, $view_own, $is_ca, $is_nca, $hasPro, $hasPlus, $acc);
        if ($sub instanceof \Symfony\Component\HttpFoundation\Response) {
            return $sub;
        }
        if (isset($sub['redirect'])) {
            return redirect($sub['redirect']);
        }

        return $this->page('pages.profile.show', $common + $sub, $layout);
    }

    private function handleViolation(Request $request, array $row): void
    {
        $citID = $row['CitizenID'];
        $byID = $this->citID();
        switch ($request->input('ftype')) {
            case 'predef':
                $viol = $this->database->row('SELECT * FROM forfeit_types WHERE forfeitID = ?', [(int) $request->input('predef')]);
                if ($viol && $request->input('predesc')) {
                    $this->mod()->addViolation($viol['forfeitID'], $citID, $byID, $viol['Title'], (string) $request->input('predesc'), (int) $viol['Points']);
                }
                break;
            case 'custom':
                if ($request->input('vtitle') && $request->input('vpoints') && $request->input('vdesc')) {
                    $this->mod()->addViolation(0, $citID, $byID, (string) $request->input('vtitle'), (string) $request->input('vdesc'), (int) $request->input('vpoints'));
                }
                break;
            case 'arrest':
                $reason = $request->input('jailreason') !== 'other' ? (string) $request->input('jailreason') : (string) $request->input('jaildesc');
                $this->mod()->banCitizen($citID, (int) $request->input('jaildur'), $reason);
                break;
            case 'ban':
                $reason = $request->input('banreason') !== 'other' ? (string) $request->input('banreason') : (string) $request->input('bandesc');
                $this->mod()->banCitizen($citID, (int) $request->input('banduration', 0), $reason);
                break;
        }
    }

    /** Returns extra view data for the sub page, or a redirect response. */
    private function subPage(Request $request, string $go, array $row, ?string $id2, array $common, bool $view_own, bool $is_ca, bool $is_nca, bool $hasPro, bool $hasPlus, array $acc): array|\Symfony\Component\HttpFoundation\Response
    {
        $cit = $this->citInfo;
        $citID = $this->citID();
        $me = $this->loggedIn();
        $isCA = $this->isCA();

        switch ($go) {
            case 'accept':
            case 'reject':
                if (! $me || $is_ca || ! $row['active'] || $isCA || ! $id2) {
                    return redirect('/index.html');
                }
                if ($go === 'accept') {
                    $this->friendship()->acceptFriend($citID, (int) $id2);
                } else {
                    $this->friendship()->rejectFriend($citID, (int) $id2);
                }

                return redirect($this->vars->getURL('profile', $citID));

            case 'add':
                if (! $me || $isCA || $is_ca || ! $row['active'] || ! ($cit['active'] ?? 0)) {
                    return redirect('/index.html');
                }
                $exists = $this->database->count('SELECT fID FROM friendship WHERE (Part1 = ? AND Part2 = ?) OR (Part2 = ? AND Part1 = ?)', [$citID, $row['CitizenID'], $citID, $row['CitizenID']]);
                if ($exists) {
                    return redirect($this->vars->getURL('profile', $row['CitizenID']));
                }
                $this->friendship()->addFriend($citID, $row['CitizenID']);

                return ['sub' => 'add', 'subtitle' => $this->lang->getstr('profile_friend_add_req', 'profile'), 'subhead' => $this->lang->getstr('profile_status', 'profile')];

            case 'remove':
            case 'friends':
                if ($is_ca || ! $row['active']) {
                    return redirect('/index.html');
                }
                if ($go === 'remove' && $me && $id2 && $view_own) {
                    $this->friendship()->removeFriend($citID, (int) $id2);

                    return redirect($this->vars->getURL('profile', $citID, 'friends'));
                }

                return ['sub' => 'friends', 'subtitle' => $this->lang->getstr('profile_friend_all', 'profile'), 'subhead' => $this->lang->getstr('profile_friend_all', 'profile'),
                    'friends' => $this->friendship()->getFriends($row['CitizenID'])];

            case 'lensinvite':
                if (! ($acc['can_define_mods'] && ! $is_ca && ! $row['ModID'])) {
                    return redirect($this->vars->getURL('home'));
                }
                $sent = false;
                if ($request->input('subinvite')) {
                    $access = (int) $request->input('access') + 3;
                    if ($access < 4 || $access > 6) {
                        return redirect($request->getRequestUri());
                    }
                    $accs = ['', 'Normal user', 'Forum moderator', 'Forum Administrator', 'Technical moderator', 'Local moderator', 'Police', 'Super moderator', 'Guard', 'Administrator'];
                    $this->database->exec('INSERT INTO lens_registration (citID, invID, invBy, access, country) VALUES (?, ?, ?, ?, ?)',
                        [$row['CitizenID'], md5($row['CitizenID'].'Lens Access'), $cit['ModID'], $access, $row['CountryID']]);
                    $regLink = $this->vars->getURL('profile', $row['CitizenID'], 'lensreg');
                    $msg = "Moderator {$cit['ModID']} sent an invite for you to register in eJahan Lens as a {$accs[$access]}. You can register in Lens via this link: "
                        ."<a href=\"{$regLink}\">{$regLink}</a><br>You will advance to higher acess if you show your trustworthy to administration team."
                        .'<br><br>Please keep this message as a secret!<br><br>eJahan Administration team';
                    $this->database->sendPM(1, $row['CitizenID'], 'Invited for moderation', $msg);
                    $sent = true;
                }

                return ['sub' => 'lensinvite', 'subtitle' => 'Invite to Lens', 'subhead' => 'Invite to Lens', 'sent' => $sent];

            case 'lensreg':
                $citreg = $this->database->row('SELECT * FROM lens_registration WHERE citID = ?', [$citID]);
                if (! $citreg) {
                    return redirect($this->vars->getURL('home'));
                }
                $err = null;
                if ($request->input('sublensreg')) {
                    $proof = $request->file('proof');
                    $f = ['fName', 'lName', 'year', 'month', 'day'];
                    $vals = array_map(fn ($k) => (string) $request->input($k, ''), array_combine($f, $f));
                    if (in_array('', $vals, true) || ! $proof) {
                        $err = 'All fields are required.';
                    } elseif (! ($proof->getMimeType() === 'image/jpeg' && $proof->getSize() <= 1024 * 1024)) {
                        $err = 'Image error.<br>Restrictions: JPG image with lower than 1MB of size';
                    } else {
                        $filName = md5($citID.'LeNsReG').'.jpg';
                        $proof->move(public_path('uploads/proofs/lensreg'), $filName);
                        $this->database->exec('UPDATE lens_registration SET fName = ?, lName = ?, language = ?, birth_year = ?, birth_month = ?, birth_day = ?, proof = ?, status = 1 WHERE citID = ?',
                            [$vals['fName'], $vals['lName'], (string) $request->input('lang', ''), (int) $vals['year'], (int) $vals['month'], (int) $vals['day'], $filName, $citID]);

                        return redirect($request->getRequestUri());
                    }
                }

                return ['sub' => 'lensreg', 'subtitle' => 'Lens', 'subhead' => 'Registration in Lens', 'citreg' => $citreg, 'err' => $err];

            case 'coaccounts':
                if ($is_ca || ! $view_own) {
                    return redirect('/index.html');
                }
                $msg = null;
                if ($request->input('subca')) {
                    $user = (string) $request->input('user', '');
                    $pass1 = (string) $request->input('pass1', '');
                    $pass2 = (string) $request->input('pass2', '');
                    $citMon = $this->database->getCitizenMoney($citID, 1);
                    if (! $pass1 || ! $pass2 || ! $user) {
                        $msg = '<h3 class=errHandle>'.$this->lang->getstr('err_fill_all', 'msgs').'</h3>';
                    } elseif ($pass1 !== $pass2) {
                        $msg = '<h3 class=errHandle>'.$this->lang->getstr('err_pass_not_match', 'msgs').'</h3>';
                    } elseif ($this->database->usernameTaken($user)) {
                        $msg = '<h3 class=errHandle>'.$this->lang->getstr('err_username_taken', 'msgs').'</h3>';
                    } elseif ($citMon < 5) {
                        $msg = '<h3 class=errHandle>'.$this->lang->getstr('err_tala_not_enough', 'msgs').'</h3>';
                    } elseif (strlen($user) > 30) {
                        $msg = '<h3 class=errHandle>'.$this->lang->getstr('err_username_long', 'msgs').'</h3>';
                    } else {
                        $r = $this->database->addNewUser($user, Hash::make($pass1), $cit['email'], $cit['female'], $cit['RegionID'], 0, null, '', 'co-account', $citID);
                        if ($r) {
                            $this->database->updateUserField($user, 'active', '1');
                            $this->database->updateUserField($user, 'accType', 'co-account');
                            $this->database->transferMoney(1, 5, $citID, 'citizen', '', '', 1);
                            $msg = '<h3 class="infHandle">'.$this->lang->getstr('success_ca_made', 'msgs').'</h3>';
                        } else {
                            $msg = '<h3 class=errHandle>'.$this->lang->getstr('err_unknown', 'msgs').'</h3>';
                        }
                    }
                }

                return ['sub' => 'coaccs', 'subtitle' => $this->lang->getstr('profile_cas', 'profile'), 'subhead' => $this->lang->getstr('profile_cas', 'profile'),
                    'msg' => $msg, 'coaccs' => $this->database->getCoAccounts($row['CitizenID'])];

            case 'edit':
                if (! $view_own) {
                    return redirect('/index.html');
                }

                return $this->editPage($request, $id2);

            case 'personalize':
                if (! $view_own || $this->isNCA()) {
                    return redirect('/index.html');
                }
                if ($request->input('subpers')) {
                    $this->personalize($request);

                    return redirect($request->getRequestUri());
                }

                return ['sub' => 'personalize', 'subtitle' => $this->lang->getstr('profile_personalize', 'profile'),
                    'subhead' => $this->lang->getstr('profile_personalize', 'profile').' <sup style="color: red"><blink>BETA</blink></sup>'];

            case 'move':
                if (! $view_own || $this->isNCA()) {
                    return redirect('/index.html');
                }

                return $this->travel($request);

            case 'activate':
                if (! $view_own || $is_ca) {
                    return redirect('/index.html');
                }
                $sentTo = null;
                if ($request->input('subsend') && ! $cit['active']) {
                    $email = (string) $request->input('email', '');
                    if ($email !== $cit['email'] && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->database->updateUserFieldID($citID, 'email', $email);
                    }
                    app(Mailer::class)->sendActivation($cit['name'], $email, $cit['actLink']);
                    $sentTo = $email;
                }

                return ['sub' => 'activate', 'subtitle' => $this->lang->getstr('profile_activate', 'profile'), 'subhead' => $this->lang->getstr('profile_activate', 'profile'), 'sentTo' => $sentTo];

            case 'nationality':
                if (! $view_own || $isCA) {
                    return redirect($this->vars->getURL('home'));
                }

                return $this->nationality($request);

            case 'diary':
                if ($is_ca) {
                    return redirect('/index.html');
                }

                return ['sub' => 'diary', 'subtitle' => $this->lang->getstr('profile_diary', 'profile'), 'subhead' => $this->lang->getstr('profile_diary_title', 'profile'),
                    'diaries' => $this->database->rows('SELECT * FROM citizen_diaries WHERE citID = ? ORDER BY Day DESC', [$row['CitizenID']])];

            case 'donate':
                if (! $me || $view_own) {
                    return redirect('/index.html');
                }

                return $this->donate($request, $row, $is_nca, $hasPro, $hasPlus);

            case 'donations':
                $pageNo = max(1, (int) ($id2 ?: 1));
                $start = ($pageNo - 1) * 20;
                $where = "(FromID = ? AND FromType = 'citizen') OR (ToID = ? AND ToType = 'citizen')";
                $size = $this->database->count("SELECT logID FROM log_donations WHERE {$where}", [$row['CitizenID'], $row['CitizenID']]);
                $donations = $this->database->rows("SELECT * FROM log_donations WHERE {$where} ORDER BY timestamp DESC LIMIT {$start}, 20", [$row['CitizenID'], $row['CitizenID']]);

                return ['sub' => 'donations', 'subtitle' => $this->lang->getstr('profile_donations', 'profile'), 'subhead' => $this->lang->getstr('profile_donations', 'profile'),
                    'donations' => $donations, 'pageNo' => $pageNo, 'start' => $start, 'size' => $size];

            case 'consume':
                if (! $view_own) {
                    return redirect('/index.html');
                }

                return $this->consume($request, 'food');

            case 'healthkit':
                if (! $view_own) {
                    return redirect('/index.html');
                }

                return $this->consume($request, 'healthkit');

            case 'violations':
                if ($acc['can_view_appeals'] || $view_own) {
                    return ['sub' => 'violations', 'subtitle' => '', 'subhead' => ''];
                }
                // fall through to index
            default:
                return $this->indexPage($row, $is_ca, $is_nca, $hasPro, $hasPlus, $acc);
        }
    }

    private function indexPage(array $row, bool $is_ca, bool $is_nca, bool $hasPro, bool $hasPlus, array $acc): array
    {
        $cit = $this->citInfo;
        $own = $this->loggedIn() && (int) $row['CitizenID'] === $this->citID();
        $showData = $own || $acc['can_view_private_data'] || $is_ca || $is_nca;
        $max = $is_nca ? 400 : ($hasPro ? 200 : ($hasPlus ? 100 : 40));
        $data = [
            'sub' => 'index', 'showData' => $showData,
            'money' => $this->database->rows('SELECT citizen_money.Amount, citizen_money.CurID FROM citizen_money JOIN citizens ON citizen_money.CitID = citizens.CitizenID WHERE citizen_money.CitID = ?', [$row['CitizenID']]),
            'canSeeInv' => $own || ((int) ($row['accOwner'] ?? 0) === $this->citID()) || $acc['can_view_private_data'],
            'max' => $max,
            'inventory' => $this->database->getCitizenInventory($row['CitizenID'], $max),
            'invCount' => $this->database->getCitizenInventory($row['CitizenID'], $max, 1),
            'np' => $row['npID'] ? $this->database->getNP($row['npID']) : null,
            'wComp' => null, 'managing' => [], 'party' => null, 'ispp' => 0,
            'iscg' => (bool) ($row['cgCountryID'] ?? 0),
            'iscp' => isset($row['cpID']) && (int) $row['cpID'] === (int) $row['CitizenID'],
            'unit' => null,
        ];
        $wCompID = $this->database->getWorkingCompany($row['CitizenID']);
        if ($wCompID) {
            $data['wComp'] = $this->database->getCompany($wCompID);
        } elseif ($this->database->isManager($row['CitizenID'])) {
            $data['managing'] = $this->database->rows('SELECT * FROM company WHERE ManagerID = ?', [$row['CitizenID']]);
        }
        $wPartyID = $this->database->getCitizenParty($row['CitizenID']);
        if ($wPartyID) {
            $data['party'] = $this->database->getParty($wPartyID);
            $data['ispp'] = $this->database->isPP($row['CitizenID']);
        }
        if ((int) $row['military_unit'] !== 0) {
            $data['unit'] = $this->database->row('SELECT * FROM military_unit WHERE mID = ?', [$row['military_unit']]);
        }
        if (! $is_ca && $row['active']) {
            $data['friends'] = $this->friendship()->getFriends($row['CitizenID'], '1');
        }

        return $data;
    }

    private function editPage(Request $request, ?string $id2): array|\Symfony\Component\HttpFoundation\Response
    {
        $cit = $this->citInfo;
        $citID = $this->citID();
        $msg = null;
        $base = ['sub' => 'edit-'.(in_array($id2, ['name', 'pass'], true) ? $id2 : 'main'), 'subtitle' => $this->lang->getstr('profile_edit', 'profile'), 'subhead' => $this->lang->getstr('profile_edit', 'profile')];

        if ($id2 === 'name' && $request->input('subname')) {
            $pass = (string) $request->input('curpass', '');
            $newname = trim((string) $request->input('newname', ''));
            $taken = $this->database->count('SELECT CitizenID FROM citizens WHERE name = ? OR oldname = ?', [$newname, $newname]);
            $cAcc = $this->database->getCitizenMoney($citID, 1);
            if ($this->database->confirmUserPass($cit['name'], $pass) !== 0) {
                $msg = 'Your password is incorrect';
            } elseif (! $newname) {
                $msg = 'New name cannot be empty';
            } elseif (strlen($newname) > 20) {
                $msg = 'Length is too high. Maximum is 20 characters.';
            } elseif ($newname === $cit['name']) {
                $msg = 'Old name and new name are the same.';
            } elseif ($cAcc < 10) {
                $msg = 'You have not enough money to change your name.';
            } elseif ($cit['editnametime'] >= time() - 30 * 24 * 3600) {
                $msg = "You've changed your name once in the last month.";
            } elseif ($taken) {
                $msg = "The name you've chosen is already taken.";
            } else {
                $this->database->updateUserFieldID($citID, 'oldname', $cit['name']);
                $this->database->updateUserFieldID($citID, 'editnametime', time());
                $this->database->updateUserFieldID($citID, 'name', $newname);
                $this->database->addMoney(1, -10, $citID);
                $this->session->logout();

                return redirect('/index.html');
            }
        } elseif ($id2 === 'pass' && $request->input('subpass')) {
            if ($this->isNCA()) {
                return redirect('/index.html');
            }
            $oldpass = (string) $request->input('curpass', '');
            $n1 = (string) $request->input('newpass1', '');
            $n2 = (string) $request->input('newpass2', '');
            $email = trim((string) $request->input('email', ''));
            $exists = $this->database->count('SELECT CitizenID FROM citizens WHERE email = ?', [$email]);
            if ($this->database->confirmUserPass($cit['name'], $oldpass) !== 0) {
                $msg = 'Your old password is incorrect';
            } elseif ($n1 !== $n2) {
                $msg = "New password doesn't match";
            } elseif (! $n1) {
                $msg = 'New password cannot be empty';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = 'Email is invalid';
            } elseif ($exists >= 1 && $email !== $cit['email']) {
                $msg = 'Email is already in our database';
            } else {
                $this->database->updateUserFieldID($citID, 'email', $email);
                $this->database->updateUserFieldID($citID, 'password', Hash::make($n1));

                return redirect($this->vars->getURL('profile', $citID));
            }
        } elseif ($request->input('subedit')) {
            $file = $request->file('file');
            if ($file) {
                if (in_array($file->getMimeType(), ['image/jpeg', 'image/pjpeg'], true) && $file->getSize() < 50 * 1024) {
                    $fName = md5($citID.config('ejahan.salts.citizen_avatar')).'.jpg';
                    $file->move(public_path('uploads/avatars/citizen'), $fName);
                    $this->database->updateUserFieldID($citID, 'Avatar', $fName);
                } else {
                    $msg = 'The file you specified is not correct.';
                }
            }
            $this->database->updateUserFieldID($citID, 'aboutme', mb_substr((string) $request->input('aboutme', ''), 0, 100));
            if (! $msg) {
                return redirect($this->vars->getURL('profile', $citID));
            }
        }

        return $base + ['msg' => $msg];
    }

    private function personalize(Request $request): void
    {
        $font = match ($request->input('font')) {
            'mssans' => 'MS Sans Serif',
            'tahoma' => 'Tahoma',
            default => 'Arial',
        };
        $langu = in_array($request->input('lang'), config('ejahan.languages'), true) ? $request->input('lang') : 'en';
        $bg = -1;
        switch ($request->input('bg')) {
            case 'default':
                $bg = 'Default';
                break;
            case 'nothing':
                $bg = '';
                break;
            case 'url':
                $url = (string) $request->input('url', '');
                $bg = (filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url)) ? mb_substr($url, 0, 100) : -1;
                break;
            case 'upload':
                $file = $request->file('file');
                if ($file && in_array($file->getMimeType(), ['image/jpeg', 'image/pjpeg', 'image/gif'], true) && $file->getSize() < 2048 * 1024) {
                    $fName = md5($this->citID().config('ejahan.salts.citizen_avatar')).'.jpg';
                    $file->move(public_path('uploads/backgrounds'), $fName);
                    $bg = "uploads/backgrounds/{$fName}";
                }
                break;
        }
        $sql = 'UPDATE citizens SET setting_font = ?, setting_lang = ?';
        $b = [$font, $langu];
        if ($bg !== -1) {
            $sql .= ', setting_bg = ?';
            $b[] = $bg;
        }
        $b[] = $this->citID();
        $this->database->exec($sql.' WHERE CitizenID = ?', $b);
        $this->session->fillInfo(null, true);
    }

    private function travel(Request $request): array
    {
        $cit = $this->citInfo;
        $citID = $this->citID();
        $base = ['sub' => 'travel', 'subtitle' => $this->lang->getstr('profile_travel', 'profile'), 'subhead' => $this->lang->getstr('profile_travel', 'profile'), 'err' => null, 'actTicket' => '', 'toCoun' => ''];
        if ($cit['puberty'] < 1 && ! $this->isCA()) {
            return $base + ['err' => 'You must pass Social Puberty level 1 (15 EP) to be able to travel.', 'blocked' => true];
        }
        if ($cit['occDue'] >= time()) {
            return $base + ['err' => 'Your citizen is currently occupied.', 'blocked' => true];
        }
        if ($request->input('subtravel')) {
            $actTicket = (int) $request->input('ticket');
            $fromCoun = $cit['CountryID'];
            $fromReg = $cit['RegionID'];
            $toCoun = (int) $request->input('CountryID');
            $toReg = (int) $request->input('RegionID');
            $base['actTicket'] = $actTicket;
            $base['toCoun'] = $toCoun;
            $tick = $this->database->row("SELECT * FROM inventory WHERE Owner = ? AND Usable = '1' AND Type = '2' AND Stars = ?", [$citID, $actTicket]);
            if ($this->database->isHiddenCountry($toCoun) && ! $this->session->isAdmin()) {
                return $base + ['err' => 'You cannot travel to this country.'];
            }
            if (! $tick) {
                $base['err'] = 'Cheating ?! You have not a ticket with this quality.';
            } elseif (! $toCoun || ! $toReg) {
                $base['err'] = 'Please select a country and/or region.';
            } elseif ($toReg === 343 || $toReg === 342) {
                $base['err'] = 'You cannot travel to this region.';
            } elseif ($cit['wellness'] < 5 && ! $this->isCA()) {
                $base['err'] = 'Your wellness must be at least 5 to travel.';
            } elseif ((int) $fromReg === $toReg) {
                $base['err'] = 'You cannot travel to your living region.';
            } elseif ($toCoun !== (int) $fromCoun && $actTicket === 1) {
                $base['err'] = 'To go outside of a country, you must use a ticket with 2 stars or more.';
            } elseif ($this->war()->haveWar($fromCoun, $toCoun) && $actTicket !== 5) {
                $base['err'] = 'You cannot go to this country directly according to an active war. You can use a 5-star ticket.';
            } elseif ($this->eco()->getEmbargoes($fromCoun, 'travel', $toCoun) && $actTicket !== 5) {
                $base['err'] = 'You cannot go to this country directly according to a travel embargo. You can use a 5-star ticket.';
            } elseif ($toCoun !== (int) $fromCoun && $this->database->isWorker($citID)) {
                $base['err'] = 'To go outside of a country, you must first resign from your company.';
            } elseif ($toCoun !== (int) $fromCoun && (int) $cit['CountryID'] === (int) ($cit['cgCountryID'] ?? 0)) {
                $base['err'] = 'You are a congress member, so you cannot move to another country until the end of your period.';
            } elseif ($toCoun !== (int) $fromCoun && ($cit['PartyID'] ?? 0)) {
                $base['err'] = 'To go outside of a country, you must first resign from your party.';
            } elseif (! $this->database->count('SELECT RegionID FROM region WHERE RegionID = ? AND CountryID = ?', [$toReg, $toCoun])) {
                $base['err'] = 'Please select a country and/or region.';
            } else {
                $this->database->exec("UPDATE inventory SET Usable = '0' WHERE pID = ?", [$tick['pID']]);
                $this->database->updateUserFieldID($citID, 'regionID', $toReg);
                $inside = $toCoun === (int) $fromCoun;
                $wChange = $inside
                    ? match ($actTicket) { 1, 3 => -2, 2, 4 => 2, 5 => 5, default => 0 }
                    : match ($actTicket) { 2 => -5, 3 => -3, 4 => 1, 5 => 5, default => 0 };
                $well = min(100, $cit['wellness'] + $wChange);
                $this->database->updateUserFieldID($citID, 'wellness', $well);
                $gds = min((int) ($cit['gd_love'] ?? 0), 10);
                $gdmin = [1, 0.95, 0.9, 0.85, 0.8, 0.75, 0.7, 0.65, 0.6, 0.55, 0.5];
                $this->database->updateUserFieldID($citID, 'occDue', time() + (int) ((30 - ($actTicket * 5)) * 60 * $gdmin[$gds]));
                $this->session->fillInfo(null, true);
                $base['redirect'] = $this->vars->getURL('profile', $citID);
            }
        }
        $base['tickets'] = $this->database->rows("SELECT Stars FROM inventory WHERE Owner = ? AND Type = '2' AND Usable = '1' GROUP BY Stars ORDER BY Stars DESC", [$citID]);
        $base['countries'] = $this->database->getCountries($this->session->isAdmin() ? 1 : 0);

        return $base;
    }

    private function nationality(Request $request): array
    {
        $cit = $this->citInfo;
        $citID = $this->citID();
        $base = ['sub' => 'nationality', 'subtitle' => $this->lang->getstr('profile_nationality', 'profile'), 'subhead' => $this->lang->getstr('profile_nationality_change', 'profile'), 'msg' => null];
        if ($cit['puberty'] < 1) {
            return $base + ['blocked' => 'You must pass Social Puberty level 1 (15 EP) to be able to change your nationality.'];
        }
        if ((int) $cit['CountryID'] === (int) $cit['nationality']) {
            return $base + ['blocked' => "Your nationality is as same as your living country, so you don't need to change that."];
        }
        $nFee = (float) $cit['nFee'];
        $req = $this->database->row('SELECT logID, reason FROM log_nchange WHERE CitizenID = ? AND nNation = ? AND approved = 0', [$citID, $cit['CountryID']]);
        $money = $this->database->getCitizenMoney($citID, 1);
        if ($request->input('subrequestNC')) {
            $reason = strip_tags((string) $request->input('reason', ''));
            if (! $req) {
                $this->database->exec('INSERT INTO log_nchange (CitizenID, oNation, nNation, reason, timestamp) VALUES (?, ?, ?, ?, ?)', [$citID, $cit['nationality'], $cit['CountryID'], $reason, time()]);
            } else {
                $this->database->exec('UPDATE log_nchange SET oNation = ?, reason = ?, timestamp = ? WHERE logID = ?', [$cit['nationality'], $reason, time(), $req['logID']]);
            }
            $base['redirect'] = $request->getRequestUri();
        } elseif ($request->has('nationality')) {
            if ($money < $nFee) {
                $base['msg'] = '<h3 class=errHandle>You have not enough Tala to change your nationality.</h3>';
            } else {
                $tFee = round($nFee / 4, 2);
                $bFee = $nFee - $tFee;
                $this->database->transferMoney(1, $tFee, $citID, 'citizen', $cit['CountryID'], 'country');
                $this->database->transferMoney(1, $bFee, $citID, 'citizen', 1, 'citizen');
                $this->database->updateUserFieldID($citID, 'nationality', $cit['CountryID']);
                $this->database->addEP($citID, 3, 'Change Nationality');
                $this->database->exec("INSERT INTO log_nchange (CitizenID, oNation, nNation, reason, timestamp, approved) VALUES (?, ?, ?, 'N/A', ?, 1)", [$citID, $cit['nationality'], $cit['CountryID'], time()]);
                if ($req) {
                    $this->database->exec('DELETE FROM log_nchange WHERE logID = ?', [$req['logID']]);
                }
                $base['msg'] = '<h3 class=infHandle>Your nationality successfully changed!</h3>';
                $this->session->fillInfo(null, true);
                $cit = $this->citInfo = $this->session->userinfo;
                $money = $this->database->getCitizenMoney($citID, 1);
            }
        }

        return $base + ['nFee' => $nFee, 'money' => $money, 'hasRequest' => (bool) $req, 'myreason' => $req['reason'] ?? '',
            'embargo' => (bool) $this->eco()->getEmbargoes($cit['nationality'], 'travel', $cit['CountryID']), 'cit' => $cit, 'same' => (int) $cit['CountryID'] === (int) $cit['nationality']];
    }

    private function donate(Request $request, array $row, bool $is_nca, bool $hasPro, bool $hasPlus): array
    {
        $cit = $this->citInfo;
        $citID = $this->citID();
        $base = ['sub' => 'donate', 'subtitle' => $this->lang->getstr('profile_donate', 'profile'), 'subhead' => $this->lang->getstr('profile_donate_item', 'profile'), 'msg' => null];
        $maxU = $is_nca ? 400 : ($hasPro ? 200 : ($hasPlus ? 100 : 40));
        $uInvs = $this->database->getCitizenInventory($row['CitizenID'], $maxU, 1);
        $maxX = $this->havePro() ? 500 : 20;
        $maxdon = max(0, min($maxU - $uInvs, $maxX));

        if ($request->input('subdonit')) {
            $type = (int) $request->input('type');
            $quality = (int) $request->input('quality');
            $amount = (int) $request->input('amount');
            $ttok = md5($type.$quality.$citID.'D0nAte It3m');
            if (! $amount) {
                $base['msg'] = '<h3 class="errHandle">You must select at least one item</h3>';
            } else {
                $ids = array_column($this->database->rows("SELECT pID FROM inventory WHERE Usable = 1 AND Owner = ? AND Type = ? AND Stars = ? LIMIT {$amount}", [$citID, $type, $quality]), 'pID');
                $amount = count($ids);
                if (! hash_equals($ttok, (string) $request->input('token'))) {
                    $base['msg'] = '<h3 class="errHandle">Cheating detected.</h3>';
                } elseif ($amount > $maxdon) {
                    $base['msg'] = $maxdon ? "<h3 class=\"errHandle\">You can donate max. {$maxdon} items. Please remove some items</h3>"
                        : '<h3 class="errHandle">The inventory of this citizen is full, so you cannot donate any item</h3>';
                } elseif ($amount <= 0) {
                    $base['msg'] = '<h3 class="errHandle">You must select at least one item</h3>';
                } else {
                    $in = implode(',', array_map('intval', $ids));
                    if ((int) $row['CitizenID'] === 1) {
                        $this->database->exec("UPDATE inventory SET Usable = 0 WHERE pID IN ({$in})");
                        $base['msg'] = "<h3 class=\"infHandle\">Successfully removed {$amount} items!</h3>";
                    } else {
                        $this->database->exec("UPDATE inventory SET Owner = ? WHERE pID IN ({$in})", [$row['CitizenID']]);
                        $this->database->donate(0, $amount, 0, $citID, 'citizen', $row['CitizenID'], 'citizen');
                        $base['msg'] = "<h3 class=\"infHandle\">Successfully sent {$amount} items to ".e($row['name']).'!</h3>';
                    }
                    $this->session->fillInfo(null, true);
                }
            }
        }
        if ($request->has('subdonate')) {
            $to = (int) $request->input('To');
            $type = (int) $request->input('Type');
            $amount = (string) $request->input('Amount', '');
            $ttok = md5($to.'key not for donate'.$citID);
            $have = $this->database->getCitizenMoney($citID, $type);
            if (! hash_equals($ttok, (string) $request->input('token')) || ($type === 1 && ! $cit['active'])) {
                $base['msg'] = '<h3 class=errHandle>Cheating detected</h3>';
            } elseif (! $amount || ! preg_match('/^[0-9.]+$/', $amount) || (float) $amount <= 0) {
                $base['msg'] = '<h3 class=errHandle>The amount is not numeric.</h3>';
            } elseif ($have < (float) $amount) {
                $base['msg'] = '<h3 class=errHandle>You have not enough money to donate this amount.</h3>';
            } elseif ($to === $citID) {
                $base['msg'] = '<h3 class=errHandle>You cannot donate yourself.</h3>';
            } elseif ($this->database->donate($type, (float) $amount, $type, $citID, 'citizen', $to, 'citizen')) {
                $base['msg'] = '<h3 class=infHandle>Successfully donated to '.e($row['name']).'!</h3>';
            }
        }
        $max = $this->isNCA() ? 400 : ($this->havePro() ? 200 : ($this->havePlus() ? 100 : 50));

        return $base + [
            'maxdon' => $maxdon,
            'invs' => $this->database->getCitizenInventory($citID, $max),
            'currencies' => $this->database->rows('SELECT CurID FROM citizen_money WHERE CitID = ?', [$citID]),
        ];
    }

    private function consume(Request $request, string $kind): array
    {
        $cit = $this->citInfo;
        $citID = $this->citID();
        $isFood = $kind === 'food';
        $table = $isFood ? 'log_consume' : 'log_consume_healthkit';
        $itemType = $isFood ? 1 : 12;
        $base = ['sub' => $isFood ? 'consume' : 'healthkit', 'subtitle' => $this->lang->getstr($isFood ? 'profile_usefood' : 'profile_usehealthkit', 'profile'),
            'subhead' => $this->lang->getstr($isFood ? 'profile_usefood' : 'profile_usehealthkit', 'profile'), 'msg' => null, 'remCons' => null];

        if ($isFood) {
            $timeGP = ($cit['gold_pack'] > time()) ? 5 : 10;
            $wait = $timeGP * 60;
        } else {
            $timeGP = 20;
            $wait = ($cit['gold_pack'] < time()) ? 20 * 60 : 0;
        }
        $last = $this->database->row("SELECT * FROM {$table} WHERE citID = ? ORDER BY timestamp DESC LIMIT 1", [$citID]);
        $gds = min((int) ($cit['gd_life'] ?? 0), 9);
        $gdmin = [1, 1.1, 1.14, 1.17, 1.2, 1.21, 1.22, 1.23, 1.24, 1.25];
        $gdMul = $gdmin[$gds];
        $qH = $isFood ? (int) $this->database->value('SELECT Stars FROM inventory WHERE Owner = ? AND Type = 8 AND Usable = 1 ORDER BY Stars DESC LIMIT 1', [$citID], 0) : 0;

        if ($last && $last['timestamp'] + $wait > time()) {
            $base['remCons'] = $last['timestamp'] + $wait;
        } elseif ($request->input('subconsume')) {
            $qF = (int) $request->input('quality', 0);
            $item = $qF ? $this->database->row('SELECT * FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = ? AND Stars = ? LIMIT 1', [$citID, $itemType, $qF]) : null;
            if (! $item && $qF) {
                $base['msg'] = '<h3 class="errHandle">'.$this->lang->getstr($isFood ? 'err_no_food' : 'err_no_healthkit', 'msgs').'</h3>';
            } else {
                $wC = $qF ? ($isFood ? round((7.5 - ($cit['wellness'] / 100)) * ($qF + $qH) * $gdMul) : round(20 * $qF + $cit['wellness'])) : 0;
                $wChange = $cit['wellness'] + $wC;
                if ($wChange > 100) {
                    $wChange = 100;
                    $wC = 100 - $cit['wellness'];
                }
                $this->database->updateUserFieldID($citID, 'wellness', $wChange);
                $this->database->exec("INSERT INTO {$table} (citID, day, ".($isFood ? 'food' : 'healthkit').", house, `change`, timestamp) VALUES (?, ?, ?, ?, ?, ?)",
                    [$citID, $this->database->today, $qF, $isFood ? $qH : 0, $wC, time()]);
                if ($item) {
                    $this->database->exec('UPDATE inventory SET Usable = 0 WHERE pID = ?', [$item['pID']]);
                }
                $this->session->fillInfo(null, true);
                $base['redirect'] = $request->getRequestUri();
            }
        }
        $avail = [];
        foreach ($this->database->rows('SELECT Stars FROM inventory WHERE Owner = ? AND Usable = 1 AND Type = ? GROUP BY Stars ORDER BY Stars', [$citID, $itemType]) as $r) {
            $avail[(int) $r['Stars']] = 1;
        }

        return $base + [
            'timeGP' => $timeGP, 'gdMul' => $gdMul, 'qH' => $qH, 'avail' => $avail,
            'log' => $this->database->rows("SELECT * FROM {$table} WHERE citID = ? AND day >= ? ORDER BY timestamp DESC", [$citID, $this->database->today - 1]),
        ];
    }
}
