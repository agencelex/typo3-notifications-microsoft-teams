<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * A single key/value pair displayed inside a FactSet.
 *
 * Example:
 *   Fact::make('Status', 'Active')
 */
readonly class Fact implements Renderable
{
    protected function __construct(
        protected string $title,
        protected string $value,
    ) {}

    public static function make(string $title, string $value): static
    {
        return new static($title, $value);
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'value' => $this->value,
        ];
    }
}
