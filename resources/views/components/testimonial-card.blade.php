@props([
    'post',
])

<blockquote {{ $attributes->class('flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm') }}>
    @if ($post->customField('rating'))
        <p class="text-sm font-medium text-amber-600" aria-label="Rating {{ $post->customField('rating') }} out of 5">
            {{ str_repeat('★', (int) $post->customField('rating')) }}{{ str_repeat('☆', max(0, 5 - (int) $post->customField('rating'))) }}
        </p>
    @endif

    <p class="mt-3 flex-1 text-base leading-relaxed text-slate-700">
        “{{ $post->customField('quote') }}”
    </p>

    <footer class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-600">
        <cite class="not-italic font-semibold text-slate-900">{{ $post->customField('author_name') }}</cite>
        @if (filled($post->customField('author_role')) || filled($post->customField('company')))
            <span class="block text-slate-500">
                {{ collect([$post->customField('author_role'), $post->customField('company')])->filter()->implode(' · ') }}
            </span>
        @endif
        <a href="{{ route('frontend.posts.show', $post->slug) }}" class="mt-2 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-700">
            Read testimonial
        </a>
    </footer>
</blockquote>
