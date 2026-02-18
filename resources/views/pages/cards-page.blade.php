@php
    use Filament\Support\Enums\IconSize;
    use Harvirsidhu\FilamentCards\Enums\Alignment;

    $iconSizeClass = match ($iconSize) {
        IconSize::Small => 'w-6 h-6',
        IconSize::Medium => 'w-10 h-10',
        IconSize::Large => 'w-14 h-14',
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
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        @foreach ($groups as $group)
            @php
                $groupLabel = $group->getLabel();
                $groupColumns = $group->getColumns() ?? $pageColumns;
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
                                <h4 class="text-sm font-medium tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $groupLabel }}
                                </h4>

                                @if (filled($groupDescription))
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
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
                            'grid grid-cols-1 gap-4',
                            'md:grid-cols-2' => $groupColumns >= 2,
                            'lg:grid-cols-3' => $groupColumns >= 3,
                            'xl:grid-cols-4' => $groupColumns >= 4,
                            'gap-3' => $isCompact,
                        ])
                    >
                        @foreach ($groupItems as $item)
                            @php
                                $isDisabled = $item->isDisabled();
                                $itemColor = $item->getColor();
                                $itemIcon = $item->getIcon();
                                $itemLabel = $item->getLabel();
                                $itemDescription = $item->getDescription();
                                $itemUrl = $item->getUrl();
                                $openInNewTab = $item->shouldOpenUrlInNewTab();
                                $columnSpan = $item->getColumnSpan();

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
                            @endphp

                            @if ($isDisabled)
                                <div
                                    {{ $item->getExtraAttributeBag()->class([
                                        'group relative flex flex-col gap-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
                                        'dark:bg-gray-900 dark:ring-white/10',
                                        'opacity-50 cursor-not-allowed',
                                        'border-l-4' => filled($borderColorClass),
                                        $borderColorClass,
                                        'p-3' => $isCompact,
                                        'p-4' => ! $isCompact,
                                        $spanClasses,
                                        'items-start' => $alignment === Alignment::Start,
                                        'items-center' => $alignment === Alignment::Center,
                                        'items-end' => $alignment === Alignment::End,
                                    ]) }}
                                >
                            @else
                                <a
                                    href="{{ $itemUrl }}"
                                    @if ($openInNewTab) target="_blank" @endif
                                    {{ $item->getExtraAttributeBag()->class([
                                        'group relative flex flex-col gap-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
                                        'dark:bg-gray-900 dark:ring-white/10',
                                        'transition duration-150 hover:shadow-md hover:ring-primary-500/25 dark:hover:ring-primary-400/25',
                                        'border-l-4' => filled($borderColorClass),
                                        $borderColorClass,
                                        'p-3' => $isCompact,
                                        'p-4' => ! $isCompact,
                                        $spanClasses,
                                        'items-start' => $alignment === Alignment::Start,
                                        'items-center' => $alignment === Alignment::Center,
                                        'items-end' => $alignment === Alignment::End,
                                    ]) }}
                                >
                            @endif

                                @if ($openInNewTab && ! $isDisabled)
                                    <x-filament::icon
                                        icon="heroicon-s-arrow-top-right-on-square"
                                        @class([
                                            'absolute top-3 h-3.5 w-3.5 text-gray-400 dark:text-gray-500',
                                            'right-3' => $alignment !== Alignment::End,
                                            'left-3' => $alignment === Alignment::End,
                                        ])
                                    />
                                @endif

                                <div @class([
                                    'flex gap-2',
                                    'flex-col' => ! $isIconInlined,
                                    'flex-row items-center' => $isIconInlined && $alignment !== Alignment::End,
                                    'flex-row-reverse items-center' => $isIconInlined && $alignment === Alignment::End,
                                    'items-start' => ! $isIconInlined && $alignment === Alignment::Start,
                                    'items-center' => ! $isIconInlined && $alignment === Alignment::Center,
                                    'items-end' => ! $isIconInlined && $alignment === Alignment::End,
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

                                    <h5 @class([
                                        'font-semibold text-sm text-gray-700 dark:text-gray-200',
                                        'text-start' => $alignment === Alignment::Start,
                                        'text-center' => $alignment === Alignment::Center,
                                        'text-end' => $alignment === Alignment::End,
                                    ])>
                                        {{ $itemLabel }}
                                    </h5>
                                </div>

                                @if (filled($itemDescription))
                                    <p @class([
                                        'text-xs text-gray-500 dark:text-gray-400',
                                        'text-start' => $alignment === Alignment::Start,
                                        'text-center' => $alignment === Alignment::Center,
                                        'text-end' => $alignment === Alignment::End,
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
</x-filament-panels::page>
