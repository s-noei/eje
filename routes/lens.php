<?php

use Illuminate\Support\Facades\Route;

/*
 * eJahan LENS moderation panel (port of /lens). URL scheme mirrors lens/htaccess.txt:
 * /lens/{action}.html, /lens/{action}-{type}.html, /lens/{action}-{type}-{id}.html,
 * /lens/{action}-{type}-{id}-{page}.html, /lens/{action}-{type}-{id}-{id2}-{page}.html
 */
$L = 'App\\Http\\Controllers\\Lens\\';
$sections = [
    'citizen' => $L.'CitizenController',
    'company' => $L.'CompanyController',
    'election' => $L.'ElectionController',
    'ip' => $L.'IpController',
    'multi' => $L.'MultiController',
    'trans' => $L.'TransController',
    'tickets' => $L.'TicketsController',
    'payment' => $L.'PaymentController',
    'personal' => $L.'PersonalController',
    'modmgr' => $L.'ModmgrController',
    'ads' => $L.'AdsController',
];

Route::prefix('lens')->group(function () use ($L, $sections) {
    Route::get('/', fn () => redirect('/lens/index.html'));
    Route::match(['get', 'post'], '/index.html', [$L.'HomeController', 'index']);
    Route::match(['get', 'post'], '/login.html', [$L.'HomeController', 'login']);
    Route::get('/logout.html', [$L.'HomeController', 'logout']);
    Route::get('/about.html', [$L.'HomeController', 'about']);
    foreach ($sections as $name => $controller) {
        Route::match(['get', 'post'], "/{$name}.html", [$controller, 'index']);
        Route::match(['get', 'post'], "/{$name}-{type}.html", [$controller, 'index'])->where('type', '[a-z]+');
        Route::match(['get', 'post'], "/{$name}-{type}-{id}.html", [$controller, 'index'])->where('type', '[a-z]+')->where('id', '[^/]+');
        Route::match(['get', 'post'], "/{$name}-{type}-{id}-{page}.html", [$controller, 'index'])->where('type', '[a-z]+')->where('id', '[^/-]+')->where('page', '[0-9]+');
        Route::match(['get', 'post'], "/{$name}-{type}-{id}-{id2}-{page}.html", [$controller, 'index'])->where('type', '[a-z]+')->where('id', '[0-9]+')->where('id2', '[0-9]+')->where('page', '[0-9]+');
    }
});
