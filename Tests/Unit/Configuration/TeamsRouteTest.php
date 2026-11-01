<?php

declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Tests\Unit\Configuration;

use InvalidArgumentException;
use Lex\Notifications\MicrosoftTeams\Configuration\TeamsRoute;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TeamsRouteTest extends TestCase
{
    private const WORKFLOW = 'https://prod-00.westeurope.logic.azure.com:443/workflows/abc/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=SECRET';
    private const POWER_PLATFORM = 'https://env.aa.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/abc/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=SECRET';
    private const LEGACY = 'https://contoso.webhook.office.com/webhookb2/abc@def/IncomingWebhook/g/h';

    #[Test]
    public function factoriesSetTransportAndDestination(): void
    {
        $named = TeamsRoute::webhookNamed('monitoring');
        $webhook = TeamsRoute::webhook(self::WORKFLOW);

        self::assertSame(['webhook_named', 'monitoring'], [$named->transport, $named->destination]);
        self::assertSame(['webhook', self::WORKFLOW], [$webhook->transport, $webhook->destination]);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function stringRoutes(): iterable
    {
        yield 'webhook alias' => ['webhook:monitoring', 'webhook_named', 'monitoring'];
        yield 'bare configured name' => ['monitoring', 'webhook_named', 'monitoring'];
        yield 'hyphenated name' => ['team-alerts_1', 'webhook_named', 'team-alerts_1'];
        yield 'workflow url' => [self::WORKFLOW, 'webhook', self::WORKFLOW];
        yield 'workflow url without port' => [
            'https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
            'webhook',
            'https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
        ];
        yield 'workflow url with explicit port' => [
            'https://prod-00.westeurope.logic.azure.com:443/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
            'webhook',
            'https://prod-00.westeurope.logic.azure.com:443/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
        ];
        yield 'Azure Government workflow' => [
            'https://prod-00.usgov.logic.azure.us/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
            'webhook',
            'https://prod-00.usgov.logic.azure.us/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
        ];
        yield 'Azure China workflow' => [
            'https://prod-00.china.logic.azure.cn/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
            'webhook',
            'https://prod-00.china.logic.azure.cn/workflows/abc/triggers/manual/paths/invoke?sig=SECRET',
        ];
        yield 'Power Platform workflow without compute unit' => [self::POWER_PLATFORM, 'webhook', self::POWER_PLATFORM];
        yield 'Power Platform workflow with compute unit' => [
            'https://default0123456789abcdef.a0.environment.api.powerplatform.com:443/powerautomate/automations/direct/cu/14/workflows/abc/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=SECRET',
            'webhook',
            'https://default0123456789abcdef.a0.environment.api.powerplatform.com:443/powerautomate/automations/direct/cu/14/workflows/abc/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=SECRET',
        ];
        foreach (self::sovereignPowerPlatformHosts() as $cloud => $host) {
            foreach (['without compute unit' => '', 'with compute unit' => 'cu/14/'] as $variant => $computeUnit) {
                $url = 'https://env.aa.' . $host . ':443/powerautomate/automations/direct/'
                    . $computeUnit . 'workflows/abc/triggers/manual/paths/invoke?sig=SECRET';
                yield $cloud . ' ' . $variant => [$url, 'webhook', $url];
            }
        }
        yield 'legacy custom tenant host' => [self::LEGACY, 'webhook', self::LEGACY];
        yield 'legacy prod host' => [
            'https://prod.webhook.office.com/webhookb2/abc@def/IncomingWebhook/g/h',
            'webhook',
            'https://prod.webhook.office.com/webhookb2/abc@def/IncomingWebhook/g/h',
        ];
        yield 'outlook connector' => [
            'https://outlook.office.com/webhook/abc@def/IncomingWebhook/g/h',
            'webhook',
            'https://outlook.office.com/webhook/abc@def/IncomingWebhook/g/h',
        ];
        yield 'outlook 365 connector' => [
            'https://outlook.office365.com/webhook/abc@def/IncomingWebhook/g/h',
            'webhook',
            'https://outlook.office365.com/webhook/abc@def/IncomingWebhook/g/h',
        ];
    }

    #[Test]
    #[DataProvider('stringRoutes')]
    public function fromStringDetectsTheTransport(string $value, string $transport, string $destination): void
    {
        $route = TeamsRoute::fromString($value);

        self::assertSame($transport, $route->transport);
        self::assertSame($destination, $route->destination);
    }

    /** @return iterable<string, array{string}> */
    public static function badRoutes(): iterable
    {
        yield 'http' => ['http://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'uppercase scheme' => ['HTTPS://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'external https' => ['https://example.org/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'userinfo trick' => ['https://prod.webhook.office.com@evil.test/webhookb2/abc/def'];
        yield 'credentials' => ['https://user:pass@outlook.office.com/webhook/abc/def'];
        yield 'port' => ['https://prod-00.westeurope.logic.azure.com:444/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'fragment' => ['https://contoso.webhook.office.com/webhookb2/abc/def#x'];
        yield 'legacy query' => ['https://contoso.webhook.office.com/webhookb2/abc/def?sig=SECRET'];
        yield 'legacy short path' => ['https://contoso.webhook.office.com/webhookb2/only'];
        yield 'missing signature' => ['https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke'];
        yield 'empty signature' => ['https://prod-00.westeurope.logic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig='];
        yield 'workflow path on connector host' => ['https://contoso.webhook.office.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'connector path on workflow host' => ['https://prod-00.westeurope.logic.azure.com/webhookb2/abc/def?sig=SECRET'];
        yield 'host suffix attack' => ['https://logic.azure.com.evil.test/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'lookalike host' => ['https://notlogic.azure.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'power platform without workflow path' => ['https://env.aa.environment.api.powerplatform.com/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'power platform empty compute unit' => ['https://env.aa.environment.api.powerplatform.com/powerautomate/automations/direct/cu//workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'power platform nonnumeric compute unit' => ['https://env.aa.environment.api.powerplatform.com/powerautomate/automations/direct/cu/abc/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        yield 'power platform compute unit missing signature' => ['https://env.aa.environment.api.powerplatform.com/powerautomate/automations/direct/cu/14/workflows/abc/triggers/manual/paths/invoke'];
        foreach (self::sovereignPowerPlatformHosts() as $cloud => $host) {
            $path = '/powerautomate/automations/direct/cu/14/workflows/abc/triggers/manual/paths/invoke';
            yield $cloud . ' missing signature' => ['https://env.aa.' . $host . $path];
            yield $cloud . ' suffix attack' => ['https://env.aa.' . $host . '.evil.test' . $path . '?sig=SECRET'];
            yield $cloud . ' lookalike host' => ['https://not' . $host . $path . '?sig=SECRET'];
            yield $cloud . ' incorrect path' => ['https://env.aa.' . $host . '/workflows/abc/triggers/manual/paths/invoke?sig=SECRET'];
        }
        yield 'named empty' => ['webhook:'];
        yield 'named path traversal' => ['webhook:../monitoring'];
        yield 'named starting with digit' => ['webhook:1monitoring'];
        yield 'custom scheme' => ['file:///etc/passwd'];
        yield 'empty string' => [''];
    }

    #[Test]
    #[DataProvider('badRoutes')]
    public function rejectsInvalidRoute(string $destination): void
    {
        $this->expectException(InvalidArgumentException::class);

        TeamsRoute::fromString($destination);
    }

    /** @return iterable<string, string> */
    private static function sovereignPowerPlatformHosts(): iterable
    {
        yield 'Power Platform US Government GCC' => 'environment.api.gov.powerplatform.microsoft.us';
        yield 'Power Platform US Government GCC High' => 'environment.api.high.powerplatform.microsoft.us';
        yield 'Power Platform US Government DoD' => 'environment.api.appsplatform.us';
        yield 'Power Platform China 21Vianet' => 'environment.api.powerplatform.partner.microsoftonline.cn';
    }

    #[Test]
    public function webhookNameLengthIsLimited(): void
    {
        self::assertSame(64, strlen(TeamsRoute::webhookNamed('a' . str_repeat('b', 63))->destination));

        $this->expectException(InvalidArgumentException::class);
        TeamsRoute::webhookNamed('a' . str_repeat('b', 64));
    }

    #[Test]
    public function namedWebhookSerializesOnlyItsAlias(): void
    {
        $serialized = serialize(TeamsRoute::webhookNamed('monitoring'));

        self::assertStringNotContainsString('https://', $serialized);
        self::assertStringNotContainsString('SECRET', $serialized);
        self::assertSame('monitoring', unserialize($serialized)->destination);
    }

    #[Test]
    public function directWebhookSerializesTheSecretUrl(): void
    {
        // Documented trade-off: prefer webhookNamed() for queued notifications.
        self::assertStringContainsString(self::WORKFLOW, serialize(TeamsRoute::webhook(self::WORKFLOW)));
    }
}
