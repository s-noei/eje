<?php

namespace App\Http\Middleware;

use App\Game\Services\GameContext;
use Closure;
use Illuminate\Http\Request;

/** Pages that only make sense for a logged-in citizen redirect guests home. */
class RequireCitizen
{
    public function __construct(protected GameContext $ctx)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $this->ctx->logged_in) {
            return redirect('/index.html');
        }

        return $next($request);
    }
}
