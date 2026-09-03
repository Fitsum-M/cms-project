<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\PostTypes\Pages\CreatePostType;
use App\Filament\Resources\PostTypes\Pages\EditPostType;
use App\Filament\Resources\PostTypes\Pages\ListPostTypes;
use App\Models\User;
use App\Services\PostService;
use App\Services\PostTypeService;
use App\Support\PostTypeRegistry;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostTypeRegistrationTest extends TestCase
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

    public function test_administrator_can_register_post_type_with_labels_slug_and_icon(): void
    {
        $admin = $this->makeUser('Administrator');

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'Case Studies',
            'singular_name' => 'Case Study',
            'slug' => 'case-studies',
            'icon' => 'heroicon-o-briefcase',
        ]);

        $this->assertSame('Case Studies', $type->plural_name);
        $this->assertSame('Case Study', $type->singular_name);
        $this->assertSame('case-studies', $type->slug);
        $this->assertSame('heroicon-o-briefcase', $type->icon);
        $this->assertTrue(PostTypeRegistry::isCustom('case-studies'));
        $this->assertSame('Case Studies', PostTypeRegistry::label('case-studies'));
        $this->assertArrayHasKey('case-studies', PostTypeRegistry::options());
    }

    public function test_slug_auto_generates_from_plural_name(): void
    {
        $admin = $this->makeUser('Administrator');

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'News Articles',
            'singular_name' => 'News Article',
        ]);

        $this->assertSame('news-articles', $type->slug);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(PostTypeService::class)->create([
            'plural_name' => 'Posts',
            'singular_name' => 'Post',
            'slug' => 'post',
        ]);
    }

    public function test_duplicate_slug_gets_numeric_suffix(): void
    {
        app(PostTypeService::class)->create([
            'plural_name' => 'Events',
            'singular_name' => 'Event',
            'slug' => 'events',
        ]);

        $second = app(PostTypeService::class)->create([
            'plural_name' => 'Events',
            'singular_name' => 'Event',
            'slug' => 'events',
        ]);

        $this->assertSame('events-2', $second->slug);
    }

    public function test_slug_change_cascades_to_existing_posts(): void
    {
        $admin = $this->makeUser('Administrator');

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'Guides',
            'singular_name' => 'Guide',
            'slug' => 'guides',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Getting Started',
            'post_type' => 'guides',
        ], $admin);

        app(PostTypeService::class)->update($type, [
            'slug' => 'how-to-guides',
        ]);

        $this->assertSame('how-to-guides', $post->fresh()->post_type);
        $this->assertSame('how-to-guides', $type->fresh()->slug);
    }

    public function test_cannot_delete_post_type_with_assigned_content(): void
    {
        $admin = $this->makeUser('Administrator');

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'Reports',
            'singular_name' => 'Report',
            'slug' => 'reports',
        ]);

        app(PostService::class)->create([
            'title' => 'Q1 Report',
            'post_type' => 'reports',
        ], $admin);

        $this->expectException(ValidationException::class);

        app(PostTypeService::class)->delete($type);
    }

    public function test_author_cannot_access_post_type_management(): void
    {
        $author = $this->makeUser('Author');
        $this->assertFalse($author->can(Permission::CustomPostTypesManage->value));

        $this->actingAs($author);

        Livewire::test(ListPostTypes::class)
            ->assertForbidden();
    }

    public function test_editor_can_create_via_filament(): void
    {
        $editor = $this->makeUser('Editor');
        $this->actingAs($editor);

        Livewire::test(CreatePostType::class)
            ->fillForm([
                'plural_name' => 'Case Studies',
                'singular_name' => 'Case Study',
                'slug' => 'case-studies',
                'icon' => 'heroicon-o-briefcase',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('post_types', [
            'slug' => 'case-studies',
            'plural_name' => 'Case Studies',
            'singular_name' => 'Case Study',
            'icon' => 'heroicon-o-briefcase',
        ]);
    }

    public function test_filament_edit_updates_labels(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
        ]);

        Livewire::test(EditPostType::class, ['record' => $type->getRouteKey()])
            ->fillForm([
                'plural_name' => 'Company News',
                'singular_name' => 'News Story',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Company News', $type->fresh()->plural_name);
        $this->assertSame('News Story', $type->fresh()->singular_name);
    }

    public function test_standard_post_remains_in_registry(): void
    {
        $this->assertSame('Posts (standard)', PostTypeRegistry::options()['post']);
        $this->assertContains('post', PostTypeRegistry::keys());
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
