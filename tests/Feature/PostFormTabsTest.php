<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\User;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostFormTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_create_post_has_exactly_two_tabs_with_required_structure(): void
    {
        $admin = $this->makeUser('Administrator');

        $component = Livewire::actingAs($admin)
            ->test(CreatePost::class)
            ->assertSee('Post Details')
            ->assertSee('SEO & Social')
            ->assertFormFieldExists('title')
            ->assertFormFieldExists('slug')
            ->assertFormFieldExists('body')
            ->assertFormFieldExists('excerpt')
            ->assertFormFieldExists('featured_image_id')
            ->assertFormFieldExists('post_type')
            ->assertFormFieldExists('status')
            ->assertFormFieldExists('visibility')
            ->assertFormFieldExists('author_id')
            ->assertFormFieldExists('published_at')
            ->assertFormFieldExists('category_ids')
            ->assertFormFieldExists('tag_ids')
            ->assertFormFieldExists('seo.meta_title')
            ->instance();

        $tabs = collect($component->form->getComponents())
            ->first(fn ($component): bool => $component instanceof Tabs);

        $this->assertInstanceOf(Tabs::class, $tabs);

        $tabComponents = $tabs->getChildSchema()->getComponents();
        $this->assertCount(2, $tabComponents);
        $this->assertInstanceOf(Tab::class, $tabComponents[0]);
        $this->assertInstanceOf(Tab::class, $tabComponents[1]);
        $this->assertSame('Post Details', $tabComponents[0]->getLabel());
        $this->assertSame('SEO & Social', $tabComponents[1]->getLabel());
        $this->assertSame('heroicon-o-document-text', $tabComponents[0]->getIcon());
        $this->assertSame('heroicon-o-globe-alt', $tabComponents[1]->getIcon());

        $detailsSections = $tabComponents[0]->getChildSchema()->getComponents();
        $this->assertCount(2, $detailsSections);
        $this->assertInstanceOf(Section::class, $detailsSections[0]);
        $this->assertInstanceOf(Section::class, $detailsSections[1]);
        $this->assertSame('Content', $detailsSections[0]->getHeading());
        $this->assertSame('Settings & Taxonomies', $detailsSections[1]->getHeading());

        $seoComponents = $tabComponents[1]->getChildSchema()->getComponents();
        $this->assertNotEmpty($seoComponents);
        $this->assertInstanceOf(Section::class, $seoComponents[0]);
        $this->assertSame('SEO & Metadata', $seoComponents[0]->getHeading());
    }

    private function makeUser(string $role): User
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
