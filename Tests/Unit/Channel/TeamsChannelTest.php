<?php

declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Tests\Unit\Channel;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Configuration\TeamsConfiguration;
use Lex\Notifications\MicrosoftTeams\Configuration\TeamsRoute;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\HasRouteNotificationForTeams;
use Lex\Notifications\MicrosoftTeams\Extension;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;
use Lex\Notifications\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class TeamsChannelTest extends TestCase
{
    private const WEBHOOK = 'https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET';

    private static function channel(RecordingRequestFactory $factory, array $settings = []): TeamsChannel
    {
        return new TeamsChannel($factory, new TeamsConfiguration(array_replace([
            'webhooks' => ['monitoring' => self::WEBHOOK],
        ], $settings)));
    }

    /** @return array{CallLog, RecordingRequestFactory} */
    private static function factory(?Response $response = null, ?Throwable $exception = null): array
    {
        $log = new CallLog();

        return [$log, new RecordingRequestFactory($log, $response, $exception)];
    }

    private static function notification(): Notification
    {
        return new class extends Notification {
            public function via(object $notifiable): array { return [Extension::CHANNEL_NAME]; }
            public function toTeams(object $notifiable): TeamsMessage
            {
                return TeamsMessage::create();
            }
        };
    }

    private static function routedTo(mixed $route): object
    {
        return new class($route) {
            use Notifiable;
            use HasRouteNotificationForTeams;

            public function __construct(private readonly mixed $route) {}
            public function routeNotificationForTeams(?Notification $notification = null): TeamsRoute|string|null
            {
                return $this->route;
            }
        };
    }

    private function assertRejectedBeforeRequest(object $notifiable, string $exception, string $message): void
    {
        [$log, $factory] = self::factory();

        try {
            self::channel($factory)->send($notifiable, self::notification());
            self::fail('The route must be rejected before the HTTP request.');
        } catch (Throwable $caught) {
            self::assertInstanceOf($exception, $caught);
            self::assertStringContainsString($message, $caught->getMessage());
            self::assertSame([], $log->calls);
        }
    }

    /** @param array<string, mixed> $payload */
    private static function assertPosted(CallLog $log, string $url, array $payload): void
    {
        self::assertCount(1, $log->calls);
        [$actualUrl, $method, $options] = $log->calls[0];
        self::assertSame($url, $actualUrl);
        self::assertSame('POST', $method);
        self::assertSame('application/json', $options['headers']['Content-Type'] ?? null);
        self::assertSame($payload, json_decode($options['body'], true));
    }

    #[Test]
    public function nameIsTeams(): void
    {
        [, $factory] = self::factory();

        self::assertSame(Extension::CHANNEL_NAME, self::channel($factory)->getName());
        self::assertSame(Extension::CHANNEL_NAME, TeamsChannel::CHANNEL);
    }

    /** @return iterable<string, array{TeamsRoute|string}> */
    public static function routes(): iterable
    {
        yield 'TeamsRoute::webhookNamed()' => [TeamsRoute::webhookNamed('monitoring')];
        yield '"webhook:<name>" string' => ['webhook:monitoring'];
        yield 'bare configured name' => ['monitoring'];
        yield 'TeamsRoute::webhook()' => [TeamsRoute::webhook(self::WEBHOOK)];
        yield 'webhook URL string' => [self::WEBHOOK];
    }

    #[Test]
    #[DataProvider('routes')]
    public function routedNotificationIsSent(TeamsRoute|string $route): void
    {
        [$log, $factory] = self::factory();

        self::channel($factory)->send(self::routedTo($route), self::notification());

        self::assertPosted($log, self::WEBHOOK, TeamsMessage::create()->toArray());
    }

    #[Test]
    public function routeIsResolvedWithTheNotification(): void
    {
        $notification = self::notification();
        $notifiable = new class {
            public ?Notification $received = null;
            public function routeNotificationFor(string $channel, Notification $notification): ?string
            {
                $this->received = $notification;
                return $channel === Extension::CHANNEL_NAME ? 'webhook:monitoring' : null;
            }
        };

        [$log, $factory] = self::factory();

        self::channel($factory)->send($notifiable, $notification);

        self::assertSame($notification, $notifiable->received);
        self::assertPosted($log, self::WEBHOOK, TeamsMessage::create()->toArray());
    }

    /** @return iterable<string, array{mixed}> */
    public static function emptyRoutes(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    #[Test]
    #[DataProvider('emptyRoutes')]
    public function notifiableWithoutRouteIsSkipped(mixed $route): void
    {
        [$log, $factory] = self::factory();

        self::channel($factory)->send(self::routedTo($route), self::notification());

        self::assertSame([], $log->calls);
    }

    #[Test]
    public function notifiableWithoutRouteNotificationForIsRejected(): void
    {
        $this->assertRejectedBeforeRequest(
            new \stdClass(),
            InvalidArgumentException::class,
            'Notifiable must implement routeNotificationFor().',
        );
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidRouteTypes(): iterable
    {
        yield 'integer' => [42];
        yield 'array' => [[self::WEBHOOK]];
        yield 'true' => [true];
    }

    #[Test]
    #[DataProvider('invalidRouteTypes')]
    public function unsupportedRouteTypeIsRejected(mixed $route): void
    {
        $this->assertRejectedBeforeRequest(
            self::routedTo($route),
            \TypeError::class,
            'Return value must be of type Lex\Notifications\MicrosoftTeams\Configuration\TeamsRoute|string|null',
        );
    }

    #[Test]
    public function unknownWebhookNameIsRejectedBeforeTheRequest(): void
    {
        $this->assertRejectedBeforeRequest(
            self::routedTo(TeamsRoute::webhookNamed('security')),
            RuntimeException::class,
            'Named Microsoft Teams webhook is not configured: security',
        );
    }

    #[Test]
    public function directUrlOutsideTeamsIsRejectedBeforeTheRequest(): void
    {
        $this->assertRejectedBeforeRequest(
            self::routedTo('https://evil.example.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'),
            InvalidArgumentException::class,
            'Invalid Microsoft Teams webhook URL.',
        );
    }

    #[Test]
    public function httpFailureIsReportedWithoutLeavingTheChannel(): void
    {
        [$log, $factory] = self::factory(new Response(500, [], 'nope'));

        self::channel($factory)->send(self::routedTo(self::WEBHOOK), self::notification());

        self::assertCount(1, $log->calls);
    }

    #[Test]
    public function transportExceptionIsReportedWithoutLeavingTheChannel(): void
    {
        [$log, $factory] = self::factory(exception: new RuntimeException('connection failed'));

        self::channel($factory)->send(self::routedTo('webhook:monitoring'), self::notification());

        self::assertCount(1, $log->calls);
    }

    #[Test]
    public function stringRouteFromTheTraitIsAccepted(): void
    {
        $notifiable = new class {
            use Notifiable;
            use HasRouteNotificationForTeams;

            public function routeNotificationForTeams(?Notification $notification = null): ?string
            {
                return 'webhook:monitoring';
            }
        };

        [$log, $factory] = self::factory();

        self::channel($factory)->send($notifiable, self::notification());

        self::assertPosted($log, self::WEBHOOK, TeamsMessage::create()->toArray());
    }
}
