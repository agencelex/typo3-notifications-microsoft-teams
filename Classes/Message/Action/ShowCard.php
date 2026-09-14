<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Action;

use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonActionProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Reveals an inline AdaptiveCard when clicked.
 *
 * @see https://adaptivecards.io/explorer/Action.ShowCard.html
 *
 * Example:
 *   Action\ShowCard::make('Show Details',
 *       AdaptiveCard::make()->body([TextBlock::make('Detail content')])
 *   )
 */
final class ShowCard implements Renderable
{
    use HasCommonActionProperties;

    private function __construct(
        private string $title,
        private AdaptiveCard $card,
    ) {}

    public static function make(string $title, AdaptiveCard $card): static
    {
        return new static($title, $card);
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function card(AdaptiveCard $card): static
    {
        $this->card = $card;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'type' => 'Action.ShowCard',
                'title' => $this->title,
                'card' => $this->card->toArray(),
            ],
            $this->buildCommonActionProperties(),
        );
    }
}
