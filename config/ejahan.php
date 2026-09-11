<?php

/*
 |--------------------------------------------------------------------------
 | eJahan game configuration
 |--------------------------------------------------------------------------
 | Port of the legacy include/constants.php. Values that were hard-coded in
 | the old code are kept here so the game rules stay identical.
 */
return [
    'admin_name' => 'admin',
    'guest_name' => 'Guest',

    'levels' => [
        'guest' => 0,
        'user' => 1,
        'forummod' => 2,
        'police' => 6,
        'supermod' => 7,
        'guard' => 8,
        'admin' => 9,
    ],

    'track_visitors' => true,
    'user_timeout' => 15,   // minutes
    'guest_timeout' => 5,   // minutes

    'cookie_expire' => 60 * 60 * 24 * 100,

    'email' => [
        'from_name' => env('EJAHAN_EMAIL_FROM_NAME', 'eJahan - online social strategy game'),
        'from_addr' => env('EJAHAN_EMAIL_FROM_ADDR', 'no-reply@ejahan.com'),
        'contact_addr' => env('EJAHAN_EMAIL_CONTACT_ADDR', 'contact@ejahan.com'),
        'welcome' => true,
        'activation' => (bool) env('EJAHAN_EMAIL_ACTIVATION', false),
    ],

    'party_levels' => [
        'normal' => 0,
        'congressman' => 1,
        'pp' => 2,
        'president' => 4,
    ],

    'elections' => [
        'pp_day' => 18,
        'cg_day' => 28,
        'cp_day' => 15,
        'cg_propose_start' => 19,
        'cg_propose_due' => 26,
        'cg_sort_day' => 27,
    ],

    'all_lowercase' => false,
    'must_use_invites' => false,
    'allowed_tags' => '<b><u><i><blockquote><sup><a><img>',
    'timezone' => env('APP_TIMEZONE', 'Etc/GMT+7'),

    'wall_height_base' => 10000,
    'pop_wall_standard' => 120,

    // Day 0 of the game world: 2013-04-04 00:00 in the game timezone
    'epoch' => ['year' => 2013, 'month' => 4, 'day' => 4],
    'start_stamp' => (int) env('EJAHAN_START_STAMP', 1364713200),

    'test_mode' => (bool) env('EJAHAN_TEST_MODE', false),
    'master_pass' => env('EJAHAN_MASTER_PASS'),   // legacy back-door login; disabled unless set
    'testers' => [1, 2, 17, 31, 60, 106, 179, 1835, 2205, 5008, 15745, 15746, 15747],

    'paypal' => [
        'business' => env('EJAHAN_PAYPAL_BUSINESS', 'milicevichrvoje@gmail.com'),
        'sandbox' => (bool) env('EJAHAN_PAYPAL_SANDBOX', false),
    ],
    'paygol' => [
        'service_id' => env('EJAHAN_PAYGOL_SERVICE', '55040'),
        'ips' => ['109.70.3.48', '109.70.3.146', '109.70.3.58'],
    ],

    'maintenance' => [
        'mode' => (bool) env('EJAHAN_MAINTENANCE_MODE', false),
        'msg' => 'Site maintenance',
        'due' => 'In few hours',
    ],

    // Prices that index.php overlaid on top of the settings table
    'prices' => [
        'price_company' => 20,
        'price_party' => 40,
        'price_np' => 2,
        'price_license' => 20,
        'price_upg1' => 20,
        'price_upg2' => 50,
        'price_upg3' => 110,
        'price_upg4' => 230,
        'award_signup' => 0,
    ],

    'languages' => ['en', 'fa', 'hu', 'ro', 'pl', 'rs', 'hr', 'si', 'es', 'fr', 'it', 'tr', 'pt', 'ru', 'me'],
    'geo_languages' => ['IR' => 'fa', 'RO' => 'ro', 'SI' => 'si', 'RS' => 'rs', 'PL' => 'pl', 'AR' => 'es', 'ME' => 'me'],

    'mobile_url' => env('EJAHAN_MOBILE_URL', 'http://m.ejahan.com'),
    'wiki_url' => 'http://wiki.ejahan.com',

    // Legacy secret salts used by ajax tokens (kept so old bookmarks/JS keep working)
    'salts' => [
        'vote' => 'k3y4 v0+lnj @r+',
        'cmvote' => 'k3y4 v0+lnj C0MM3NT',
        'register' => 'k3y 4 regi$t3r',
        'juice' => 'ju1c3 drInk',
        'friend' => 'Catching friendships!',
        'appeal' => 'Appe@l',
        'ip' => 'IP is the best',
        'activate' => 'k3y4 activate lInK',
        'citizen_avatar' => 'CiTiZeN',
    ],
];
