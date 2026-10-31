<?php

declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Tests\Unit\Configuration;

use InvalidArgumentException;
use Lex\Notifications\MicrosoftTeams\Configuration\TeamsConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TeamsConfigurationTest extends TestCase
{
    private const WORKFLOW = 'https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET';

    private static function configuration(array $overrides = []): TeamsConfiguration
    {
        return new TeamsConfiguration(array_replace([
            'webhooks' => ['monitoring' => self::WORKFLOW],
        ], $overrides));
    }

    #[Test]
    public function readsANamedWebhook(): void
    {
        self::assertSame(self::WORKFLOW, self::configuration()->namedWebhook('monitoring'));
    }

    /** @return iterable<string, array{string}> */
    public static function approvedWebhookUrls(): iterable
    {
        yield 'Power Platform' => ['https://env.aa.environment.api.powerplatform.com/powerautomate/automations/direct/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'legacy connector' => ['https://contoso.webhook.office.com/webhookb2/abc@def/IncomingWebhook/g/h'];
        yield 'Azure Government' => ['https://prod-00.usgov.logic.azure.us/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
    }

    #[Test]
    #[DataProvider('approvedWebhookUrls')]
    public function approvedConfiguredUrlsAreAccepted(string $url): void
    {
        self::assertSame($url, self::configuration(['webhooks' => ['alerts' => $url]])->namedWebhook('alerts'));
    }

    /** @return iterable<string, array{mixed}> */
    public static function unconfiguredWebhookLists(): iterable
    {
        yield 'unknown name' => [['security' => self::WORKFLOW]];
        yield 'empty value (unset environment variable)' => [['monitoring' => '']];
        yield 'not a string' => [['monitoring' => [self::WORKFLOW]]];
        yield 'webhooks is not an array' => [self::WORKFLOW];
    }

    #[Test]
    #[DataProvider('unconfiguredWebhookLists')]
    public function unconfiguredNamedWebhookIsRejected(mixed $webhooks): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Named Microsoft Teams webhook is not configured: monitoring');

        self::configuration(['webhooks' => $webhooks])->namedWebhook('monitoring');
    }

    #[Test]
    public function configuredWebhookOutsideTeamsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::configuration(['webhooks' => ['monitoring' => 'https://evil.example.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET']])
            ->namedWebhook('monitoring');
    }

    #[Test]
    public function configuredWorkflowWithoutSignatureIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::configuration(['webhooks' => ['monitoring' => 'https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke']])
            ->namedWebhook('monitoring');
    }
}
