<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class AuthController extends ApiController
{
    /** POST /api/v1/auth/login {user, pass} → token */
    public function login(Request $request)
    {
        $r = $this->session->verifyCredentials((string) $request->input('user'), (string) $request->input('pass'));
        if (isset($r['errors'])) {
            return $this->fail(implode(' ', $r['errors']), 401, ['fields' => array_map('strip_tags', $r['errors'])]);
        }
        $citizen = $r['citizen'];
        $token = $citizen->createToken((string) $request->input('device', 'mobile'))->plainTextToken;
        $this->session->fillInfo($citizen->getKey(), true);
        $this->session->CitID = $citizen->getKey();
        $this->session->logged_in = true;

        return $this->ok(['token' => $token, 'citizen' => $this->citizenPayload($this->cit(), true)]);
    }

    /** POST /api/v1/auth/logout */
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok();
    }

    /** GET /api/v1/me */
    public function me()
    {
        $c = $this->cit(true);

        return $this->ok(['citizen' => $this->citizenPayload($c, true), 'newPM' => $this->database->getNewMSGs($this->citID()), 'newNotes' => $this->database->getNewNotes($this->citID()), 'today' => $this->database->today]);
    }
}
