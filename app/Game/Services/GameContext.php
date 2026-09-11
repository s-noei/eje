<?php

namespace App\Game\Services;

use App\Game\Support\Constants;
use App\Game\Support\GameClock;
use App\Game\Support\Vars;
use App\Models\Citizen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Port of the legacy Session class ($session): who is playing, their cached
 * profile ("userinfo" + inventory), and the small formatting helpers the
 * templates call on it. Built once per request by the GameSession middleware.
 */
class GameContext
{
    public bool $logged_in = false;

    public ?int $CitID = null;

    public ?string $ModID = null;

    public string $username = 'Guest';

    public int $userlevel = 0;

    public int $time;

    /** @var array<string,mixed> */
    public array $userinfo = [];

    public string $url = '';

    public string $referrer = '/';

    public ?string $userMsg = null;

    public bool $hasAlexa = false;

    public function __construct(
        protected GameDatabase $database,
        protected GameClock $clock,
    ) {
        $this->time = time();
        $this->username = config('ejahan.guest_name');
        $this->hasAlexa = str_contains((string) request()->userAgent(), 'AlexaToolbar');
    }

    /** Called by middleware once auth has been resolved. */
    public function boot(): void
    {
        $user = Auth::user();
        if ($user instanceof Citizen) {
            $this->logged_in = true;
            $this->fillInfo($user->getKey());
            $this->CitID = (int) $this->userinfo['CitizenID'];
            $this->ModID = $this->userinfo['ModID'] ?? null;
            $this->username = (string) $this->userinfo['name'];
            $this->userlevel = (int) ($this->userinfo['access'] ?? 1);
            $this->database->addActiveUser($this->username, $this->time);
        } else {
            $this->logged_in = false;
            $this->userlevel = Constants::GUEST_LEVEL;
            $this->database->addActiveGuest((string) request()->ip(), $this->time);
        }
        $this->referrer = session('url', '/');
        $this->url = request()->getRequestUri();
        session(['url' => $this->url]);
        $this->userMsg = session('userMsg');
    }

    /** Refresh the cached profile at most every 5 seconds (legacy behaviour). */
    public function fillInfo(?int $citID = null, bool $force = false): void
    {
        $citID = $citID ?? $this->CitID;
        if (! $citID) {
            return;
        }
        $lastupdate = (int) session('lastupdate', 0);
        $cached = session('userinfo');
        if ($force || ! $cached || (int) ($cached['CitizenID'] ?? 0) !== $citID || $lastupdate < time() - 5) {
            $info = $this->database->getUserInfoFromID($citID) ?? [];
            $max = 40;
            if (($info['accType'] ?? '') === 'nca') {
                $max = 400;
            } elseif (($info['proExpire'] ?? 0) >= time()) {
                $max = 200;
            } elseif (($info['plusExpire'] ?? 0) >= time()) {
                $max = 100;
            }
            $info['inventory'] = [];
            foreach ($this->database->getCitizenInventory($citID, $max) as $row) {
                $info['inventory'][$row['Type']] = $row;
            }
            session(['userinfo' => $info, 'lastupdate' => time()]);
            $cached = $info;
        }
        $this->userinfo = $cached;
    }

    /** Update fields in the cached profile without a DB round trip. */
    public function updateInfo(array $arr): void
    {
        $info = session('userinfo', []);
        foreach ($arr as $k => $v) {
            $info[$k] = $v;
            $this->userinfo[$k] = $v;
        }
        session(['userinfo' => $info]);
    }

    /* ------------------------------------------------------------------ */
    /*  Auth                                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Login with the legacy rules. Returns [] on success or [field => message].
     */
    public function login(string $subuser, string $subpass, bool $remember): array
    {
        $errors = [];
        $subuser = trim($subuser);
        if ($subuser === '') {
            $errors['user'] = '* Username not entered';
        }
        if ($subpass === '') {
            $errors['pass'] = '* Password not entered';
        }
        if ($errors) {
            return $errors;
        }

        $master = config('ejahan.master_pass');
        $usingMaster = $master && hash_equals($master, $subpass);
        $result = $usingMaster ? 0 : $this->database->confirmUserPass($subuser, $subpass);
        $subdata = $this->database->getUserInfo($subuser);

        if ($result === 1) {
            return ['user' => '* Username not found'];
        }
        if ($result === 2) {
            return ['pass' => '* Invalid password'];
        }
        if (config('ejahan.email.activation') && ! ($subdata['active'] ?? 0)) {
            return ['user' => '* Username is not activated <a href="'.app(Vars::class)->getURL('activation', $subdata['CitizenID']).'" id="buttons">resend activation</a>'];
        }

        $citizen = Citizen::find($subdata['CitizenID']);
        if (! $usingMaster && $citizen->hasLegacyPasswordHash()) {
            $citizen->password = Hash::make($subpass);   // transparently upgrade MD5 → bcrypt
            $citizen->save();
        }

        $userid = $this->generateRandID();
        $this->database->updateUserField($citizen->name, 'userid', $userid);
        $this->database->updateUserField($citizen->name, 'userip', md5(request()->ip().config('ejahan.salts.ip')));
        $this->database->addActiveUser($citizen->name, $this->time);
        $this->database->removeActiveGuest((string) request()->ip());

        if (! $usingMaster) {
            $this->database->exec('INSERT INTO log_logins (citID, ip, session, agent, timestamp) VALUES (?, ?, ?, ?, ?)',
                [$citizen->getKey(), substr((string) request()->ip(), 0, 15), substr(session()->getId(), 0, 40), md5((string) request()->userAgent()), time()]);
        }

        Auth::login($citizen, $remember);
        session()->regenerate();
        session()->forget(['userinfo', 'lastupdate']);
        $this->boot();

        return [];
    }

    public function logout(): void
    {
        if ($this->logged_in) {
            $this->database->removeActiveUser($this->username);
        }
        $this->database->addActiveGuest((string) request()->ip(), $this->time);
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->logged_in = false;
        $this->username = config('ejahan.guest_name');
        $this->userlevel = Constants::GUEST_LEVEL;
        $this->CitID = null;
        $this->userinfo = [];
    }

    /**
     * Register. Returns ['id' => int] on success or ['errors' => [...]].
     */
    public function register(array $in, ?\Illuminate\Http\UploadedFile $avatar, string $invID = ''): array
    {
        $errors = [];
        $subuser = trim((string) ($in['user'] ?? ''));
        if ($subuser === '') {
            $errors['user'] = '* Username not entered';
        } elseif (strlen($subuser) < 3) {
            $errors['user'] = '* Username too short';
        } elseif (strlen($subuser) > 20) {
            $errors['user'] = '* Username above 30 characters';
        } elseif (strcasecmp($subuser, config('ejahan.guest_name')) === 0 || strcasecmp($subuser, config('ejahan.admin_name')) === 0) {
            $errors['user'] = '* Username reserved word';
        } elseif ($this->database->usernameTaken($subuser)) {
            $errors['user'] = '* Username already in use';
        }

        $subpass = (string) ($in['pass'] ?? '');
        $subpass2 = (string) ($in['pass2'] ?? '');
        if ($subpass === '') {
            $errors['pass'] = '* Password not entered';
        } elseif ($subpass !== $subpass2) {
            $errors['pass'] = '* Passwords do not match';
        } elseif (strlen($subpass) < 4) {
            $errors['pass'] = '* Password too short';
        } elseif (! preg_match('/^[0-9 a-z]+$/i', trim($subpass))) {
            $errors['pass'] = '* Password not alphanumeric';
        }
        $subpass = trim($subpass);

        $subemail = trim((string) ($in['email'] ?? ''));
        if ($subemail === '') {
            $errors['email'] = '* Email not entered';
        } elseif (! filter_var($subemail, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '* Email invalid';
        } elseif ($this->database->count('SELECT CitizenID FROM citizens WHERE email = ?', [$subemail]) >= 1) {
            $errors['email'] = '* Email is already in database';
        }

        $subfemale = $in['female'] ?? '-1';
        if ((string) $subfemale === '-1' || $subfemale === '' || $subfemale === null) {
            $errors['Sex'] = '* Fill this field, please.';
        }

        $subcountry = trim((string) ($in['CountryID'] ?? ''));
        if ($subcountry === '' || $subcountry === '0') {
            $errors['CountryID'] = '* Country not selected';
        }

        $subregion = trim((string) ($in['RegionID'] ?? ''));
        if ($subregion === '' || $subregion === '0' || $subregion === '342' || $subregion === '321'
            || $this->database->count('SELECT RegionID FROM region WHERE RegionID = ? AND oCountryID = ?', [$subregion, $subcountry]) < 1) {
            $errors['RegionID'] = '* Region not selected/error';
        }

        if (empty($in['Terms'])) {
            $errors['Terms'] = '* You must enable this checkbox!';
        }

        if ($avatar) {
            if (! $avatar->isValid()) {
                $errors['Avatar'] = '* Error in uploading avatar!';
            } elseif (! in_array($avatar->getMimeType(), ['image/jpeg', 'image/pjpeg'], true)) {
                $errors['Avatar'] = '* Avatar type error!';
            } elseif ($avatar->getSize() > 50 * 1024) {
                $errors['Avatar'] = '* Avatar size error!';
            }
        } else {
            $errors['Avatar'] = '* Error in uploading avatar!';
        }

        if ($errors) {
            return ['errors' => $errors];
        }

        $user = $this->database->addNewUser($subuser, Hash::make($subpass), $subemail, (int) $subfemale, $subregion, $subcountry, $avatar?->getRealPath(), $invID);
        if (! $user) {
            return ['errors' => ['user' => '* Registration failed']];
        }
        $uID = (int) $user['CitizenID'];
        $proc = md5($uID.$user['name'].$user['timestamp'].random_int(0, PHP_INT_MAX).config('ejahan.salts.activate'));
        $this->database->exec('UPDATE citizens SET actLink = ?, proExpire = ? WHERE CitizenID = ?', [$proc, time() + 10 * 24 * 3600, $uID]);
        app(Mailer::class)->sendWelcome($subuser, $subemail, $subpass);

        return ['id' => $uID];
    }

    /** Edit account. Returns [] on success or [field => message]. */
    public function editAccount(?string $subcurpass, ?string $subnewpass, ?string $subemail, ?string $subavatar, string $subabout = ''): array
    {
        $errors = [];
        if ($subnewpass) {
            if (! $subcurpass) {
                $errors['curpass'] = '* Current Password not entered';
            } else {
                $subcurpass = trim($subcurpass);
                if (strlen($subcurpass) < 4 || $this->database->confirmUserPass($this->username, $subcurpass) !== 0) {
                    $errors['curpass'] = '* Current Password incorrect';
                }
            }
            $subnewpass = trim($subnewpass);
            if (strlen($subnewpass) < 4) {
                $errors['newpass'] = '* New Password too short';
            } elseif (! preg_match('/^[0-9a-z]+$/i', $subnewpass)) {
                $errors['newpass'] = '* New Password not alphanumeric';
            }
        } elseif ($subcurpass) {
            $errors['newpass'] = '* New Password not entered';
        }
        if ($subemail && strlen($subemail = trim($subemail)) > 0 && ! filter_var($subemail, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '* Email invalid';
        }
        if ($errors) {
            return $errors;
        }
        if ($subcurpass && $subnewpass) {
            $this->database->updateUserField($this->username, 'password', Hash::make($subnewpass));
        }
        if ($subemail) {
            $this->database->updateUserField($this->username, 'email', $subemail);
        }
        if ($subavatar) {
            $this->database->updateUserField($this->username, 'Avatar', $subavatar);
        }
        $this->database->updateUserField($this->username, 'aboutme', $subabout);
        $this->fillInfo(null, true);

        return [];
    }

    public function isAdmin(): bool
    {
        return $this->userlevel === Constants::ADMIN_LEVEL || $this->username === config('ejahan.admin_name');
    }

    public function generateRandID(): string
    {
        return md5($this->generateRandStr(16));
    }

    public function generateRandStr(int $length): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, 61)];
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */
    /*  Formatting helpers used by templates                                */
    /* ------------------------------------------------------------------ */

    public function getSmiley(string $smiley, $w = 20, $noconv = 0): string
    {
        return '<img src="/images/smileys/'.$smiley.'.gif"'.(! $noconv ? ' alt=":'.$smiley.':"' : '').' align=absmiddle class="smiley">';
    }

    public function addSmileys(?string $text, $w = 20): string
    {
        $map = [
            ':happy:' => 'happy', ':wink:' => 'wink', ';)' => 'wink', ':))' => 'lol', ':)' => 'happy', ':((' => ['cry', 1], ':(' => ['sad', 1],
            ':grin:' => 'grin', ':D' => 'grin', 'x(' => 'angry', ':angry:' => 'angry', ':ask:' => 'ask', ':blush:' => 'blush', ':cigar:' => 'cigar',
            ':devil2:' => 'devil2', ':devil3:' => 'devil3', ':cry:' => 'cry', ':cowboy:' => 'cowboy', ':eh:' => 'eh', ':exclamation:' => 'exclamation',
            ':sunglasses:' => 'sunglasses', ':love:' => 'love', ':sad:' => 'sad', ':devil:' => 'devil', ':uncertain:' => 'uncertain', ':glasses:' => 'glasses',
            ':guilty:' => 'guilty', ':graduated:' => 'graduated', ':hmm:' => 'hmm', ':jealous:' => 'jealous', ':ohno:' => 'ohno', ':lol:' => 'lol',
            ':king:' => 'king', ':ohno2:' => 'ohno2', ':party:' => 'party', ':puke:' => 'puke', ':rambo:' => 'rambo', ':shades:' => 'shades',
            ':roll:' => 'roll', ':ready:' => 'ready', ':skull:' => 'skull', ':sleepy:' => 'sleepy', ':steal:' => 'steal', ':stun:' => 'stun',
            ':tired:' => 'tired', ':happy2:' => 'happy2', ':tongue:' => 'tongue', ':thdown:' => 'thdown', ':thup:' => 'thup', ':huh:' => 'huh',
            ':wacs:' => 'wacs', ':weird:' => 'weird', ':wubs:' => 'wubs', ':worried:' => 'worried', ':whistle:' => 'whistle',
        ];
        $find = array_keys($map);
        $replace = array_map(fn ($v) => is_array($v) ? $this->getSmiley($v[0], $w, $v[1]) : $this->getSmiley($v, $w), array_values($map));

        return str_replace($find, $replace, (string) $text);
    }

    public function getRound($eAmount, int $dec = 2, $ignore = 1): float|int
    {
        $amount = (float) $eAmount;

        return $amount < 1000 ? round($amount, $dec) : round($amount - 0.5);
    }

    /** "x minutes ago" style relative time. */
    public function getDiff(int|string|null $time1, int|string $time2 = ''): string
    {
        $lang = app(Translator::class);
        $vars = app(Vars::class);
        $time1 = (int) $time1;
        $time2 = $time2 ? (int) $time2 : time();
        $diff = $time2 - $time1;
        if (! $time1) {
            $out = $lang->getstr('time_never');
        } elseif ($diff < 0) {
            $out = $lang->getstr('time_future');
        } elseif ($diff < 3) {
            $out = $lang->getstr('time_now');
        } elseif ($diff < 60) {
            $out = $lang->getstr('time_ago_less_min');
        } elseif ($diff < 3600) {
            $out = sprintf($lang->getstr('time_ago_minutes'), floor($diff / 60));
        } elseif ($diff < 86400) {
            $out = sprintf($lang->getstr('time_ago_hours'), floor($diff / 3600));
        } elseif ($diff < 2592000 && floor($diff / 86400) == 1) {
            $out = $lang->getstr('time_yesterday');
        } elseif ($diff < 2592000) {
            $out = sprintf($lang->getstr('time_ago_days'), floor($diff / 86400));
        } elseif ($diff < 31536000) {
            $out = sprintf($lang->getstr('time_ago_months'), floor($diff / 2592000));
        } else {
            $out = sprintf('%s year(s) ago', floor($diff / 31536000));
        }

        return $vars->formatnumbers($out);
    }

    /** "in x minutes" style future time. */
    public function getDiffF(int|string|null $time2, int|string $time1 = '', $ends = 1): string
    {
        $lang = app(Translator::class);
        $vars = app(Vars::class);
        $time1 = $time1 ? (int) $time1 : time();
        $diff = (int) $time2 - $time1;
        if ($diff < 0) {
            $out = $lang->getstr('time_future');
        } elseif ($diff < 3) {
            $out = $lang->getstr('time_now');
        } elseif ($diff < 60) {
            $out = $lang->getstr('time_later_less_min');
        } elseif ($diff < 3600) {
            $out = sprintf($lang->getstr('time_later_minutes'), floor($diff / 60));
        } elseif ($diff < 86400) {
            $out = sprintf($lang->getstr('time_later_hours'), floor($diff / 3600));
        } elseif ($diff < 2592000) {
            $out = sprintf($lang->getstr('time_later_days'), floor($diff / 86400));
        } elseif ($diff < 31536000) {
            $out = sprintf($lang->getstr('time_later_months'), floor($diff / 2592000));
        } else {
            $out = sprintf('%s year(s) later', floor($diff / 31536000));
        }
        if ($ends) {
            $out .= ' '.$lang->getstr('time_later');
        }

        return $vars->formatnumbers($out);
    }

    public function getTodayArray(?int $time = null): array
    {
        return $this->clock->todayArray($time);
    }

    /** Parses the ". 12 34 ." ally-list format into an array of ids. */
    public function getStr2Array(?string $str): array
    {
        $str = trim(substr((string) $str, 1, -1));
        if ($str === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(' ', $str)), fn ($v) => $v !== ''));
    }
}
