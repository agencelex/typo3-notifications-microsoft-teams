<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Action;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonActionProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Toggles the visibility of one or more elements when clicked.
 *
 * Target elements are referenced by their `id` property. Each entry can be
 * a plain element ID string, or an associative array with keys `elementId`
 * and `isVisible` (bool) to force a specific state.
 *
 * @see https://adaptivecards.io/explorer/Action.ToggleVisibility.html
 *
 * Example:
 *   Action\ToggleVisibility::make('Toggle Details', ['detailsContainer'])
 *
 * Force a specific state:
 *   Action\ToggleVisibility::make('Show Details', [
 *       ['elementId' => 'detailsContainer', 'isVisible' => true],
 *   ])
 */
final class ToggleVisibility implements Renderable
{
    use HasCommonActionProperties;

    private function __construct(
        private string $title,
        private array $targetElements = [],
    ) {}

    public static function make(string $title, array $targetElements = []): static
    {
        return new static($title, $targetElements);
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param array<string|array{elementId: string, isVisible?: bool}> $targetElements
     */
    public function targetElements(array $targetElements): static
    {
        $this->targetElements = $targetElements;
        return $this;
    }

    public function toArray(): array
    {
        $normalised = array_map(
            static fn($target) => is_string($target) ? ['elementId' => $target] : $target,
            $this->targetElements,
        );

        return array_merge(
            [
                'type' => 'Action.ToggleVisibility',
                'title' => $this->title,
                'targetElements' => $normalised,
            ],
            $this->buildCommonActionProperties(),
        );
    }
}
