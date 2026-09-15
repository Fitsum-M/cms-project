@php
    use App\Support\CustomFields\CustomFieldRegistry;
@endphp

@if (CustomFieldRegistry::hasSchema($post->post_type))
    <section class="mt-10 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Structured fields</h2>

        @switch($post->post_type)
            @case(CustomFieldRegistry::TEAM_MEMBERS)
                @php
                    $headshot = $post->customFieldMedia('headshot_id');
                    $socialLinks = $post->customField('social_links', []);
                @endphp
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Job Title / Position</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('job_title') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Committee / Department</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('department') ?: '—' }}</dd>
                    </div>
                </dl>
                @if (filled($post->customField('bio')))
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Bio</h3>
                        <p class="mt-2 whitespace-pre-line text-slate-700">{{ $post->customField('bio') }}</p>
                    </div>
                @endif
                @if ($headshot)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Headshot</h3>
                        <img src="{{ $headshot->previewUrl() ?? $headshot->originalUrl() }}" alt="{{ $headshot->alt_text ?? $post->title }}" class="mt-2 h-40 w-40 rounded-xl object-cover">
                    </div>
                @endif
                @if (is_array($socialLinks) && count($socialLinks) > 0)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Social Profile Links</h3>
                        <ul class="mt-2 space-y-1">
                            @foreach ($socialLinks as $link)
                                <li>
                                    <a href="{{ $link['url'] }}" class="text-blue-600 hover:text-blue-700" rel="noopener noreferrer" target="_blank">
                                        {{ $link['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @break

            @case(CustomFieldRegistry::SERVICES)
                @php
                    $icon = $post->customFieldMedia('icon_id');
                    $benefits = $post->customField('key_benefits', []);
                    $features = $post->customField('feature_highlights', []);
                @endphp
                @if ($icon)
                    <img src="{{ $icon->previewUrl() ?? $icon->originalUrl() }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                @endif
                @if (is_array($benefits) && count($benefits) > 0)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Key Benefits</h3>
                        <ul class="mt-2 list-disc space-y-1 ps-5 text-slate-700">
                            @foreach ($benefits as $benefit)
                                <li>{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (is_array($features) && count($features) > 0)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Feature Highlights</h3>
                        <ul class="mt-2 list-disc space-y-1 ps-5 text-slate-700">
                            @foreach ($features as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (filled($post->customField('eligibility')))
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Eligibility / Requirements</h3>
                        <p class="mt-2 whitespace-pre-line text-slate-700">{{ $post->customField('eligibility') }}</p>
                    </div>
                @endif
                @if (filled($post->customField('cta_text')) && filled($post->customField('cta_url')))
                    <a href="{{ $post->customField('cta_url') }}" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        {{ $post->customField('cta_text') }}
                    </a>
                @endif
                @break

            @case(CustomFieldRegistry::PRODUCTS)
                @php
                    $specs = $post->customField('specifications', []);
                    $gallery = $post->customFieldMediaMany('gallery_ids');
                @endphp
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('sku') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pricing</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('price') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Availability</dt>
                        <dd class="mt-1 text-slate-800">{{ str_replace('_', ' ', (string) ($post->customField('availability') ?: '—')) }}</dd>
                    </div>
                </dl>
                @if (is_array($specs) && count($specs) > 0)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Specifications</h3>
                        <dl class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200">
                            @foreach ($specs as $spec)
                                <div class="grid grid-cols-2 gap-2 px-3 py-2 text-sm">
                                    <dt class="font-medium text-slate-600">{{ $spec['label'] }}</dt>
                                    <dd class="text-slate-800">{{ $spec['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
                @if ($gallery !== [])
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">Image Gallery</h3>
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($gallery as $image)
                                <img src="{{ $image->previewUrl() ?? $image->originalUrl() }}" alt="{{ $image->alt_text ?? $post->title }}" class="aspect-square w-full rounded-lg object-cover">
                            @endforeach
                        </div>
                    </div>
                @endif
                @if (filled($post->customField('brochure_url')))
                    <a href="{{ $post->customField('brochure_url') }}" class="inline-flex text-sm font-semibold text-blue-600 hover:text-blue-700" rel="noopener noreferrer" target="_blank">
                        Download brochure / datasheet
                    </a>
                @endif
                @break

            @case(CustomFieldRegistry::TESTIMONIALS)
                <blockquote class="border-s-4 border-blue-500 ps-4 text-lg text-slate-800">
                    “{{ $post->customField('quote') }}”
                </blockquote>
                <p class="text-sm text-slate-600">
                    — {{ $post->customField('author_name') }}
                    @if (filled($post->customField('author_role')))
                        , {{ $post->customField('author_role') }}
                    @endif
                    @if (filled($post->customField('company')))
                        ({{ $post->customField('company') }})
                    @endif
                </p>
                @if ($post->customField('rating'))
                    <p class="text-sm font-medium text-amber-600">Rating: {{ $post->customField('rating') }}/5</p>
                @endif
                @break

            @case(CustomFieldRegistry::STATS)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Value</dt>
                        <dd class="mt-1 text-2xl font-semibold text-slate-900">{{ $post->customField('value') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Label</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('label') ?: '—' }}</dd>
                    </div>
                </dl>
                @break

            @case(CustomFieldRegistry::IMPACT_STORIES)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Issuer</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('issuer') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Year</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('year') ?: '—' }}</dd>
                    </div>
                </dl>
                @if (filled($post->customField('summary')))
                    <p class="text-slate-700">{{ $post->customField('summary') }}</p>
                @endif
                @break

            @case(CustomFieldRegistry::RESOURCES)
                <p class="text-sm text-slate-600">{{ $post->customField('category') }} · {{ $post->customField('resource_type') }}</p>
                @if (filled($post->customField('file_url')))
                    <a href="{{ $post->customField('file_url') }}" class="inline-flex font-semibold text-blue-600" target="_blank" rel="noopener noreferrer">
                        {{ $post->customField('cta_text') ?: 'Download' }}
                    </a>
                @endif
                @break

            @case(CustomFieldRegistry::FAQS)
                <p class="whitespace-pre-line text-slate-700">{{ $post->customField('answer') }}</p>
                @break

            @case(CustomFieldRegistry::MEMBER_SACCOS)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Location</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('location') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Membership</dt>
                        <dd class="mt-1 text-slate-800">{{ $post->customField('membership_label') ?: '—' }}</dd>
                    </div>
                </dl>
                @break

            @case(CustomFieldRegistry::JOIN_STEPS)
                <p class="text-sm font-semibold text-slate-500">Step {{ $post->customField('step_number') }}</p>
                <p class="mt-2 whitespace-pre-line text-slate-700">{{ $post->customField('summary') }}</p>
                @break
        @endswitch
    </section>
@endif
