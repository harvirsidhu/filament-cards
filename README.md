# Filament Cards

A Filament-native plugin that turns your pages and resources into a card-based navigation hub. Built to feel like it belongs in Filament's core -- uses the same API patterns (`label`, `schema`, `columnSpan`, `visible`/`hidden`) and integrates natively with Clusters and Resources.

**Best used as a Cluster or Resource front page** that auto-discovers child pages and resources, respecting Filament's existing navigation configuration. Also works standalone as a general-purpose settings hub.

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament v4 or v5

## Installation

Install via Composer:

```bash
composer require harvirsidhu/filament-cards
```

### Theme Setup

Since the plugin uses Tailwind CSS classes, add the plugin's views to your theme.

**For Filament v4.x / v5.x**, add this line to your `theme.css`:

```css
@source '../../../../vendor/harvirsidhu/filament-cards/resources/views';
```

Then rebuild your assets:

```bash
npm run build
```

## Quick Start

The simplest possible cards page in 10 lines:

```php
namespace App\Filament\Pages;

use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Harvirsidhu\FilamentCards\CardItem;

class ControlPanel extends CardsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static function getCards(): array
    {
        return [
            CardItem::make(CompanySettings::class),
            CardItem::make(BillingSettings::class),
        ];
    }
}
```

## Using with Clusters (Primary Use Case)

The most powerful way to use this plugin is as the **front page of a Cluster**. Auto-discovery reads all pages and resources in the cluster and creates cards automatically.

### Step 1: Define the Cluster

```php
namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
}
```

### Step 2: Create the CardsPage

```php
namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class SettingsHub extends CardsPage
{
    protected static ?string $cluster = Settings::class;
    protected static ?int $navigationSort = -1;

    protected static function getCards(): array
    {
        return static::discoverClusterCards();
    }
}
```

### Step 3: Your Cluster Pages Work as Normal

No extra traits or changes needed on any of your pages:

```php
namespace App\Filament\Clusters\Settings\Pages;

use Filament\Pages\Page;
use App\Filament\Clusters\Settings;

class CompanySettings extends Page
{
    protected static ?string $cluster = Settings::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
}
```

**Breadcrumbs work automatically:** `Dashboard > Settings > Company Settings`. Filament's cluster breadcrumb system handles everything -- no custom traits needed.

### What `discoverClusterCards()` Does

1. Reads all pages and resources registered to the Cluster
2. Excludes the CardsPage itself
3. Checks `canAccess()` on each component (respects authorization)
4. Uses each page's `$navigationLabel`, `$navigationIcon`, and URL
5. Groups cards by `$navigationGroup` into `CardGroup` objects
6. Sorts by `$navigationSort`

### Mixing Auto-Discovered and Manual Cards

```php
protected static function getCards(): array
{
    return [
        ...static::discoverClusterCards(),

        CardGroup::make('External Links')
            ->schema([
                CardItem::make('https://docs.example.com')
                    ->label('Documentation')
                    ->icon('heroicon-o-book-open')
                    ->openUrlInNewTab(),
            ]),
    ];
}
```

## Using with Resources

When a Resource has many custom pages, use `discoverResourceCards()` to auto-create cards for each:

```php
namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class UserSettingsHub extends CardsPage
{
    protected static string $resource = UserResource::class;

    protected static function getCards(): array
    {
        return static::discoverResourceCards();
    }
}
```

## Using Standalone

Without a Cluster or Resource, define cards manually:

```php
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class ControlPanel extends CardsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static function getCards(): array
    {
        return [
            CardGroup::make('General')
                ->icon('heroicon-o-cog')
                ->description('Core settings')
                ->schema([
                    CardItem::make(CompanySettings::class)->color('primary'),
                    CardItem::make(BillingSettings::class)->color('success'),
                ]),

            CardItem::make('/external/docs')
                ->label('Documentation')
                ->icon('heroicon-o-document-text')
                ->openUrlInNewTab(),
        ];
    }
}
```

## Card Items API

### Creating a Card Item

Pass a Filament Page class, Resource class, or a URL string:

```php
CardItem::make(CompanySettings::class)  // Filament Page
CardItem::make(UserResource::class)     // Filament Resource
CardItem::make('/custom/path')          // URL string
CardItem::make('https://example.com')   // External URL
```

When a Page or Resource class is passed, the card automatically resolves its `label`, `icon`, and `url` from the class's navigation properties.

### `label()`

Override the card title. Accepts a string or Closure:

```php
CardItem::make(CompanySettings::class)
    ->label('Company')

CardItem::make(CompanySettings::class)
    ->label(fn () => __('settings.company'))
```

### `description()`

Add a subtitle below the card title:

```php
CardItem::make(CompanySettings::class)
    ->description('Manage company name, address, and branding')
```

### `icon()`

Override the card icon:

```php
CardItem::make('/path')
    ->icon('heroicon-o-building-office')
```

### `url()` and `openUrlInNewTab()`

Override the URL or open in a new tab:

```php
CardItem::make(CompanySettings::class)
    ->url('https://custom-url.com')
    ->openUrlInNewTab()
```

### `visible()` and `hidden()`

Control card visibility. Accepts a boolean or Closure:

```php
CardItem::make(BillingSettings::class)
    ->visible(fn () => auth()->user()->can('manage-billing'))

CardItem::make(DangerZone::class)
    ->hidden(fn () => ! auth()->user()->isAdmin())
```

### `color()`

Add a color accent to the card. Supports Filament's color system:

```php
CardItem::make(CompanySettings::class)->color('primary')
CardItem::make(BillingSettings::class)->color('success')
CardItem::make(DangerZone::class)->color('danger')
CardItem::make(Notifications::class)->color('warning')
CardItem::make(ApiSettings::class)->color('info')
CardItem::make(LegacySettings::class)->color('gray')
```

Available colors: `primary`, `success`, `danger`, `warning`, `info`, `gray`.

### `disabled()`

Show the card but make it non-clickable with reduced opacity:

```php
CardItem::make(DangerZone::class)
    ->disabled(fn () => ! auth()->user()->isAdmin())
```

### `sort()`

Control the order of cards within a group:

```php
CardItem::make(CompanySettings::class)->sort(1)
CardItem::make(BillingSettings::class)->sort(2)
CardItem::make(NotificationPrefs::class)->sort(3)
```

### `columnSpan()` and `columnSpanFull()`

Control how many grid columns a card occupies:

```php
// Span 2 columns
CardItem::make(CompanySettings::class)->columnSpan(2)

// Span the full width
CardItem::make(NotificationPrefs::class)->columnSpanFull()

// Responsive spans
CardItem::make(CompanySettings::class)->columnSpan([
    'default' => 1,
    'md' => 2,
    'lg' => 3,
])
```

### `extraAttributes()`

Add custom HTML attributes to the card element:

```php
CardItem::make(CompanySettings::class)
    ->extraAttributes([
        'data-analytics' => 'company-settings',
        'id' => 'company-card',
    ])
```

## Card Groups API

Groups organize cards under a collapsible header, similar to Filament's `Section`.

### Creating a Group

```php
use Harvirsidhu\FilamentCards\CardGroup;

CardGroup::make('General Settings')
    ->schema([
        CardItem::make(CompanySettings::class),
        CardItem::make(BillingSettings::class),
    ])
```

### `schema()`

Define the card items in the group:

```php
CardGroup::make('General')
    ->schema([
        CardItem::make(CompanySettings::class),
        CardItem::make(BillingSettings::class),
    ])
```

### `columns()`

Override the grid columns for this specific group:

```php
CardGroup::make('Wide Cards')
    ->columns(2)
    ->schema([...])
```

### `collapsible()` and `collapsed()`

Make the group collapsible, optionally starting collapsed:

```php
CardGroup::make('Advanced')
    ->collapsible()
    ->schema([...])

CardGroup::make('Advanced')
    ->collapsed() // Starts collapsed, implicitly collapsible
    ->schema([...])

CardGroup::make('Advanced')
    ->collapsed(fn () => ! auth()->user()->isAdmin())
    ->schema([...])
```

### `compact()`

Reduce padding and gaps for a denser layout:

```php
CardGroup::make('Quick Links')
    ->compact()
    ->schema([...])
```

### `label()`, `description()`, `icon()`

Customize the group header:

```php
CardGroup::make('General')
    ->label('General Settings')
    ->description('Core application configuration')
    ->icon('heroicon-o-cog')
    ->schema([...])
```

### Group-level `visible()` and `hidden()`

Hide an entire group conditionally:

```php
CardGroup::make('Admin Only')
    ->hidden(fn () => ! auth()->user()->isAdmin())
    ->schema([...])
```

## Page Configuration

Customize the CardsPage with static properties:

### `$columns`

Default number of grid columns (default: `3`):

```php
class ControlPanel extends CardsPage
{
    protected static int $columns = 4;
}
```

### `$itemsAlignment`

Alignment of card content. Options: `Start`, `Center`, `End` (default: `Start`):

```php
use Harvirsidhu\FilamentCards\Enums\Alignment;

class ControlPanel extends CardsPage
{
    protected static Alignment $itemsAlignment = Alignment::Center;
}
```

### `$iconSize`

Size of card icons. Options: `Small`, `Medium`, `Large` (default: `Medium`):

```php
use Filament\Support\Enums\IconSize;

class ControlPanel extends CardsPage
{
    protected static IconSize $iconSize = IconSize::Small;
}
```

### `$iconInlined`

Display the icon inline with the title instead of stacked above it:

```php
class ControlPanel extends CardsPage
{
    protected static bool $iconInlined = true;
}
```

## Dynamic Registration

Add cards to a CardsPage from outside the class -- useful for modular applications or packages:

```php
use App\Filament\Pages\ControlPanel;
use Harvirsidhu\FilamentCards\CardItem;

// In a service provider boot() method:
ControlPanel::addCards([
    CardItem::make(UserManagement::class)
        ->label('User Accounts')
        ->icon('heroicon-o-users')
        ->description('Manage roles, permissions, and user accounts'),
]);
```

## Full Example

```php
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Harvirsidhu\FilamentCards\Enums\Alignment;
use Filament\Support\Enums\IconSize;

class SettingsHub extends CardsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static int $columns = 3;
    protected static Alignment $itemsAlignment = Alignment::Start;
    protected static IconSize $iconSize = IconSize::Medium;

    protected static function getCards(): array
    {
        return [
            CardGroup::make('General')
                ->icon('heroicon-o-cog')
                ->description('Core application settings')
                ->collapsible()
                ->schema([
                    CardItem::make(CompanySettings::class)
                        ->color('primary'),

                    CardItem::make(BillingSettings::class)
                        ->visible(fn () => auth()->user()->can('manage-billing'))
                        ->color('success')
                        ->sort(2),

                    CardItem::make(NotificationPrefs::class)
                        ->description('Email, SMS & push notification preferences')
                        ->columnSpanFull(),
                ]),

            CardGroup::make('Danger Zone')
                ->icon('heroicon-o-exclamation-triangle')
                ->collapsed()
                ->columns(2)
                ->schema([
                    CardItem::make(DangerZone::class)
                        ->color('danger')
                        ->disabled(fn () => ! auth()->user()->isAdmin()),
                ]),

            CardItem::make('https://docs.example.com')
                ->label('Documentation')
                ->icon('heroicon-o-book-open')
                ->openUrlInNewTab()
                ->extraAttributes(['data-track' => 'docs']),
        ];
    }
}
```

## Filament Plugin Registration

Optionally register the plugin in your panel provider:

```php
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentCardsPlugin::make(),
        ]);
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
