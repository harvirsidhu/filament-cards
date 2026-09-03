<?php

namespace Harvirsidhu\FilamentCards\Concerns;

use Harvirsidhu\FilamentCards\CardGroup;
use Illuminate\Support\Collection;

/**
 * Registers every visible card's actions with the Livewire component.
 *
 * Lives in a trait so the hook is `bootInteractsWithCardActions()`, which
 * Livewire calls alongside — never instead of — a `boot()` the page or a
 * future Filament release might define. A plain `boot()` on CardsPage would
 * silently shadow such a method.
 */
trait InteractsWithCardActions
{
    /**
     * Livewire calls this on every request, including the one that mounts an
     * action. Caching from getViewData() instead would only populate the
     * registry while rendering, so a card's action button rendered fine and
     * then did nothing when clicked.
     */
    public function bootInteractsWithCardActions(): void
    {
        $this->cacheCardActions($this->getProcessedGroups());
    }

    /**
     * Filament resolves a mounted action by name off the component's action
     * cache, so an action rendered inside a card is inert until it has been
     * handed to cacheAction(). Names therefore have to be unique across the
     * page, exactly as they do for a page's header actions.
     *
     * @param  Collection<int, CardGroup>  $groups
     */
    protected function cacheCardActions(Collection $groups): void
    {
        foreach ($groups as $group) {
            foreach ($group->getItems() as $item) {
                foreach ($item->getActions() as $action) {
                    $this->cacheAction($action);
                }
            }
        }
    }
}
