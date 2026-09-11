<?php

namespace App\Http\Middleware;

use App\Game\Services\GameContext;
use App\Game\Services\GameDatabase;
use Closure;
use Illuminate\Http\Request;

/**
 * The per-citizen gates index.php applied before including a page:
 * banned → banned page (contact page still allowed), hibernated → revive page,
 * no security question → forced security question form.
 */
class CitizenGates
{
    public function __construct(protected GameContext $ctx, protected GameDatabase $database)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $this->ctx->logged_in) {
            return $next($request);
        }
        $cit = $this->ctx->userinfo;
        $citID = (int) $cit['CitizenID'];

        $banned = $this->database->usernameBanned($citID);
        if ($banned && ! $request->routeIs('contact*')) {
            return response()->view('gates.banned', ['baninf' => $cit + ['due' => $cit['ban_due']]]);
        }
        if ($this->database->usernameDead($citID)) {
            return response()->view('gates.dead');
        }
        if ($this->database->usernameHibernated($citID) && ! $request->routeIs('revive')) {
            return response()->view('gates.hibernated');
        }
        if (empty($cit['secu_question']) && ! $request->routeIs('logout')) {
            return app(\App\Http\Controllers\SecurityQuestionController::class)->show($request);
        }

        return $next($request);
    }
}
