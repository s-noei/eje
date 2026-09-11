<?php

namespace App\Http\Middleware;

use App\Game\Support\InputFilter;
use Closure;
use Illuminate\Http\Request;

/**
 * Port of include/security.php: every request value goes through the HTML/XSS
 * InputFilter with the game's allowed tag/attribute lists. Password fields are
 * left untouched.
 */
class SanitizeInput
{
    private const SKIP = ['pass', 'pass2', 'password', 'curpass', 'newpass', '_token'];

    public function handle(Request $request, Closure $next)
    {
        $filter = InputFilter::defaults();
        $clean = [];
        foreach ($request->all() as $k => $v) {
            if (in_array($k, self::SKIP, true) || $v instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }
            $clean[$k] = $filter->process($v);
        }
        $request->merge($clean);

        return $next($request);
    }
}
