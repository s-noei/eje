<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of process.php (login/logout/forgot) and activate.php / activation.php.
 */
class AuthController extends GameController
{
    public function loginForm()
    {
        if ($this->loggedIn()) {
            return redirect($this->vars->getURL('home'));
        }

        return $this->page('pages.login', [], ['title' => 'Login', 'coltype' => 1, 'actiontype' => 'login']);
    }

    public function login(Request $request)
    {
        $errors = $this->session->login((string) $request->input('user', ''), (string) $request->input('pass', ''), $request->boolean('remember'));
        if ($errors) {
            return redirect('/login.html')->withErrors($errors)->withInput($request->except('pass'));
        }
        $redto = (string) $request->input('redto', '/index.html');
        if (! str_starts_with($redto, '/') || str_starts_with($redto, '//') || str_contains($redto, 'logout')) {
            $redto = '/index.html';
        }

        return redirect($redto);
    }

    public function logout()
    {
        $this->session->logout();

        return redirect('/index.html');
    }

    public function forgot(Request $request)
    {
        if ($this->loggedIn()) {
            return redirect('/index.html');
        }
        $data = ['step' => 'form'];
        $user = (string) $request->input('user', '');
        $email = (string) $request->input('email', '');
        if ($request->input('subforgot') && $user !== '1') {
            $citZ = $this->database->row('SELECT * FROM citizens WHERE name = ? AND email = ?', [$user, $email]);
            if (! $citZ) {
                $data = ['step' => 'error', 'msg' => "Citizen name and Email don't match"];
            } elseif (! $citZ['secu_question']) {
                $data = ['step' => 'error', 'msg' => 'Sorry, you have entered no security question. You cannot recover your password.'];
            } else {
                $data = ['step' => 'question', 'citZ' => $citZ, 'user' => $user, 'email' => $email];
            }
        } elseif ($request->input('subforgot2') && $user !== '1') {
            $answer = (string) $request->input('secans', '');
            $citZ = $this->database->row('SELECT * FROM citizens WHERE name = ? AND email = ? AND secu_answer = ?', [$user, $email, $answer]);
            if (! $citZ) {
                $data = ['step' => 'error', 'msg' => 'Your answer is not correct'];
            } else {
                $newpass = $this->session->generateRandStr(8);
                $this->database->updateUserField($user, 'password', \Illuminate\Support\Facades\Hash::make($newpass));
                app(\App\Game\Services\Mailer::class)->sendNewPass($user, $email, $newpass);
                $data = ['step' => 'done', 'newpass' => $newpass];
            }
        }

        return $this->page('pages.forgotpass', $data, ['title' => 'Forgot Password', 'coltype' => 2, 'bar_title' => 'Forgot Password']);
    }

    /** activate-{id}.html — activation link from email. */
    public function activate(string $id)
    {
        $exists = $this->database->count("SELECT active FROM citizens WHERE actLink = ? AND accType = 'citizen' AND active = 0", [$id]);
        if (! $exists) {
            return redirect($this->vars->getURL('home'));
        }
        $this->database->exec("UPDATE citizens SET active = '1' WHERE actLink = ?", [$id]);
        if (config('ejahan.email.welcome')) {
            $row = $this->database->row('SELECT * FROM citizens WHERE actLink = ?', [$id]);
            if ($row) {
                $this->pays()->extendAcc($row['CitizenID'], 'pro', 1 / 3);
                $this->database->sendNote($row['CitizenID'], '', 'Your citizen account is upgraded to PRO account for 10 days FOR FREE because you activated your account!');
            }
        }

        return response('<h3>Account successfully activated! Now you can login! Redirecting to main page...</h3>'
            ."<script>location.href='".$this->vars->getURL('home')."'</script>");
    }

    /** activation-{id}.html — resend the activation mail. */
    public function activation(int $id)
    {
        $row = $this->database->row('SELECT actLink, active, name, email FROM citizens WHERE CitizenID = ?', [$id]);
        if ($row && config('ejahan.email.activation') && ! $row['active']) {
            app(\App\Game\Services\Mailer::class)->sendActivation($row['name'], $row['email'], $row['actLink']);
            $msg = "<h3 class=\"infHandle\">We've sent your activation link! Check ".e($row['email']).'...<br>Note: Emails sent by eJahan may appear in your inbox or spam/junks folder and it may take more than 10 minutes.</h3>';
        } else {
            $msg = '<h3 class="errHandle">This user is already activated</h3>';
        }

        return $this->page('pages.message', ['message' => $msg], ['title' => $this->lang->getstr('title_activation', 'title'), 'coltype' => 2]);
    }

    /** revive.html — wake a hibernated citizen. */
    public function revive()
    {
        if ($this->loggedIn() && $this->database->usernameHibernated($this->citID())) {
            $this->database->updateUserFieldID($this->citID(), 'wellness', 50);
            $this->database->updateUserFieldID($this->citID(), 'dDeath', null);
        }

        return redirect('/index.html');
    }
}
