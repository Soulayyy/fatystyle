<?php

namespace App\Providers;

use App\Models\CreationCategory;
use App\Models\PageTranslation;
use App\Models\Service;
use App\Models\User;
use App\Observers\CreationCategoryObserver;
use App\Observers\PageTranslationObserver;
use App\Observers\ServiceObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CreationCategory::observe(CreationCategoryObserver::class);
        PageTranslation::observe(PageTranslationObserver::class);
        Service::observe(ServiceObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
