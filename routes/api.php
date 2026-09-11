<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
 * JSON API v1 for the Flutter client. Token auth (Sanctum); the legacy game
 * services are reused, so the web UI and the app share one rule set.
 */
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [Api\AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'api.game'])->group(function () {
        Route::post('auth/logout', [Api\AuthController::class, 'logout']);
        Route::get('me', [Api\AuthController::class, 'me']);

        Route::get('home', [Api\HomeController::class, 'index']);
        Route::post('daily-reward', [Api\HomeController::class, 'dailyReward']);

        Route::get('army', [Api\ArmyController::class, 'index']);
        Route::post('army/train', [Api\ArmyController::class, 'train']);

        Route::get('work', [Api\WorkController::class, 'index']);
        Route::post('work', [Api\WorkController::class, 'work']);

        Route::get('battles', [Api\BattleController::class, 'index']);
        Route::get('battles/{id}', [Api\BattleController::class, 'show']);
        Route::post('battles/{id}/fight', [Api\BattleController::class, 'fight']);

        Route::get('mail/inbox', [Api\MailController::class, 'inbox']);
        Route::get('mail/sent', [Api\MailController::class, 'sent']);
        Route::get('mail/notes', [Api\MailController::class, 'notes']);
        Route::get('mail/{id}', [Api\MailController::class, 'show']);
        Route::post('mail/send', [Api\MailController::class, 'send']);
        Route::delete('mail/{id}', [Api\MailController::class, 'destroy']);

        Route::get('news', [Api\NewsController::class, 'index']);
        Route::get('articles/{id}', [Api\NewsController::class, 'show']);
        Route::post('articles/{id}/vote', [Api\NewsController::class, 'vote']);
        Route::post('articles/{id}/comments', [Api\NewsController::class, 'comment']);
    });
});
