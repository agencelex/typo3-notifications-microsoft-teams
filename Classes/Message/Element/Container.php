<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Groups a set of elements together with optional styling and background.
 *
 * @see https://adaptivecards.io/explorer/Container.html
 *
 * Example:
 *   Container::make([
 *       TextBlock::make('Section title')->weight('Bolder'),
 *       TextBlock::make('Section content')->wrap(true),
 *   ])
 *       ->style('emphasis')
 *       ->bleed(true)
 */
final class Container implements Renderable
{
    use HasCommonBodyProperties;

    private ?string $backgroundImage = null;
    private ?bool $bleed = null;
    private ?string $minHeight = null;
    private ?bool $rtl = null;
    private ?Renderable $selectAction = null;
    private ?string $style = null;
    private ?string $verticalContentAlignment = null;

    /** @param Renderable[] $items */
    private function __construct(private array $items = []) {}

    /** @param Renderable[] $items */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    public function add(Renderable ...$items): static
    {
        $this->items = array_merge($this->items, $items);
        return $this;
    }

    /**
     * Background image URL.
     */
    public function backgroundImage(string $url): static
    {
        $this->backgroundImage = $url;
        return $this;
    }

    /**
     * Extend the container to cover surrounding padding when true.
     */
    public function bleed(bool $bleed = true): static
    {
        $this->bleed = $bleed;
        return $this;
    }

    /**
     * Minimum height of the container (CSS value, e.g. "100px").
     */
    public function minHeight(string $minHeight): static
    {
        $this->minHeight = $minHeight;
        return $this;
    }

    /**
     * Enable right-to-left text flow.
     */
    public function rtl(bool $rtl = true): static
    {
        $this->rtl = $rtl;
        return $this;
    }

    /**
     * Action triggered when the container is clicked.
     */
    public function selectAction(Renderable $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    /**
     * Container style for background colouring.
     *
     * Allowed values: default, emphasis, good, attention, warning, accent
     */
    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * Vertical alignment of content within the container.
     *
     * Allowed values: Top, Center, Bottom
     */
    public function verticalContentAlignment(string $alignment): static
    {
        $this->verticalContentAlignment = $alignment;
        return $this;
    }

    public function toArray(): array
    {
        $array = array_merge(
            [
                'type' => 'Container',
                'items' => array_map(static fn(Renderable $el) => $el->toArray(), $this->items),
            ],
            $this->buildCommonBodyProperties(),
        );

        if ($this->backgroundImage !== null) {
            $array['backgroundImage'] = $this->backgroundImage;
        }
        if ($this->bleed !== null) {
            $array['bleed'] = $this->bleed;
        }
        if ($this->minHeight !== null) {
            $array['minHeight'] = $this->minHeight;
        }
        if ($this->rtl !== null) {
            $array['rtl'] = $this->rtl;
        }
        if ($this->selectAction !== null) {
            $array['selectAction'] = $this->selectAction->toArray();
        }
        if ($this->style !== null) {
            $array['style'] = $this->style;
        }
        if ($this->verticalContentAlignment !== null) {
            $array['verticalContentAlignment'] = $this->verticalContentAlignment;
        }

        return $array;
    }
}
