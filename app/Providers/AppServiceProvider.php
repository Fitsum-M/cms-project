<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Models\MediaAsset;
use App\Models\Tag;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CustomTaxonomyPolicy;
use App\Policies\CustomTaxonomyTermPolicy;
use App\Policies\MediaAssetPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Support\Settings\EmailSettings;
use App\Support\Settings\GeneralSettings;
use App\Support\Settings\MediaSettings;
use App\Support\Settings\PermalinkSettings;
use App\Support\Settings\ReadingSettings;
use App\Support\Settings\SeoDefaultsSettings;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeneralSettings::class);
        $this->app->singleton(ReadingSettings::class);
        $this->app->singleton(PermalinkSettings::class);
        $this->app->singleton(MediaSettings::class);
        $this->app->singleton(SeoDefaultsSettings::class);
        $this->app->singleton(EmailSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(CustomTaxonomy::class, CustomTaxonomyPolicy::class);
        Gate::policy(CustomTaxonomyTerm::class, CustomTaxonomyTermPolicy::class);
        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);

        try {
            $this->app->make(GeneralSettings::class)->applyRuntimeConfiguration();
            $this->app->make(EmailSettings::class)->applyRuntimeConfiguration();
        } catch (\Throwable) {
            // Settings table may not exist yet during install/migrate.
        }

        Event::listen(function (Validated $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            if ($user->isActive()) {
                return;
            }

            $message = match ($user->status) {
                UserStatus::PendingActivation => 'Activate your account using the invitation email before signing in.',
                UserStatus::Suspended => 'Your account has been suspended. Contact an administrator.',
                default => 'You cannot sign in with this account.',
            };

            throw ValidationException::withMessages([
                'email' => $message,
                'data.email' => $message,
            ]);
        });
    }
}
