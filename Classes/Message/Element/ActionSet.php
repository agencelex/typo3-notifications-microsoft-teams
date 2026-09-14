<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Embeds a set of actions inline within the card body.
 *
 * Use this when you need actions at a specific position in the body rather
 * than at the bottom of the card via AdaptiveCard::actions().
 *
 * @see https://adaptivecards.io/explorer/ActionSet.html
 *
 * Example:
 *   ActionSet::make([
 *       Action\OpenUrl::make('Approve', 'https://example.com/approve'),
 *       Action\Submit::make('Reject', ['action' => 'reject']),
 *   ])
 */
final class ActionSet implements Renderable
{
    use HasCommonBodyProperties;

    /** @param Renderable[] $actions */
    private function __construct(private array $actions = []) {}

    /** @param Renderable[] $actions */
    public static function make(array $actions = []): static
    {
        return new static($actions);
    }

    public function add(Renderable ...$actions): static
    {
        $this->actions = array_merge($this->actions, $actions);
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'type' => 'ActionSet',
                'actions' => array_map(static fn(Renderable $a) => $a->toArray(), $this->actions),
            ],
            $this->buildCommonBodyProperties(),
        );
    }
}
