<?php

declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Tests\Unit\Channel;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Records the HTTP call TeamsChannel makes and returns a fixed response.
 *
 * The TYPO3 parent constructor needs a Guzzle client, which this double never uses.
 */
final readonly class RecordingRequestFactory extends RequestFactory
{
    public function __construct(
        private CallLog $log,
        private ?ResponseInterface $response = null,
        private ?Throwable $exception = null,
    ) {}

    public function request(string $uri, string $method = 'GET', array $options = [], ?string $context = null): ResponseInterface
    {
        $this->log->calls[] = [$uri, $method, $options];
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response ?? new Response(200, [], '');
    }
}
