<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Action;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonActionProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Opens a URL in the user's browser when clicked.
 *
 * @see https://adaptivecards.io/explorer/Action.OpenUrl.html
 *
 * Example:
 *   Action\OpenUrl::make('View Order', 'https://example.com/orders/123')
 *       ->style('positive')
 */
final class OpenUrl implements Renderable
{
    use HasCommonActionProperties;

    private function __construct(
        private string $title,
        private string $url,
    ) {}

    public static function make(string $title, string $url): static
    {
        return new static($title, $url);
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'type' => 'Action.OpenUrl',
                'title' => $this->title,
                'url' => $this->url,
            ],
            $this->buildCommonActionProperties(),
        );
    }
}
