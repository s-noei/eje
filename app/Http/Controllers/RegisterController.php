<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of register.php + Process::procRegister + include/register/success.php.
 */
class RegisterController extends GameController
{
    public function form(Request $request, ?string $referer = null)
    {
        if (config('ejahan.must_use_invites')) {
            return redirect('/index.html');
        }
        $this->lang->addPhrases('register');
        $layout = ['title' => $this->lang->getstr('title_register', 'title'), 'coltype' => 1, 'actiontype' => 'register', 'bar_title' => 'Register'];

        $refID = $request->query('byid') ?: $referer;
        $ref = null;
        $view_form = 0;
        if ($refID && $refID !== 'done') {
            $ref = $this->database->getUserInfoFromID((int) $refID, 0);
            if (! $ref || $ref['accType'] === 'co-account' || $ref['puberty'] < 0) {
                $view_form = -1;
            }
        }
        $referer = $ref['CitizenID'] ?? '';

        if ($refID === 'done') {
            return $this->success($layout);
        }
        if ($this->loggedIn()) {
            return redirect($this->vars->getURL('home'));
        }
        if (session()->has('regsuccess')) {
            $ok = session()->pull('regsuccess');
            $name = session()->pull('reguname');
            if ($ok) {
                return $this->success($layout);
            }

            return $this->page('pages.message', ['message' => '<h1>Registration Failed</h1><p>We\'re sorry, but an error has occurred and your registration for the username <b>'.e($name).'</b>, could not be completed.<br>Please try again at a later time.</p>'], $layout);
        }
        $refer = ($ref['name'] ?? null) ? e($ref['name']) : '<i>'.$this->lang->getstr('register_direct', 'register').'</i>';
        $lang = in_array($this->lang->lang, config('ejahan.languages'), true) ? $this->lang->lang : 'en';
        $countries = $this->database->rows("SELECT country.CountryID, IFNULL(trans_strings.trans_{$lang}, IFNULL(trans_strings.trans_en, country.cName)) AS cName
            FROM country JOIN trans_strings ON country.shortName = trans_strings.phrase WHERE Hidden = '0' ORDER BY cName");

        return $this->page('pages.register', compact('referer', 'refer', 'view_form', 'countries'), $layout);
    }

    private function success(array $layout)
    {
        $citInfo = $this->database->getUserInfoFromID((int) session('reguid', 0));
        if (! $citInfo) {
            return redirect('/index.html');
        }

        return $this->page('pages.register-success', ['newCit' => $citInfo], $layout);
    }

    public function process(Request $request)
    {
        $invID = (string) $request->input('red', '');
        $token = (string) $request->input('token', '');
        if (! hash_equals(md5($invID.$invID.config('ejahan.salts.register')), $token)) {
            return redirect('/index.html');
        }
        // one registration per IP per day
        if ($this->database->count("SELECT logID FROM log WHERE `Type` = 'Register' AND Param = ? AND timestamp >= ?", [$request->ip(), time() - 86400]) >= 1) {
            return redirect('/index.html');
        }
        $in = $request->all();
        $in['female'] = $request->has('Sex') ? (int) $request->input('Sex') - 1 : -1;
        $result = $this->session->register($in, $request->file('avatar'), $invID);
        if (isset($result['id'])) {
            session(['reguname' => $request->input('user'), 'reguid' => $result['id'], 'regsuccess' => true]);

            return redirect('/register-done.html');
        }

        return redirect($invID ? "/register-{$invID}.html" : '/register.html')->withErrors($result['errors'])->withInput($request->except(['pass', 'pass2']));
    }
}
