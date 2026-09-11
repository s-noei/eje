<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Port of secu_question.php — forced until the citizen sets a security question. */
class SecurityQuestionController extends GameController
{
    public function show(Request $request)
    {
        $done = false;
        if ($request->input('subsecurity') && $request->isMethod('post')) {
            $q = strip_tags((string) $request->input('secques', ''));
            $a = strip_tags((string) $request->input('secans', ''));
            if ($q !== '' && $a !== '') {
                $this->database->updateUserFieldID($this->citID(), 'secu_question', $q);
                $this->database->updateUserFieldID($this->citID(), 'secu_answer', $a);
                $this->session->fillInfo(null, true);
                $done = true;
            }
        }

        return response($this->page('pages.secu-question', ['done' => $done, 'q' => $request->input('secques'), 'a' => $request->input('secans')],
            ['title' => 'Secure your account', 'bar_title' => 'Secure your account']));
    }
}
