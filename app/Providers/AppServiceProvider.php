<?php

namespace App\Providers;

use App\View\Composers\GameLayoutComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', GameLayoutComposer::class);

        // {{ t('phrase', 'section') }} → translated string (unescaped, like the legacy getstr)
        Blade::directive('t', function ($expression) {
            return "<?php echo app(\\App\\Game\\Services\\Translator::class)->getstr({$expression}); ?>";
        });
        // @url('profile', $id) → pretty URL
        Blade::directive('url', function ($expression) {
            return "<?php echo app(\\App\\Game\\Support\\Vars::class)->getURL({$expression}); ?>";
        });
        // @fn($number) → localized digits
        Blade::directive('fn', function ($expression) {
            return "<?php echo app(\\App\\Game\\Support\\Vars::class)->formatnumbers({$expression}); ?>";
        });
    }
}
