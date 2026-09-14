<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message;

use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Builds the content of a Microsoft Adaptive Card.
 *
 * Pass the built card to TeamsMessage::card() to embed it in the Teams
 * message payload.
 *
 * @see https://adaptivecards.microsoft.com/?topic=AdaptiveCard
 *
 * Example:
 *   AdaptiveCard::make()
 *       ->body([
 *           TextBlock::make('Hello World')->size('Large')->weight('Bolder'),
 *           FactSet::make([
 *               Fact::make('Status', 'Active'),
 *           ]),
 *       ])
 *       ->actions([
 *           Action\OpenUrl::make('View', 'https://example.com'),
 *       ])
 */
final class AdaptiveCard
{
    private string $version = '1.6';
    private ?string $minHeight = null;
    private ?string $verticalContentAlignment = null;
    private ?string $backgroundImage = null;
    private ?string $speak = null;
    private ?bool $rtl = null;

    /** @var Renderable[] */
    private array $body = [];

    /** @var Renderable[] */
    private array $actions = [];

    private function __construct() {}

    public static function make(): static
    {
        return new static();
    }

    /**
     * Adaptive Card schema version. Defaults to 1.6.
     *
     * @see https://adaptivecards.io/explorer/AdaptiveCard.html
     */
    public function version(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Set all body elements at once (replaces any previously added elements).
     *
     * @param Renderable[] $elements
     */
    public function body(array $elements): static
    {
        $this->body = $elements;
        return $this;
    }

    /**
     * Append one or more body elements.
     */
    public function add(Renderable ...$elements): static
    {
        $this->body = array_merge($this->body, $elements);
        return $this;
    }

    /**
     * Set all card-level actions at once (replaces any previously added actions).
     *
     * @param Renderable[] $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    /**
     * Append one or more card-level actions.
     */
    public function addAction(Renderable ...$actions): static
    {
        $this->actions = array_merge($this->actions, $actions);
        return $this;
    }

    /**
     * Minimum height of the card (CSS value, e.g. "200px").
     */
    public function minHeight(string $minHeight): static
    {
        $this->minHeight = $minHeight;
        return $this;
    }

    /**
     * Vertical alignment of the card body.
     *
     * Allowed values: Top, Center, Bottom
     */
    public function verticalContentAlignment(string $alignment): static
    {
        $this->verticalContentAlignment = $alignment;
        return $this;
    }

    /**
     * Background image URL displayed behind the card body.
     */
    public function backgroundImage(string $url): static
    {
        $this->backgroundImage = $url;
        return $this;
    }

    /**
     * Text read aloud by screen readers.
     */
    public function speak(string $speak): static
    {
        $this->speak = $speak;
        return $this;
    }

    /**
     * Enable right-to-left text flow for the entire card.
     */
    public function rtl(bool $rtl = true): static
    {
        $this->rtl = $rtl;
        return $this;
    }

    public function toArray(): array
    {
        $card = [
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'type' => 'AdaptiveCard',
            'version' => $this->version,
        ];

        if (!empty($this->body)) {
            $card['body'] = array_map(
                static fn(Renderable $el) => $el->toArray(),
                $this->body,
            );
        }

        if (!empty($this->actions)) {
            $card['actions'] = array_map(
                static fn(Renderable $action) => $action->toArray(),
                $this->actions,
            );
        }

        if ($this->minHeight !== null) {
            $card['minHeight'] = $this->minHeight;
        }
        if ($this->verticalContentAlignment !== null) {
            $card['verticalContentAlignment'] = $this->verticalContentAlignment;
        }
        if ($this->backgroundImage !== null) {
            $card['backgroundImage'] = $this->backgroundImage;
        }
        if ($this->speak !== null) {
            $card['speak'] = $this->speak;
        }
        if ($this->rtl !== null) {
            $card['rtl'] = $this->rtl;
        }

        return $card;
    }
}
