<?php

namespace App\Http\Middleware;

use App\Game\Services\GameContext;
use App\Game\Services\Translator;
use Closure;
use Illuminate\Http\Request;

/** Boots the legacy game context for token-authenticated (session-less) API requests. */
class ApiGameContext
{
    public function handle(Request $request, Closure $next)
    {
        app(Translator::class)->setLanguage($request->header('X-Lang', 'en'));
        app(GameContext::class)->boot();

        return $next($request);
    }
}
