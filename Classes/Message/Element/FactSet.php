<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Displays a series of key/value pairs in a tabular layout.
 *
 * @see https://adaptivecards.io/explorer/FactSet.html
 *
 * Example:
 *   FactSet::make([
 *       Fact::make('Status', 'Active'),
 *       Fact::make('Priority', 'High'),
 *       Fact::make('Assignee', 'Jane Doe'),
 *   ])
 */
class FactSet implements Renderable
{
    use HasCommonBodyProperties;

    /** @param Fact[] $facts */
    protected function __construct(protected array $facts = []) {}

    /** @param Fact[] $facts */
    public static function make(array $facts = []): static
    {
        return new static($facts);
    }

    public function add(Fact ...$facts): static
    {
        $this->facts = array_merge($this->facts, $facts);
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'type' => 'FactSet',
                'facts' => array_map(static fn(Fact $f) => $f->toArray(), $this->facts),
            ],
            $this->buildCommonBodyProperties(),
        );
    }
}
