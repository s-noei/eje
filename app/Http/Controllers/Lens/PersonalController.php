<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** lens/include/personal/* — moderator details + password change. */
class PersonalController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged()) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $msg = '';
        if ($request->input('subedit')) {
            $stored = (string) $this->mod['Password'];
            $oldOk = (strlen($stored) === 32 && ctype_xdigit($stored)) ? hash_equals($stored, md5((string) $request->input('oPass'))) : Hash::check((string) $request->input('oPass'), $stored);
            $n1 = (string) $request->input('nPass1');
            if (!$oldOk) {
                $msg = 'Error: Old password is not correct.';
            } elseif ($n1 !== (string) $request->input('nPass2')) {
                $msg = 'Error: Typed passwords do not match.';
            } elseif (strlen($n1) < 6) {
                $msg = 'Error: New password must be 6 characters or more.';
            } else {
                $db->exec('UPDATE lens_info SET Password = ? WHERE ModID = ?', [Hash::make($n1), $this->mod['ModID']]);
                $msg = 'Password changed successfully.';
            }
        }
        $replied = $db->count('SELECT post_id FROM ticket_posts WHERE by_name = ? GROUP BY ticket_id', [$this->mod['ModID']]);

        return $this->lensPage('lens.personal', ['msg' => $msg, 'replied' => $replied], 'Personal area', 'personal');
    }
}
