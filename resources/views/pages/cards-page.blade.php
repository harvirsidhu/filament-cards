<x-filament-panels::page>
    <div
        @if ($isSearchable)
            x-data="{
                query: '',

                /**
                 * Cards store both their raw and their accent-folded text, so
                 * matching either form is enough: 'Präferenzen' is findable
                 * whether or not the user types the umlaut.
                 */
                get needles() {
                    const query = this.query.trim().toLowerCase()

                    if (! query) {
                        return []
                    }

                    return [...new Set([
                        query,
                        query.normalize('NFD').replace(/[\u0300-\u036f]/g, ''),
                    ])]
                },

                matches(element) {
                    const needles = this.needles

                    if (! needles.length) {
                        return true
                    }

                    const haystack = element.dataset.searchText ?? ''

                    return needles.some((needle) => haystack.includes(needle))
                },

                groupMatches(element) {
                    if (! this.needles.length) {
                        return true
                    }

                    return [...element.querySelectorAll('[data-search-text]')].some((card) => this.matches(card))
                },

                get hasResults() {
                    if (! this.needles.length) {
                        return true
                    }

                    return [...this.$root.querySelectorAll('[data-search-text]')].some((card) => this.matches(card))
                },
            }"
        @endif
        class="fi-cards-page"
    >
        @if ($isSearchable)
            <div class="fi-cards-search mb-4 flex justify-end">
                <div class="relative w-full sm:w-72">
                    <label for="{{ $pageKey }}-search" class="fi-sr-only">
                        {{ $searchPlaceholder }}
                    </label>

                    <x-filament::input.wrapper
                        inline-prefix
                        prefix-icon="heroicon-m-magnifying-glass"
                    >
                        <x-filament::input
                            :id="$pageKey . '-search'"
                            type="search"
                            inlinePrefix
                            autocomplete="off"
                            x-ref="searchInput"
                            x-model.debounce.150ms="query"
                            :placeholder="$searchPlaceholder"
                            class="pe-9"
                        />
                    </x-filament::input.wrapper>

                    <button
                        type="button"
                        x-show="query.length > 0"
                        x-cloak
                        x-on:click="query = ''; $refs.searchInput?.focus()"
                        x-transition.opacity.duration.100ms
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <x-filament::icon
                            icon="heroicon-m-x-mark"
                            class="h-4 w-4"
                        />

                        <span class="fi-sr-only">
                            {{ __('filament-cards::cards.search.clear') }}
                        </span>
                    </button>
                </div>
            </div>

            {{-- Announced rather than shown: sighted users can see the grid
                 shrink, screen reader users otherwise get no feedback at all
                 when a keystroke changes what is on the page. --}}
            <div
                aria-live="polite"
                role="status"
                class="fi-sr-only"
                x-text="needles.length
                    ? @js(__('filament-cards::cards.search.results_announcement')).replace(
                        ':count',
                        [...$root.querySelectorAll('[data-search-text]')].filter((card) => matches(card)).length,
                    )
                    : ''"
            ></div>
        @endif

        <div class="fi-cards-groups space-y-6">
            @foreach ($groups as $group)
                <x-harvirsidhu-filament-cards::group
                    :group="$group"
                    :page-columns="$pageColumns"
                    :page-key="$pageKey"
                    :alignment="$alignment"
                    :icon-position="$iconPosition"
                    :icon-size="$iconSize"
                    :is-icon-inlined="$isIconInlined"
                    :is-searchable="$isSearchable"
                    :should-persist-collapsed="$shouldPersistCollapsed"
                />
            @endforeach
        </div>

        @if ($isSearchable)
            <div x-show="! hasResults" x-cloak class="fi-cards-empty-search mt-6">
                <x-filament::empty-state
                    icon="heroicon-o-magnifying-glass"
                    icon-color="gray"
                    :heading="__('filament-cards::cards.search.empty.heading')"
                    :description="__('filament-cards::cards.search.empty.description')"
                />
            </div>
        @endif

        @if ($groups->isEmpty())
            <x-filament::empty-state
                icon="heroicon-o-squares-2x2"
                icon-color="gray"
                :heading="__('filament-cards::cards.empty.heading')"
                :description="__('filament-cards::cards.empty.description')"
            />
        @endif
    </div>
</x-filament-panels::page>
