<?php

namespace App\Http\Controllers\Lens;

use App\Game\Services\GameDatabase;
use App\Game\Services\Translator;
use App\Game\Support\Vars;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Base for the eJahan LENS moderation panel (port of lens/index.php + lens/include/lens.php).
 * Moderators log in with their ModID; the session keeps the LensID token stored in lens_info.
 */
abstract class LensController extends \App\Http\Controllers\Controller
{
    public const ACCESS_TECHNICAL = 4;
    public const ACCESS_LOCAL = 5;
    public const ACCESS_POLICE = 6;
    public const ACCESS_SUPERMOD = 7;
    public const ACCESS_GUARD = 8;
    public const ACCESS_ADMIN = 9;

    protected GameDatabase $database;
    protected Vars $vars;
    protected Translator $lang;
    /** lens_info row joined with the assigned citizen (legacy $citInfo inside lens). */
    protected ?array $mod = null;

    public function __construct()
    {
        $this->database = app(GameDatabase::class);
        $this->vars = app(Vars::class);
        $this->lang = app(Translator::class);
        $this->mod = $this->loadMod();
    }

    protected function loadMod(): ?array
    {
        $modID = session('lens.mod_id');
        $lensID = session('lens.lens_id');
        if (!$modID || !$lensID) {
            return null;
        }
        $row = $this->database->row('SELECT lens_info.*, citizens.*, country.cName FROM lens_info
            LEFT JOIN citizens ON citizens.CitizenID = lens_info.AssignedTo
            LEFT JOIN country ON (country.CountryID = lens_info.AccessD AND lens_info.Access = 5)
            WHERE ModID = ? AND LensID = ?', [$modID, $lensID]);
        if (!$row) {
            return null;
        }
        $row['userlevel'] = (int) $row['Access'];

        return $row;
    }

    protected function logged(): bool
    {
        return $this->mod !== null && $this->mod['userlevel'] >= 3;
    }

    protected function level(): int
    {
        return (int) ($this->mod['userlevel'] ?? 0);
    }

    protected function canDoActions(): bool
    {
        return $this->level() >= self::ACCESS_GUARD;
    }

    /** lens URL builder (lens.php getURL). */
    public static function url(string $action, $type = '', $id = '', $page = ''): string
    {
        if ($type === '' || $type === null) {
            return "/lens/$action.html";
        }
        if ($id === '' || $id === null) {
            return "/lens/$action-$type.html";
        }
        if ($page === '' || $page === null) {
            return "/lens/$action-$type-$id.html";
        }

        return "/lens/$action-$type-$id-$page.html";
    }

    protected function lensPage(string $view, array $data = [], string $title = 'Home', string $action = ''): View
    {
        return view($view, $data + [
            'mod' => $this->mod, 'lensTitle' => $title, 'lensAction' => $action, 'ulevel' => $this->level(),
            'lensUrl' => fn (...$a) => self::url(...$a), 'vars' => $this->vars, 'database' => $this->database, 'lang' => $this->lang,
            'session' => app(\App\Game\Services\GameContext::class),
            'pendingTickets' => $this->pendingTickets(), 'pendingRegs' => $this->pendingRegs(),
        ]);
    }

    /** Ticket departments a moderator may handle (lens/index.php). */
    protected function ticketReasons(): array
    {
        $tick = (string) ($this->mod['at_tickets'] ?? '');
        if ($tick === '') {
            return [];
        }
        if (!in_array($tick, ['admin', 'smod', 'guard'], true)) {
            return [$tick];
        }
        $ticks = ['bugreport', 'abuse', 'multi'];
        if (in_array($tick, ['guard', 'admin'], true)) {
            array_push($ticks, 'appeal', 'supgame');
        }
        if ($tick === 'admin') {
            array_push($ticks, 'modticket', 'modreport', 'payment', 'support', 'feedback');
        }

        return $ticks;
    }

    protected function ticketScope(): array
    {
        $ticks = $this->ticketReasons();
        if (!$ticks) {
            return ['', []];
        }
        $sql = ' AND (reason IN ('.implode(',', array_fill(0, count($ticks), '?')).')';
        $b = $ticks;
        if ((int) $this->mod['Access'] === self::ACCESS_LOCAL) {
            $sql .= ' AND country = ?';
            $b[] = $this->mod['AccessD'];
        }

        return [$sql.')', $b];
    }

    protected function pendingTickets(): int
    {
        if (!$this->mod || !$this->mod['at_tickets']) {
            return 0;
        }
        [$scope, $b] = $this->ticketScope();

        return $this->database->count('SELECT ticket_head.ticket_id FROM ticket_head JOIN citizens ON citizens.CitizenID = ticket_head.by_id WHERE status = 0'.$scope, $b);
    }

    protected function pendingRegs(): int
    {
        if (!$this->mod || !$this->mod['at_mods']) {
            return 0;
        }

        return $this->database->count('SELECT * FROM lens_registration WHERE status = 1');
    }

    /* ------------------------------------------------------------------ */

    /** Legacy login: MD5 password, upgraded to bcrypt on success. */
    public function login(Request $request)
    {
        if ($this->logged()) {
            return redirect(self::url('index'));
        }
        if ($request->input('sublogin')) {
            $user = (string) $request->input('modid');
            $pass = (string) $request->input('pass');
            $row = $this->database->row('SELECT * FROM lens_info WHERE ModID = ?', [$user]);
            $ok = false;
            if ($row) {
                $stored = (string) $row['Password'];
                if (strlen($stored) === 32 && ctype_xdigit($stored)) {
                    $ok = hash_equals($stored, md5($pass));
                    if ($ok) {
                        $this->database->exec('UPDATE lens_info SET Password = ? WHERE ModID = ?', [Hash::make($pass), $user]);
                    }
                } else {
                    $ok = Hash::check($pass, $stored);
                }
            }
            if ($ok) {
                $uID = md5((string) random_int(0, PHP_INT_MAX));
                $this->database->exec('UPDATE lens_info SET LensID = ? WHERE ModID = ?', [$uID, $user]);
                session(['lens.mod_id' => $user, 'lens.lens_id' => $uID]);

                return redirect(self::url('index'));
            }
        }

        return $this->lensPage('lens.login', [], 'Login');
    }

    public function logout()
    {
        if ($this->mod) {
            $this->database->exec("UPDATE lens_info SET LensID = 'loggedout' WHERE ModID = ?", [$this->mod['ModID']]);
        }
        session()->forget('lens');

        return redirect(self::url('index'));
    }
}
