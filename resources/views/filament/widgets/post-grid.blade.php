<x-filament-widgets::widget class="fi-wi-post-grid">
    <x-filament::section
        :heading="__('cms.dashboard.post_grid.heading')"
        :description="__('cms.dashboard.post_grid.description')"
    >
        @if ($posts->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('cms.dashboard.post_grid.empty') }}
            </p>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a
                        href="{{ \App\Filament\Resources\Posts\PostResource::getUrl('edit', ['record' => $post]) }}"
                        class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:border-primary-300 dark:border-white/10 dark:bg-gray-900"
                    >
                        @if ($post->featuredImageUrl())
                            <img
                                src="{{ $post->featuredImageUrl() }}"
                                alt="{{ $post->featuredImage?->alt_text ?: $post->title }}"
                                class="h-40 w-full object-cover"
                            >
                        @else
                            <div class="flex h-40 w-full items-center justify-center bg-gray-100 text-xs font-medium uppercase tracking-wide text-gray-400 dark:bg-white/5 dark:text-gray-500">
                                {{ __('cms.dashboard.post_grid.no_image') }}
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                    {{ $post->status?->label() ?? __('cms.dashboard.post_grid.published') }}
                                </span>
                                @if ($post->post_type)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                        {{ $post->post_type }}
                                    </span>
                                @endif
                                @foreach ($post->categories->take(2) as $category)
                                    <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                            </div>

                            <h3 class="font-bold text-gray-950 dark:text-white">{{ $post->title }}</h3>
                            <p class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $post->resolvedExcerpt() }}</p>

                            <p class="mt-auto pt-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ trans_choice('cms.dashboard.post_grid.views', (int) $post->view_count, ['count' => number_format((int) $post->view_count)]) }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
