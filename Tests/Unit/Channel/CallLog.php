<?php

declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Tests\Unit\Channel;

final class CallLog
{
    /** @var list<array{0: string, 1: string, 2: array<string, mixed>}> */
    public array $calls = [];
}
