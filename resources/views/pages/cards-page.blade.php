@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Enums\IconSize;
    use function Filament\Support\generate_href_html;

    $iconSizeClass = match ($iconSize) {
        IconSize::Small => 'w-6 h-6',
        IconSize::Medium => 'w-10 h-10',
        IconSize::Large => 'w-12 h-12',
        default => 'w-10 h-10',
    };

    $colorMap = [
        'primary' => 'border-primary-500 dark:border-primary-400',
        'success' => 'border-success-500 dark:border-success-400',
        'danger' => 'border-danger-500 dark:border-danger-400',
        'warning' => 'border-warning-500 dark:border-warning-400',
        'info' => 'border-info-500 dark:border-info-400',
        'gray' => 'border-gray-500 dark:border-gray-400',
    ];

    $iconColorMap = [
        'primary' => 'text-primary-500 dark:text-primary-400',
        'success' => 'text-success-500 dark:text-success-400',
        'danger' => 'text-danger-500 dark:text-danger-400',
        'warning' => 'text-warning-500 dark:text-warning-400',
        'info' => 'text-info-500 dark:text-info-400',
        'gray' => 'text-gray-500 dark:text-gray-400',
    ];

    /**
     * Build Tailwind grid classes from a widget-style columns config.
     *
     * Supports:
     * - int/string values with a progressive fallback (backward compatible)
     * - array values keyed by breakpoints: default, sm, md, lg, xl, 2xl
     */
    $resolveGridColumnClasses = function (int | string | array | null $columns): array {
        $parseColumnCount = static function (int | string | null $value): ?int {
            if (is_int($value)) {
                return max(1, min(12, $value));
            }

            if (is_string($value) && is_numeric($value)) {
                return max(1, min(12, (int) $value));
            }

            return null;
        };

        // Keep legacy behavior for scalar values: progressively increase by breakpoint.
        if (! is_array($columns)) {
            $count = $parseColumnCount($columns) ?? 3;
            $classes = ['grid-cols-1'];

            if ($count >= 2) {
                $classes[] = 'md:grid-cols-2';
            }

            if ($count >= 3) {
                $classes[] = 'lg:grid-cols-3';
            }

            if ($count >= 4) {
                $classes[] = 'xl:grid-cols-4';
            }

            if ($count >= 5) {
                $classes[] = "2xl:grid-cols-{$count}";
            }

            return $classes;
        }

        $breakpointPrefixes = [
            'default' => '',
            'sm' => 'sm:',
            'md' => 'md:',
            'lg' => 'lg:',
            'xl' => 'xl:',
            '2xl' => '2xl:',
        ];

        $classes = [];

        foreach ($breakpointPrefixes as $breakpoint => $prefix) {
            $columnCount = $parseColumnCount($columns[$breakpoint] ?? null);

            if ($columnCount === null) {
                continue;
            }

            $classes[] = "{$prefix}grid-cols-{$columnCount}";
        }

        if (empty($classes)) {
            return ['grid-cols-1'];
        }

        if (! array_key_exists('default', $columns)) {
            array_unshift($classes, 'grid-cols-1');
        }

        return array_values(array_unique($classes));
    };
@endphp

<x-filament-panels::page>
    <div @if ($isSearchable) x-data="{ q: '' }" @endif>
        @if ($isSearchable)
            <div class="flex justify-end mb-4">
                <div class="relative w-full sm:w-72">
                    <x-filament::input.wrapper
                        inline-prefix
                        prefix-icon="heroicon-m-magnifying-glass"
                    >
                        <x-filament::input
                            type="text"
                            inlinePrefix
                            autocomplete="off"
                            x-model.debounce.150ms="q"
                            placeholder="{{ $searchPlaceholder }}"
                            class="pe-9"
                        />
                    </x-filament::input.wrapper>

                    <button
                        type="button"
                        x-show="q.length > 0"
                        x-cloak
                        x-on:click="q = ''"
                        x-transition.opacity.duration.100ms
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        aria-label="Clear search"
                    >
                        <x-filament::icon
                            icon="heroicon-m-x-mark"
                            class="h-4 w-4"
                        />
                    </button>
                </div>
            </div>
        @endif

        <div class="space-y-6">
        @foreach ($groups as $group)
            @php
                $groupLabel = $group->getLabel();
                $groupColumns = $group->getColumns() ?? $pageColumns;
                $groupGridColumnClasses = $resolveGridColumnClasses($groupColumns);
                $groupItems = $group->getItems();
                $isCollapsible = $group->isCollapsible();
                $isCollapsed = $group->isCollapsed();
                $isCompact = $group->isCompact();
                $groupIcon = $group->getIcon();
                $groupDescription = $group->getDescription();
            @endphp

            @if (count($groupItems) > 0)
                <div
                    @if ($isCollapsible)
                        x-data="{ collapsed: {{ $isCollapsed ? 'true' : 'false' }} }"
                    @endif
                    @if ($isSearchable)
                        x-show="!q || [...$el.querySelectorAll('[data-search-text]')].some(el => el.dataset.searchText.includes(q.toLowerCase()))"
                    @endif
                    {{ $group->getExtraAttributeBag()->class(['space-y-3']) }}
                >
                    {{-- Group Header --}}
                    @if (filled($groupLabel))
                        <div
                            @if ($isCollapsible)
                                x-on:click="collapsed = ! collapsed"
                            @endif
                            @class([
                                'flex items-center gap-2',
                                'cursor-pointer select-none' => $isCollapsible,
                            ])
                        >
                            @if ($isCollapsible)
                                <x-filament::icon
                                    x-show="! collapsed"
                                    icon="heroicon-o-chevron-down"
                                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                                />
                                <x-filament::icon
                                    x-show="collapsed"
                                    icon="heroicon-o-chevron-right"
                                    class="h-4 w-4 text-gray-400 dark:text-gray-500"
                                />
                            @endif

                            @if (filled($groupIcon))
                                <x-filament::icon
                                    :icon="$groupIcon"
                                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                />
                            @endif

                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $groupLabel }}
                                </h4>

                                @if (filled($groupDescription))
                                    <p class="text-sm text-gray-400 dark:text-gray-500">
                                        {{ $groupDescription }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Cards Grid --}}
                    <div
                        @if ($isCollapsible)
                            x-show="! collapsed"
                            x-collapse
                        @endif
                        @class([
                            'grid',
                            ...$groupGridColumnClasses,
                            'gap-3' => $isCompact,
                            'gap-4' => ! $isCompact,
                        ])
                    >
                        @foreach ($groupItems as $item)
                            @php
                                $isDisabled = $item->isDisabled();
                                $itemColor = $item->getColor();
                                $itemIcon = $item->getIcon();
                                $itemLabel = $item->getLabel();
                                $itemBadge = $item->getBadge();
                                $itemBadgeColor = $item->getBadgeColor();
                                $itemDescription = $item->getDescription();
                                $itemUrl = $item->getUrl();
                                $openInNewTab = $item->shouldOpenUrlInNewTab();
                                $columnSpan = $item->getColumnSpan();
                                $itemAlignment = $item->getAlignment() ?? $alignment;

                                $borderColorClass = $itemColor ? ($colorMap[$itemColor] ?? '') : '';
                                $iconHoverColorClass = $itemColor
                                    ? ($iconColorMap[$itemColor] ?? 'text-primary-500 dark:text-primary-400')
                                    : '';

                                $spanClasses = '';
                                if (is_array($columnSpan)) {
                                    $defaultSpan = $columnSpan['default'] ?? null;
                                    $lgSpan = $columnSpan['lg'] ?? null;

                                    if ($defaultSpan === 'full') {
                                        $spanClasses = 'col-span-full';
                                    } elseif ($lgSpan === 'full') {
                                        $spanClasses = 'lg:col-span-full';
                                    } elseif ($lgSpan) {
                                        $spanClasses = 'lg:col-span-' . $lgSpan;
                                    }
                                }

                                $itemSearchText = $isSearchable
                                    ? strtolower(trim(strip_tags(
                                        (string) $itemLabel . ' '
                                        . (string) $itemDescription . ' '
                                        . (string) $itemBadge . ' '
                                        . implode(' ', $item->getSearchKeywords())
                                    )))
                                    : '';
                            @endphp

                            @if ($isDisabled)
                                <div
                                    @if ($isSearchable)
                                        data-search-text="{{ $itemSearchText }}"
                                        x-show="!q || $el.dataset.searchText.includes(q.toLowerCase())"
                                    @endif
                                    {{ $item->getExtraAttributeBag()->class([
                                        'group relative flex flex-col gap-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
                                        'dark:bg-gray-900 dark:ring-white/10',
                                        'opacity-50 cursor-not-allowed',
                                        'border-l-4' => filled($borderColorClass),
                                        $borderColorClass,
                                        'p-3' => $isCompact,
                                        'p-4' => ! $isCompact,
                                        $spanClasses,
                                        'items-start' => $itemAlignment === Alignment::Start,
                                        'items-center' => $itemAlignment === Alignment::Center,
                                        'items-end' => $itemAlignment === Alignment::End,
                                    ]) }}
                                >
                            @else
                                <a
                                    {{ generate_href_html($itemUrl, $openInNewTab) }}
                                    @if ($isSearchable)
                                        data-search-text="{{ $itemSearchText }}"
                                        x-show="!q || $el.dataset.searchText.includes(q.toLowerCase())"
                                    @endif
                                    {{ $item->getExtraAttributeBag()->class([
                                        'group relative flex flex-col gap-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
                                        'dark:bg-gray-900 dark:ring-white/10',
                                        'transition duration-150 hover:shadow-md hover:ring-primary-500/25 dark:hover:ring-primary-400/25',
                                        'border-l-4' => filled($borderColorClass),
                                        $borderColorClass,
                                        'p-3' => $isCompact,
                                        'p-4' => ! $isCompact,
                                        $spanClasses,
                                        'items-start' => $itemAlignment === Alignment::Start,
                                        'items-center' => $itemAlignment === Alignment::Center,
                                        'items-end' => $itemAlignment === Alignment::End,
                                    ]) }}
                                >
                            @endif

                                @if ($openInNewTab && ! $isDisabled)
                                    <x-filament::icon
                                        icon="heroicon-s-arrow-top-right-on-square"
                                        @class([
                                            'absolute top-3 h-3.5 w-3.5 text-gray-400 dark:text-gray-500',
                                            'right-3' => $itemAlignment !== Alignment::End,
                                            'left-3' => $itemAlignment === Alignment::End,
                                        ])
                                    />
                                @endif

                                <div @class([
                                    'flex gap-2',
                                    'flex-col' => ! $isIconInlined && $iconPosition === IconPosition::Before,
                                    'flex-col-reverse' => ! $isIconInlined && $iconPosition === IconPosition::After,
                                    'flex-row items-center' => $isIconInlined && $iconPosition === IconPosition::Before,
                                    'flex-row-reverse items-center' => $isIconInlined && $iconPosition === IconPosition::After,
                                    'items-start' => ! $isIconInlined && $itemAlignment === Alignment::Start,
                                    'items-center' => ! $isIconInlined && $itemAlignment === Alignment::Center,
                                    'items-end' => ! $isIconInlined && $itemAlignment === Alignment::End,
                                ])>
                                    @if (filled($itemIcon))
                                        <x-filament::icon
                                            :icon="$itemIcon"
                                            @class([
                                                $iconSizeClass,
                                                'text-gray-400 dark:text-gray-500',
                                                "group-hover:{$iconHoverColorClass}" => ! $isDisabled && filled($iconHoverColorClass),
                                                'group-hover:text-primary-500 dark:group-hover:text-primary-400' => ! $isDisabled && blank($iconHoverColorClass),
                                                'transition duration-150' => ! $isDisabled,
                                            ])
                                        />
                                    @endif

                                    <div @class([
                                        'flex w-full items-center gap-2',
                                        'justify-start' => $itemAlignment === Alignment::Start,
                                        'justify-center' => $itemAlignment === Alignment::Center,
                                        'justify-end' => $itemAlignment === Alignment::End,
                                        'justify-between' => $itemAlignment === Alignment::Justify,
                                    ])>
                                        <h5 @class([
                                            'font-semibold text-sm text-gray-700 dark:text-gray-200',
                                            'text-start' => $itemAlignment === Alignment::Start,
                                            'text-center' => $itemAlignment === Alignment::Center,
                                            'text-end' => $itemAlignment === Alignment::End,
                                            'text-justify' => $itemAlignment === Alignment::Justify,
                                        ])>
                                            {{ $itemLabel }}
                                        </h5>

                                        @if (filled($itemBadge))
                                            <x-filament::badge :color="$itemBadgeColor" size="sm">
                                                {{ $itemBadge }}
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($itemDescription))
                                    <p @class([
                                        'text-sm text-gray-500 dark:text-gray-400',
                                        'text-start' => $itemAlignment === Alignment::Start,
                                        'text-center' => $itemAlignment === Alignment::Center,
                                        'text-end' => $itemAlignment === Alignment::End,
                                        'text-justify' => $itemAlignment === Alignment::Justify,
                                    ])>
                                        {{ $itemDescription }}
                                    </p>
                                @endif

                            @if ($isDisabled)
                                </div>
                            @else
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
        </div>
    </div>
</x-filament-panels::page>
