@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Enums\IconSize;
    use Harvirsidhu\FilamentCards\View\Components\CardComponent;
    use Harvirsidhu\FilamentCards\View\Components\CardComponent\IconComponent;
    use Illuminate\View\ComponentAttributeBag;

    use function Filament\Support\generate_href_html;
    use function Filament\Support\generate_icon_html;
@endphp

@props([
    'item',
    'alignment' => Alignment::Center,
    'iconPosition' => IconPosition::Before,
    'iconSize' => IconSize::Medium,
    'isCompact' => false,
    'isIconInlined' => false,
    'isSearchable' => false,
    'searchContext' => null,
])

@php
    $isDisabled = $item->isDisabled();
    $hasUrl = $item->hasUrl();
    $itemColor = $item->getColor();
    $itemIcon = $item->getIcon();
    $itemLabel = $item->getLabel();
    $itemBadge = $item->getBadge();
    $itemDescription = $item->getDescription();
    $openInNewTab = $item->shouldOpenUrlInNewTab();
    $itemAlignment = $item->getAlignment() ?? $alignment;

    if (! $itemAlignment instanceof Alignment) {
        $itemAlignment = filled($itemAlignment) ? (Alignment::tryFrom($itemAlignment) ?? $itemAlignment) : Alignment::Center;
    }

    if (! $iconPosition instanceof IconPosition) {
        $iconPosition = filled($iconPosition) ? (IconPosition::tryFrom($iconPosition) ?? $iconPosition) : IconPosition::Before;
    }

    if (! $iconSize instanceof IconSize) {
        $iconSize = filled($iconSize) ? (IconSize::tryFrom($iconSize) ?? $iconSize) : IconSize::Medium;
    }

    // A card icon is the card's main visual anchor, not an inline glyph, so it
    // is sized on its own scale rather than Filament's `fi-size-*` one — that
    // tops out at 32px and puts Medium at 20px, half the 40px this package has
    // always used. The three documented sizes keep their original dimensions.
    $iconSizeClass = match ($iconSize) {
        IconSize::ExtraSmall => 'size-5',
        IconSize::Small => 'size-6',
        IconSize::Medium => 'size-10',
        IconSize::Large => 'size-12',
        IconSize::ExtraLarge => 'size-14',
        IconSize::TwoExtraLarge => 'size-16',
        default => 'size-10',
    };

    // A card without a destination used to render as an anchor to "#", which
    // looks clickable and goes nowhere. Only a card that is both enabled and
    // actually pointing somewhere becomes a link.
    $isInteractive = (! $isDisabled) && $hasUrl;

    $actions = $item->getActions();
    $hasActions = $actions !== [];

    // A <button> inside an <a> is invalid HTML and unreachable by keyboard, so
    // a card carrying actions keeps a plain <div> as its outer element and
    // stretches an overlay link across it instead. Without actions the whole
    // card stays a single anchor, which is simpler and needs no overlay.
    $isWrappedInLink = $isInteractive && ! $hasActions;
    $hasOverlayLink = $isInteractive && $hasActions;

    $hasColor = filled($itemColor);

    // Card colours go through Filament's own colour pipeline rather than a
    // hardcoded map of six names: `->color()` emits `fi-color fi-color-{name}`,
    // and Filament defines those custom properties for every colour registered
    // on the panel — so `->color('brand')` works without this package having
    // heard of "brand".
    $cardAttributes = (new ComponentAttributeBag)
        ->color(CardComponent::class, $itemColor)
        ->class([
            'fi-cards-card group relative flex flex-col gap-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
            'dark:bg-gray-900 dark:ring-white/10',
            'p-3' => $isCompact,
            'p-4' => ! $isCompact,
            'border-s-4 border-s-color-500 dark:border-s-color-400' => $hasColor,
            'items-start' => $itemAlignment === Alignment::Start,
            'items-center' => $itemAlignment === Alignment::Center,
            'items-end' => $itemAlignment === Alignment::End,
            'opacity-50 cursor-not-allowed' => $isDisabled,
            'transition duration-150 hover:shadow-md hover:ring-primary-500/25 dark:hover:ring-primary-400/25' => $isInteractive,
        ])
        // Filament's own grid macro: emits `--col-span-md` style custom
        // properties against `fi-grid-col`, which ships compiled in Filament's
        // CSS. Every breakpoint works, and `columnSpanFull()` resolves to
        // `1 / -1` rather than a Tailwind class that was never generated.
        ->gridColumn($item->getColumnSpan(), $item->getColumnStart())
        ->merge($item->getExtraAttributes(), escape: false);

    if ($isSearchable) {
        // Both the raw and the accent-folded text are stored so that a query
        // typed either way ("Praferenzen" or "Präferenzen") matches.
        $searchSource = trim(
            strip_tags((string) $itemLabel) . ' '
            . strip_tags((string) $itemDescription) . ' '
            . strip_tags((string) $itemBadge) . ' '
            . implode(' ', $item->getSearchKeywords()) . ' '
            // The group's own label is indexed against each of its cards, so
            // searching for "Billing" surfaces the cards in the Billing group
            // even when none of them says "Billing" itself.
            . strip_tags((string) $searchContext)
        );

        $searchText = trim(mb_strtolower($searchSource) . ' ' . mb_strtolower(\Illuminate\Support\Str::ascii($searchSource)));

        $cardAttributes = $cardAttributes->merge([
            'data-search-text' => $searchText,
            // No x-cloak: a card is visible until a query hides it, so it
            // must not flash out of existence while Alpine boots.
            'x-show' => 'matches($el)',
        ], escape: false);
    }

    if ($isDisabled) {
        $cardAttributes = $cardAttributes->merge(['aria-disabled' => 'true'], escape: false);
    }
@endphp

<{{ $isWrappedInLink ? 'a' : 'div' }}
    @if ($isWrappedInLink)
        {{ generate_href_html($item->getUrl(), $openInNewTab) }}
    @endif
    {{ $cardAttributes }}
>
    @if ($hasOverlayLink)
        {{-- Stretched link: covers the card so the whole surface stays
             clickable, while the action buttons sit above it. --}}
        <a
            {{ generate_href_html($item->getUrl(), $openInNewTab) }}
            class="absolute inset-0 z-0"
        >
            <span class="fi-sr-only">{{ $itemLabel }}</span>
        </a>
    @endif
    @if ($openInNewTab && $isInteractive)
        {{
            generate_icon_html(
                \Filament\Support\Icons\Heroicon::ArrowTopRightOnSquare,
                attributes: (new ComponentAttributeBag)->class([
                    'fi-cards-card-new-tab-icon absolute top-3 text-gray-400 dark:text-gray-500',
                    'end-3' => $itemAlignment !== Alignment::End,
                    'start-3' => $itemAlignment === Alignment::End,
                ]),
                size: IconSize::ExtraSmall,
            )
        }}
    @endif

    <div
        @class([
            'flex gap-2',
            'flex-col' => ! $isIconInlined && $iconPosition === IconPosition::Before,
            'flex-col-reverse' => ! $isIconInlined && $iconPosition === IconPosition::After,
            'flex-row items-center' => $isIconInlined && $iconPosition === IconPosition::Before,
            'flex-row-reverse items-center' => $isIconInlined && $iconPosition === IconPosition::After,
            'items-start' => ! $isIconInlined && $itemAlignment === Alignment::Start,
            'items-center' => ! $isIconInlined && $itemAlignment === Alignment::Center,
            'items-end' => ! $isIconInlined && $itemAlignment === Alignment::End,
        ])
    >
        @if (filled($itemIcon))
            {{
                generate_icon_html($itemIcon, attributes: (new ComponentAttributeBag)
                    ->color(IconComponent::class, $itemColor)
                    ->class([
                        // Tailwind emits utilities into a later cascade layer
                        // than Filament's components, so this wins over the
                        // size-5 that `fi-icon` applies.
                        'fi-cards-card-icon shrink-0 text-gray-400 dark:text-gray-500',
                        $iconSizeClass,
                        'transition duration-150' => $isInteractive,
                        // Written out per variant rather than interpolated: the
                        // old `"group-hover:{$class}"` produced
                        // `group-hover:text-x-500 dark:text-x-400`, which left
                        // the dark variant permanently tinted instead of only
                        // on hover — and Tailwind never generated either class.
                        'group-hover:text-color-500 dark:group-hover:text-color-400' => $isInteractive && $hasColor,
                        'group-hover:text-primary-500 dark:group-hover:text-primary-400' => $isInteractive && ! $hasColor,
                    ]), size: $iconSize)
            }}
        @endif

        <div
            @class([
                'flex w-full items-center gap-2',
                'justify-start' => $itemAlignment === Alignment::Start,
                'justify-center' => $itemAlignment === Alignment::Center,
                'justify-end' => $itemAlignment === Alignment::End,
                'justify-between' => $itemAlignment === Alignment::Justify,
            ])
        >
            <h3
                @class([
                    'fi-cards-card-label text-sm font-semibold text-gray-700 dark:text-gray-200',
                    'text-start' => $itemAlignment === Alignment::Start,
                    'text-center' => $itemAlignment === Alignment::Center,
                    'text-end' => $itemAlignment === Alignment::End,
                    'text-justify' => $itemAlignment === Alignment::Justify,
                ])
            >
                {{ $itemLabel }}
            </h3>

            @if (filled($itemBadge))
                <x-filament::badge :color="$item->getBadgeColor()" size="sm">
                    {{ $itemBadge }}
                </x-filament::badge>
            @endif
        </div>
    </div>

    @if (filled($itemDescription))
        <p
            @class([
                'fi-cards-card-description text-sm text-gray-500 dark:text-gray-400',
                'text-start' => $itemAlignment === Alignment::Start,
                'text-center' => $itemAlignment === Alignment::Center,
                'text-end' => $itemAlignment === Alignment::End,
                'text-justify' => $itemAlignment === Alignment::Justify,
            ])
        >
            {{ $itemDescription }}
        </p>
    @endif

    @if ($openInNewTab && $isInteractive)
        <span class="fi-sr-only">{{ __('filament-cards::cards.card.opens_in_new_tab') }}</span>
    @endif

    @if ($hasActions)
        <div
            @class([
                'fi-cards-card-actions relative z-10 mt-1 flex flex-wrap gap-2',
                'justify-start' => $itemAlignment === Alignment::Start,
                'justify-center' => $itemAlignment === Alignment::Center,
                'justify-end' => $itemAlignment === Alignment::End,
            ])
        >
            @foreach ($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    @endif
</{{ $isWrappedInLink ? 'a' : 'div' }}>
