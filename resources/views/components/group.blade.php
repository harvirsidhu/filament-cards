@php
    use Harvirsidhu\FilamentCards\Support\GridColumns;
    use Illuminate\View\ComponentAttributeBag;
@endphp

@props([
    'group',
    'pageColumns' => 3,
    'pageKey' => 'cards',
    'alignment' => null,
    'iconPosition' => null,
    'iconSize' => null,
    'isIconInlined' => false,
    'isSearchable' => false,
    'shouldPersistCollapsed' => false,
])

@php
    $groupLabel = $group->getLabel();
    $groupDescription = $group->getDescription();
    $groupIcon = $group->getIcon();
    $groupItems = $group->getItems();
    $isCollapsible = $group->isCollapsible();
    $isCollapsed = $group->isCollapsed();
    $isCompact = $group->isCompact();
    $isContained = $group->isContained();
    $hasHeading = filled($groupLabel);

    $persistCollapsed = $group->shouldPersistCollapsed() ?? $shouldPersistCollapsed;

    // Scoped by page as well as group so that two pages that both have a
    // "Billing" group do not share one stored collapse state.
    $collapseId = $pageKey . '-' . $group->getId();

    // Filament's grid macro — see Harvirsidhu\FilamentCards\Support\GridColumns
    // for why the classes are no longer built by string interpolation.
    $gridAttributes = (new ComponentAttributeBag)
        ->grid(GridColumns::normalize($group->getColumns() ?? $pageColumns))
        ->class([
            'fi-cards-group-grid',
            'gap-3' => $isCompact,
            'gap-4' => ! $isCompact,
        ]);

    // Built here rather than as `@if (...) x-show=... @endif` inside the
    // section's tag: Blade's component tag compiler parses the attribute list
    // itself and does not accept directives in it.
    $searchAttributes = (new ComponentAttributeBag)->merge(
        $isSearchable
            ? ['x-show' => 'groupMatches($el)']
            : [],
        escape: false,
    );

    // Passed as `:attributes` rather than echoed into the tag: Blade only
    // recognises attribute-bag spreading when the expression literally starts
    // with `$attributes`, so `{{ $searchAttributes->... }}` left the whole
    // opening tag uncompiled.
    $sectionAttributes = $searchAttributes
        ->class(['fi-cards-group'])
        ->merge($group->getExtraAttributes(), escape: false);

    $cardProps = [
        'alignment' => $alignment,
        'iconPosition' => $iconPosition,
        'iconSize' => $iconSize,
        'isCompact' => $isCompact,
        'isIconInlined' => $isIconInlined,
        'isSearchable' => $isSearchable,
        'searchContext' => $groupLabel,
    ];
@endphp

@if ($groupItems !== [])
    @if ($isContained)
        {{-- Delegate wholesale to Filament's section: it already solves
             collapsing, persistence, the collapse events and the heading
             semantics, and it keeps a contained group looking like every
             other panel section. --}}
        <x-filament::section
            :heading="$groupLabel"
            :description="$groupDescription"
            :icon="$groupIcon"
            :collapsible="$isCollapsible"
            :collapsed="$isCollapsed"
            :persist-collapsed="$persistCollapsed"
            :collapse-id="$collapseId"
            :compact="$isCompact"
            heading-tag="h2"
            :attributes="$sectionAttributes"
        >
            <div {{ $gridAttributes }}>
                @foreach ($groupItems as $item)
                    <x-harvirsidhu-filament-cards::card
                        :item="$item"
                        :alignment="$cardProps['alignment']"
                        :icon-position="$cardProps['iconPosition']"
                        :icon-size="$cardProps['iconSize']"
                        :is-compact="$cardProps['isCompact']"
                        :is-icon-inlined="$cardProps['isIconInlined']"
                        :is-searchable="$cardProps['isSearchable']"
                        :search-context="$cardProps['searchContext']"
                    />
                @endforeach
            </div>
        </x-filament::section>
    @else
        <div
            @if ($isCollapsible)
                x-data="{
                    isCollapsed: @if ($persistCollapsed) $persist(@js($isCollapsed)).as(@js('filament-cards-' . $collapseId . '-isCollapsed')) @else @js($isCollapsed) @endif,
                }"
                x-on:collapse-section.window="if ($event.detail.id === @js($collapseId)) isCollapsed = true"
                x-on:expand-section.window="if ($event.detail.id === @js($collapseId)) isCollapsed = false"
                x-on:toggle-section.window="if ($event.detail.id === @js($collapseId)) isCollapsed = ! isCollapsed"
            @endif
            @if ($isSearchable)
                x-show="groupMatches($el)"
            @endif
            {{ $group->getExtraAttributeBag()->class(['fi-cards-group space-y-3']) }}
        >
            @if ($hasHeading || filled($groupDescription) || $isCollapsible)
                <div
                    @class([
                        'fi-cards-group-header flex items-center gap-2',
                        'cursor-pointer select-none' => $isCollapsible,
                    ])
                    @if ($isCollapsible)
                        x-on:click="isCollapsed = ! isCollapsed"
                    @endif
                >
                    @if ($isCollapsible)
                        {{-- A real button, not a click handler on a div: this is
                             the only thing on the header a keyboard or screen
                             reader can reach, and it carries the expanded
                             state. Mirrors Filament's own collapsible section. --}}
                        <x-filament::icon-button
                            color="gray"
                            size="sm"
                            icon="heroicon-m-chevron-down"
                            :label="__('filament-cards::cards.group.toggle', ['group' => strip_tags((string) $groupLabel)])"
                            x-on:click.stop="isCollapsed = ! isCollapsed"
                            x-bind:aria-expanded="(! isCollapsed).toString()"
                            :aria-controls="$collapseId"
                            x-bind:class="isCollapsed && '-rotate-90 rtl:rotate-90'"
                            class="fi-cards-group-collapse-btn transition"
                        />
                    @endif

                    @if (filled($groupIcon))
                        {{
                            \Filament\Support\generate_icon_html($groupIcon, attributes: (new ComponentAttributeBag)
                                ->class(['fi-cards-group-icon text-gray-400 dark:text-gray-500']), size: \Filament\Support\Enums\IconSize::Small)
                        }}
                    @endif

                    <div>
                        @if ($hasHeading)
                            <h2 class="fi-cards-group-heading text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $groupLabel }}
                            </h2>
                        @endif

                        @if (filled($groupDescription))
                            <p class="fi-cards-group-description text-sm text-gray-400 dark:text-gray-500">
                                {{ $groupDescription }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <div
                id="{{ $collapseId }}"
                @if ($isCollapsible)
                    x-show="! isCollapsed"
                    x-collapse
                    @if ($isCollapsed || $persistCollapsed)
                        x-cloak
                    @endif
                @endif
                {{ $gridAttributes }}
            >
                @foreach ($groupItems as $item)
                    <x-harvirsidhu-filament-cards::card
                        :item="$item"
                        :alignment="$cardProps['alignment']"
                        :icon-position="$cardProps['iconPosition']"
                        :icon-size="$cardProps['iconSize']"
                        :is-compact="$cardProps['isCompact']"
                        :is-icon-inlined="$cardProps['isIconInlined']"
                        :is-searchable="$cardProps['isSearchable']"
                        :search-context="$cardProps['searchContext']"
                    />
                @endforeach
            </div>
        </div>
    @endif
@endif
