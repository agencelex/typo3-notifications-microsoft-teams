<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Contract;

interface Renderable
{
    /**
     * Serialize this element to an Adaptive Card-compatible array.
     */
    public function toArray(): array;
}
