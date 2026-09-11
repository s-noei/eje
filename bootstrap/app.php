<?php

use App\Http\Middleware\CitizenGates;
use App\Http\Middleware\GameSession;
use App\Http\Middleware\RequireCitizen;
use App\Http\Middleware\SanitizeInput;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/lens.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SanitizeInput::class, GameSession::class]);
        $middleware->alias([
            'gates' => CitizenGates::class,
            'api.game' => \App\Http\Middleware\ApiGameContext::class,
            'citizen' => RequireCitizen::class,
        ]);
        // Legacy forms post without CSRF tokens from many JS helpers; keep CSRF for the
        // classic forms but exempt the ajax/legacy endpoints that are token-protected themselves.
        $middleware->validateCsrfTokens(except: [
            'ajax/*', 'loginprocess.html', 'register-process.html', 'getCBresult.html', 'buywp.html', 'addchat*',
            'ipn/*', 'sms/*', 'sms-smsback*', 'ejstore-buyproc*', 'xpand/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
