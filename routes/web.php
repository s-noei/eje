<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | eJahan routes — a 1:1 port of the legacy .htaccess rewrite rules.
 | Every page URL is "<slug>-<params>-<lang>.html"; {lang} is the 2-letter language.
 |--------------------------------------------------------------------------
 */
Route::pattern('lang', '[a-z]{2}');
Route::pattern('id', '[0-9]+');
Route::pattern('id2', '[0-9]+');
Route::pattern('page', '[0-9]+');

/* Home / auth (no language on some legacy URLs) */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/index.html', [HomeController::class, 'index'])->name('home');
Route::get('/index1.html', [HomeController::class, 'index']);
Route::match(['get', 'post'], '/index-{lang}.html', [HomeController::class, 'index'])->name('home')->middleware('gates');
Route::get('/login.html', [AuthController::class, 'loginForm'])->name('login');
Route::get('/login-{lang}.html', [AuthController::class, 'loginForm']);
Route::get('/activation-{id}-{lang}.html', [AuthController::class, 'activation']);
Route::post('/loginprocess.html', [AuthController::class, 'login']);
Route::get('/logout.html', [AuthController::class, 'logout'])->name('logout');
Route::match(['get', 'post'], '/forgotpass.html', [AuthController::class, 'forgot']);
Route::match(['get', 'post'], '/forgotpass-{lang}.html', [AuthController::class, 'forgot']);
Route::get('/activate-{code}.html', [AuthController::class, 'activate'])->where('code', '[a-f0-9]{32}');
Route::get('/activation-{id}.html', [AuthController::class, 'activation']);
Route::get('/revive.html', [AuthController::class, 'revive'])->name('revive');
Route::get('/revive-{lang}.html', [AuthController::class, 'revive'])->name('revive');

/* Register */
Route::post('/register-process.html', [RegisterController::class, 'process']);
Route::get('/register.html', [RegisterController::class, 'form']);
Route::get('/register-{lang}.html', [RegisterController::class, 'form']);
Route::get('/register-{referer}-{lang}.html', [RegisterController::class, 'form'])->where('referer', '[0-9]+|done|error');
Route::get('/register-{referer}.html', [RegisterController::class, 'form'])->where('referer', '[0-9]+|done|error');
Route::get('/referrer-{id}-{lang}.html', fn ($id) => redirect("/register-{$id}.html"));
Route::get('/referrer-{id}.html', fn ($id) => redirect("/register-{$id}.html"));

/* Profile */
Route::pattern('go', '[a-z][a-z0-9_\-]*');
Route::match(['get', 'post'], '/profile-{id}-{go}-{id2}-{lang}.html', [\App\Http\Controllers\ProfileController::class, 'show'])->where('id2', '[a-z0-9]+')->middleware('gates')->name('profile.sub2');
Route::match(['get', 'post'], '/profile-{id}-{go}-{lang}.html', [\App\Http\Controllers\ProfileController::class, 'show'])->middleware('gates')->name('profile.sub');
Route::match(['get', 'post'], '/profile-{id}-{lang}.html', [\App\Http\Controllers\ProfileController::class, 'show'])->middleware('gates')->name('profile');
Route::match(['get', 'post'], '/profile--{go}-{id2}-{lang}.html', fn ($go, $id2, $lang) => app(\App\Http\Controllers\ProfileController::class)->show(request(), null, $go, $id2))->where('id2', '[a-z0-9]+')->middleware('gates');
Route::match(['get', 'post'], '/profile--{go}-{lang}.html', fn ($go, $lang) => app(\App\Http\Controllers\ProfileController::class)->show(request(), null, $go, null))->middleware('gates');

/* Ajax / self endpoints (no language suffix) */
$ajax = \App\Http\Controllers\AjaxController::class;
Route::get('/regions-{id}-{type}.html', [$ajax, 'regions'])->where('type', '[01]');
Route::get('/regions-{id}.html', [$ajax, 'regions']);
Route::get('/region-{id}.gif', [$ajax, 'regionImage']);
Route::get('/juice-{id}-{token}.html', [$ajax, 'juice'])->where('token', '[a-f0-9]{32}');
Route::get('/friendship-{do}-{id}-{token}.html', [$ajax, 'friendAction'])->where(['do' => 'accept|reject', 'token' => '[a-f0-9]{32}']);
Route::get('/getevents-{id}.html', [$ajax, 'events']);
Route::get('/tasks-{what}.html', [$ajax, 'tasks'])->where('what', '[a-z]+');
Route::get('/clinic-{token}.html', [$ajax, 'clinic'])->where('token', '[a-f0-9]{32}');
Route::get('/getweap-{q}.html', [$ajax, 'getWeapon'])->where('q', '[0-5]');
Route::get('/buywp.html', [$ajax, 'buyWellnessPack']);
Route::match(['get', 'post'], '/getCBresult.html', [$ajax, 'chanceboxResult']);
Route::get('/getchat-{id}-{pr}.html', [$ajax, 'getChat'])->where('pr', '[01]');
Route::get('/getchat-{id}.html', [$ajax, 'getChat']);
Route::match(['get', 'post'], '/addchat.html', [$ajax, 'addChat']);
Route::get('/addchat-{message}.html', [$ajax, 'addChat']);
Route::get('/heroes-{battleID}.html', [$ajax, 'heroes'])->where('battleID', '[0-9]+');
Route::get('/ajaxstats-{battleID}.html', [$ajax, 'battleStats'])->where('battleID', '[0-9]+');
Route::get('/battle-{battleID}-log-{num}.html', [$ajax, 'battleLog'])->where(['battleID' => '[0-9]+', 'num' => '[0-9]+']);
Route::get('/battle-{battleID}-log.html', [$ajax, 'battleLog'])->where('battleID', '[0-9]+');
Route::get('/ajaxfight-{battleID}-{weap}-{for}-{token}.html', [$ajax, 'fight'])->where(['battleID' => '[0-9]+', 'weap' => '[0-5]', 'for' => '[a-z]*', 'token' => '[a-f0-9]{32}']);
Route::match(['get', 'post'], '/include/ajFunc.php', [$ajax, 'ajFunc']);
Route::match(['get', 'post'], '/ajax/func', [$ajax, 'ajFunc']);

/*
 | Helper: registers a page route both with and without the "-{lang}" suffix
 | (the legacy .htaccess had both variants for every page).
 */
function gameRoute(string $pattern, $action, array $where = [], array $middleware = ['gates'], array $methods = ['get', 'post'], ?string $name = null): void
{
    $r1 = Route::match($methods, "/{$pattern}-{lang}.html", $action)->where($where)->middleware($middleware);
    $r2 = Route::match($methods, "/{$pattern}.html", $action)->where($where)->middleware($middleware);
    if ($name) {
        $r1->name($name);
        $r2->name($name.'.nolang');
    }
}

/* Company */
$company = \App\Http\Controllers\CompanyController::class;
gameRoute('company-{id}-{go}', [$company, 'show'], ['go' => 'details|migrate|edit|finance|license|quality|sell|status|workers|workplace']);
gameRoute('company-{id}', [$company, 'show']);
gameRoute('company', [$company, 'index'], [], ['gates', 'citizen']);
gameRoute('workers-{id}', fn ($id) => redirect("/company-{$id}-workers.html"));
gameRoute('finance-{id}', fn ($id) => redirect("/company-{$id}-finance.html"));

/* Markets, items, create */
$market = \App\Http\Controllers\MarketController::class;
$items = \App\Http\Controllers\ItemsController::class;
gameRoute('jobs-{sID}-{cID}-{page}', [$market, 'jobs'], ['sID' => '[0-9]+', 'cID' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('jobs-{sID}-{cID}', [$market, 'jobs'], ['sID' => '[0-9]+', 'cID' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('jobs', [$market, 'jobs'], [], ['gates', 'citizen']);
gameRoute('market-{iID}-{sID}-{cID}', [$market, 'market'], ['iID' => '[0-9]*', 'sID' => '[0-9]*', 'cID' => '[0-9]*']);
gameRoute('market', [$market, 'market']);
Route::get('/ajax-market-view-{iID}-{qID}-{cID}.html', [$market, 'marketView'])->where(['iID' => '[0-9]+', 'qID' => '[0-5]', 'cID' => '[0-9]+']);
Route::post('/ajax-market-buy.html', [$market, 'marketBuy']);
gameRoute('exchange-my-{acc}', fn ($acc) => app($market)->exchange(request(), null, null, $acc, 'mine'), ['acc' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('exchange-my', fn () => app($market)->exchange(request(), null, null, null, 'mine'), [], ['gates', 'citizen']);
gameRoute('exchange-{sell}-{buy}-{acc}', [$market, 'exchange'], ['sell' => '[0-9]+', 'buy' => '[0-9]+', 'acc' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('exchange-{sell}-{buy}', [$market, 'exchange'], ['sell' => '[0-9]+', 'buy' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('exchange', [$market, 'exchange'], [], ['gates', 'citizen']);
gameRoute('imarket-my', fn () => app($market)->imarket(request(), null, null, 'my'), [], ['gates', 'citizen']);
gameRoute('imarket-{iID}-{sID}', [$market, 'imarket'], ['iID' => '[0-9]+', 'sID' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('imarket', [$market, 'imarket'], [], ['gates', 'citizen']);
gameRoute('company_market-{iID}-{cID}', [$market, 'cmarket'], ['iID' => '[0-9]*', 'cID' => '[0-9]*']);
gameRoute('company_market', [$market, 'cmarket']);
gameRoute('items', [$items, 'items'], [], ['gates', 'citizen']);
gameRoute('special', [$items, 'special'], [], ['gates', 'citizen']);
gameRoute('create-{what}', [$items, 'create'], ['what' => 'company|newspaper|party|unit'], ['gates', 'citizen']);
gameRoute('company-license-{id}', fn ($id) => redirect("/company-{$id}-license.html"));

/* Military */
$war = \App\Http\Controllers\WarController::class;
gameRoute('army', [$war, 'army'], [], ['gates', 'citizen']);
gameRoute('battle-{id}-stats', fn ($id) => redirect("/battle-{$id}.html"));
gameRoute('battle-{id}-{for}', [$war, 'battle'], ['for' => 'att|def']);
gameRoute('battle-{id}', [$war, 'battle']);
gameRoute('fight-{id}', [$war, 'battle']);
gameRoute('war-{id}-{go}', [$war, 'warInfo'], ['go' => 'finished|details']);
gameRoute('war-{id}', [$war, 'warInfo']);
gameRoute('wars-{inv}-{act}-{type}-{page}', [$war, 'wars'], ['inv' => '[0-9]+', 'act' => 'act|end|all', 'type' => 'war|rev|all']);
gameRoute('wars', [$war, 'wars']);

/* Military units */
$mu = \App\Http\Controllers\MilitaryUnitController::class;
gameRoute('military-unit-{id}', [$mu, 'show'], [], ['gates', 'citizen'], ['GET', 'POST']);
gameRoute('military-unit', [$mu, 'index'], [], ['gates', 'citizen']);

/* Country / regions / congress / laws */
$country = \App\Http\Controllers\CountryController::class;
gameRoute('country-{id}-congress-{page}', [$country, 'congressPage']);
gameRoute('congress-{id}-{page}', [$country, 'congressPage']);
gameRoute('congress-{id}', [$country, 'congressPage']);
gameRoute('country-{id}-{go}', [$country, 'show'], ['go' => 'society|economy|politics|military|congress|requests|cpcandidates']);
gameRoute('country-{id}', [$country, 'show']);
gameRoute('region-{id}', [$country, 'region']);
gameRoute('law-{id}-{what}', [$country, 'law'], ['id' => 'new|[0-9]+', 'what' => '[a-z]+'], ['gates', 'citizen']);
gameRoute('law-{id}', [$country, 'law'], ['id' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('laws', [$country, 'ejlaws']);

/* Party */
$party = \App\Http\Controllers\PartyController::class;
gameRoute('party-{id}-{go}-{id2}', [$party, 'show'], ['go' => 'members|ppCandidates|cgCandidates']);
gameRoute('party-{id}-{go}', [$party, 'show'], ['go' => 'members|ppCandidates|cgCandidates']);
gameRoute('party-{id}', [$party, 'show']);
gameRoute('party', [$party, 'index'], [], ['gates', 'citizen']);

/* Elections */
$elections = \App\Http\Controllers\ElectionController::class;
Route::match(['get', 'post'], '/election-{what}-{country}-{p3}-{year}-{month}-{lang}.html', [$elections, 'index'])->where(['what' => 'pp|cg', 'country' => '[0-9]+', 'p3' => '[0-9]+', 'year' => '[0-9]+', 'month' => '[0-9]+'])->middleware('gates');
Route::match(['get', 'post'], '/election-{what}-{country}-{p3}-{year}-{month}.html', [$elections, 'index'])->where(['what' => 'pp|cg', 'country' => '[0-9]+', 'p3' => '[0-9]+', 'year' => '[0-9]+', 'month' => '[0-9]+'])->middleware('gates');
Route::match(['get', 'post'], '/election-{what}-{country}-{p3}-{year}-{lang}.html', [$elections, 'index'])->where(['what' => 'cp', 'country' => '[0-9]+', 'p3' => '[0-9]+', 'year' => '[0-9]+'])->middleware('gates');
Route::match(['get', 'post'], '/election-{what}-{country}-{p3}-{year}.html', [$elections, 'index'])->where(['what' => 'cp', 'country' => '[0-9]+', 'p3' => '[0-9]+', 'year' => '[0-9]+'])->middleware('gates');
gameRoute('elections', [$elections, 'index']);

/* Newspapers, articles, media center */
$media = \App\Http\Controllers\MediaController::class;
gameRoute('newspaper-{id}-page-{page}', fn (\Illuminate\Http\Request $r, $id, $page) => app($media)->newspaper($r, (int) $id, null, (int) $page));
gameRoute('newspaper-{id}-{do}', [$media, 'newspaper'], ['do' => 'edit|sub']);
gameRoute('newspaper-{id}', [$media, 'newspaper']);
gameRoute('newspaper', [$media, 'newspaperIndex'], [], ['gates', 'citizen']);
gameRoute('article-new', [$media, 'articleNew'], [], ['gates', 'citizen']);
gameRoute('article-{id}-{go}', [$media, 'article'], ['go' => 'edit']);
gameRoute('article-{id}', [$media, 'article']);
gameRoute('media-{country}-{type}-{page}', [$media, 'media'], ['country' => '[0-9]+', 'type' => 'top|new|eve']);
gameRoute('media', [$media, 'media']);

/* Mailbox */
$mail = \App\Http\Controllers\MailController::class;
gameRoute('mail-{go}-{id}', [$mail, 'index'], ['go' => '[a-z_]+', 'id' => '[0-9]*'], ['gates', 'citizen']);
gameRoute('mail-{go}', [$mail, 'index'], ['go' => '[a-z_]+'], ['gates', 'citizen']);
gameRoute('mail', [$mail, 'index'], [], ['gates', 'citizen']);

/* Rankings, online tracker, search */
$ranking = \App\Http\Controllers\RankingController::class;
gameRoute('ranking-{what}-{page}-{p1}-{p2}', [$ranking, 'ranking'], ['what' => '[a-z]+', 'p1' => '[0-9]+', 'p2' => '[0-9]+']);
gameRoute('ranking-{what}-{page}-{p1}', [$ranking, 'ranking'], ['what' => '[a-z]+', 'p1' => '[0-9]*']);
gameRoute('ranking-{what}-{page}', [$ranking, 'ranking'], ['what' => '[a-z]+']);
gameRoute('ranking-{what}', [$ranking, 'ranking'], ['what' => '[a-z]+']);
gameRoute('ranking', [$ranking, 'ranking']);
gameRoute('online-{page}-{coun}', [$ranking, 'online'], ['coun' => '[0-9]+']);
gameRoute('online-{page}', [$ranking, 'online']);
gameRoute('online', [$ranking, 'online']);
gameRoute('search', [$ranking, 'search']);

/* Maps */
$map = \App\Http\Controllers\MapController::class;
Route::get('/map-{id}.gif', [$map, 'image'])->where('id', '[A-Z]{2}|[0-9]+');
Route::get('/map-{code}.html', [$map, 'country'])->where('code', '[A-Z]{2}');
gameRoute('map', [$map, 'index']);

/* Mines */
gameRoute('mines', [\App\Http\Controllers\MinesController::class, 'index'], [], ['gates', 'citizen']);

/* Chance boxes & lottery */
$cb = \App\Http\Controllers\ChanceboxController::class;
gameRoute('chancebox-{do}-{id}', [$cb, 'index'], ['do' => 'view|open|buy'], ['gates', 'citizen']);
gameRoute('chancebox-{do}', [$cb, 'index'], ['do' => 'view|open|buy'], ['gates', 'citizen']);
gameRoute('chancebox', [$cb, 'index'], [], ['gates', 'citizen']);
gameRoute('lottery-{go}-{day}', [$cb, 'lottery'], ['go' => 'results', 'day' => '[0-9]+'], ['gates', 'citizen']);
gameRoute('lottery-{go}', [$cb, 'lottery'], ['go' => 'results'], ['gates', 'citizen']);
gameRoute('lottery', [$cb, 'lottery'], [], ['gates', 'citizen']);

/* Store (PayPal) & mobile payments (PayGol) */
$store = \App\Http\Controllers\StoreController::class;
Route::match(['get', 'post'], '/ejstore-buyproc.html', [$store, 'paypalIpn']);
Route::match(['get', 'post'], '/ejstore-buyproc-{lang}.html', [$store, 'paypalIpn']);
Route::post('/ipn/paypal', [$store, 'paypalIpn']);
gameRoute('ejstore-{go}', [$store, 'index'], ['go' => 'my|process|success|failure'], ['gates', 'citizen']);
gameRoute('ejstore', [$store, 'index'], [], ['gates', 'citizen']);
Route::match(['get', 'post'], '/sms-smsback.html', fn (\Illuminate\Http\Request $r) => app($store)->sms($r, 'smsback'));
Route::match(['get', 'post'], '/sms-smsback-{lang}.html', fn (\Illuminate\Http\Request $r) => app($store)->sms($r, 'smsback'));
gameRoute('sms-{go}', [$store, 'sms'], ['go' => 'sms|smscancel|smsright'], ['gates', 'citizen']);
gameRoute('sms', [$store, 'sms'], [], ['gates', 'citizen']);

/* Ad center */
$ads = \App\Http\Controllers\AdCenterController::class;
gameRoute('ads-{go}-{id}', [$ads, 'index'], ['go' => 'add|edit|stop|start|click', 'id' => '[a-z0-9]+']);
gameRoute('ads-{go}', [$ads, 'index'], ['go' => 'add|edit|stop|start|click']);
gameRoute('ads', [$ads, 'index']);

/* Contact / tickets */
$contact = \App\Http\Controllers\ContactController::class;
gameRoute('contact-{go}-{id}', [$contact, 'index'], ['go' => 'new|track|view']);
gameRoute('contact-{go}', [$contact, 'index'], ['go' => 'new|track|view']);
gameRoute('contact', [$contact, 'index']);

/* Extra & invite */
$extra = \App\Http\Controllers\ExtraController::class;
gameRoute('extra-{go}', [$extra, 'extra'], ['go' => 'credits|history|emblems|devnews']);
gameRoute('extra', [$extra, 'extra']);
gameRoute('invite', [$extra, 'invite'], [], ['gates', 'citizen']);

/* Translation center */
$trans = \App\Http\Controllers\TransController::class;
gameRoute('trans-{do}', [$trans, 'index'], ['do' => 'view|team'], ['gates', 'citizen']);
gameRoute('trans', [$trans, 'index'], [], ['gates', 'citizen']);

/* Forum */
$forum = \App\Http\Controllers\ForumController::class;
gameRoute('forum-{go}-{id}-{page}', [$forum, 'index'], ['go' => '[a-z]+', 'page' => '[0-9]+|last']);
gameRoute('forum-{go}-{id}', [$forum, 'index'], ['go' => '[a-z]+']);
gameRoute('forum', [$forum, 'index']);

/* Xpand API */
$xpand = \App\Http\Controllers\XpandController::class;
Route::get('/xpand', [$xpand, 'index']);
Route::get('/xpand/index.html', [$xpand, 'index']);
Route::get('/xpand/region-{id}.html', [$xpand, 'regionJson']);
Route::get('/xpand/region-{id}.xml', [$xpand, 'regionXml']);
Route::get('/xpand/citizen-{id}.xml', [$xpand, 'citizenXml']);
Route::get('/xpand/citizen-{name}.html', [$xpand, 'citizenJson'])->where('name', '[^/]+');

/* GD images */
$img = \App\Http\Controllers\ImageController::class;
Route::get('/emblem-{id}.gif', [$img, 'emblem']);
Route::get('/license-back-{id}-{c}-{s}-{t}.gif', fn ($id, $c) => app($img)->licenseBack((int) $id, (int) $c))->where(['s' => '[0-9]+', 't' => '[^/]+']);
Route::get('/license-back-{id}-{c}.gif', [$img, 'licenseBack']);
