<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FrontendPostGridComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
    }

    public function test_home_uses_post_grid_component_markup(): void
    {
        $author = User::factory()->create();

        Post::factory()->published()->create([
            'title' => 'Component Grid Post',
            'slug' => 'component-grid-post',
            'author_id' => $author->id,
            'excerpt' => 'Excerpt for the reusable card.',
            'post_type' => 'post',
        ]);

        $response = $this->get(route('frontend.blog'));

        $response->assertOk();
        $response->assertSee('Component Grid Post');
        $response->assertSee('Read more');
        $response->assertSee('grid-cols-1 md:grid-cols-2 lg:grid-cols-3', false);
    }

    public function test_post_grid_supports_two_three_and_four_column_layouts(): void
    {
        $author = User::factory()->create();
        $posts = collect([
            Post::factory()->published()->create([
                'title' => 'Column Layout Post',
                'author_id' => $author->id,
            ]),
        ]);

        $two = Blade::render('<x-post-grid :posts="$posts" :columns="2" />', ['posts' => $posts]);
        $three = Blade::render('<x-post-grid :posts="$posts" :columns="3" />', ['posts' => $posts]);
        $four = Blade::render('<x-post-grid :posts="$posts" :columns="4" />', ['posts' => $posts]);

        $this->assertStringContainsString('grid-cols-1 md:grid-cols-2', $two);
        $this->assertStringNotContainsString('lg:grid-cols-3', $two);
        $this->assertStringContainsString('grid-cols-1 md:grid-cols-2 lg:grid-cols-3', $three);
        $this->assertStringContainsString('grid-cols-1 md:grid-cols-2 lg:grid-cols-4', $four);
        $this->assertStringContainsString('Column Layout Post', $three);
    }
}
