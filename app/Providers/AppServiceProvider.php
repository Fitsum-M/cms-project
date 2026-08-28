<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Listeners\Audit\LogAuthenticationEvents;
use App\Models\Category;
use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Models\Folder;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Tag;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CustomTaxonomyPolicy;
use App\Policies\CustomTaxonomyTermPolicy;
use App\Policies\FolderPolicy;
use App\Policies\MediaAssetPolicy;
use App\Policies\PagePolicy;
use App\Policies\PostPolicy;
use App\Policies\PostTypePolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Services\MediaReferences\ContentSeoOgImageMediaReferenceProvider;
use App\Services\MediaReferences\PostFeaturedImageMediaReferenceProvider;
use App\Services\MediaReferences\SeoDefaultsMediaReferenceProvider;
use App\Services\MediaReferenceService;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\CmsPassword;
use App\Support\Settings\EmailSettings;
use App\Support\Settings\GeneralSettings;
use App\Support\Settings\MediaSettings;
use App\Support\Settings\PermalinkSettings;
use App\Support\Settings\ReadingSettings;
use App\Support\Settings\SeoDefaultsSettings;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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

        $this->app->tag([
            SeoDefaultsMediaReferenceProvider::class,
            PostFeaturedImageMediaReferenceProvider::class,
            ContentSeoOgImageMediaReferenceProvider::class,
        ], 'media.reference_providers');

        $this->app->singleton(MediaReferenceService::class, function ($app): MediaReferenceService {
            return new MediaReferenceService($app->tagged('media.reference_providers'));
        });

        $this->app->singleton(AuditLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => CmsPassword::rules());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(CustomTaxonomy::class, CustomTaxonomyPolicy::class);
        Gate::policy(CustomTaxonomyTerm::class, CustomTaxonomyTermPolicy::class);
        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(PostType::class, PostTypePolicy::class);
        Gate::policy(Page::class, PagePolicy::class);

        try {
            $this->app->make(GeneralSettings::class)->applyRuntimeConfiguration();
            $this->app->make(EmailSettings::class)->applyRuntimeConfiguration();
        } catch (\Throwable) {
            // Settings table may not exist yet during install/migrate.
        }

        foreach ([
            storage_path('app/public'),
            storage_path('app/private/livewire-tmp'),
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }

        // GAP.S.01 — admin tables/infolists use General Settings date/time formats (§16.2, §18.10).
        FilamentTimezone::set(fn (): string => app(GeneralSettings::class)->timezone());

        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultDateDisplayFormat(fn (): string => app(GeneralSettings::class)->dateFormat())
                ->defaultDateTimeDisplayFormat(fn (): string => app(GeneralSettings::class)->dateTimeFormat())
                ->defaultTimeDisplayFormat(fn (): string => app(GeneralSettings::class)->timeFormat());
        });

        Schema::configureUsing(function (Schema $schema): void {
            $schema
                ->defaultDateDisplayFormat(fn (): string => app(GeneralSettings::class)->dateFormat())
                ->defaultDateTimeDisplayFormat(fn (): string => app(GeneralSettings::class)->dateTimeFormat())
                ->defaultTimeDisplayFormat(fn (): string => app(GeneralSettings::class)->timeFormat());
        });

        Event::listen(Login::class, [LogAuthenticationEvents::class, 'handleLogin']);
        Event::listen(Failed::class, [LogAuthenticationEvents::class, 'handleFailed']);

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
