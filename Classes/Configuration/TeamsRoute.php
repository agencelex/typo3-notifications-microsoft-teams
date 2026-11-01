<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Configuration;

use InvalidArgumentException;
use Lex\Notifications\MicrosoftTeams\Extension;

/**
 * Serializable destination. Prefer named webhooks for queued notifications:
 * a direct webhook URL is itself a secret and would be serialized into the queue.
 */
final readonly class TeamsRoute
{
    private function __construct(
        public string $transport,
        public string $destination,
    ) {}

    public static function webhook(string $url): self
    {
        self::assertWebhookUrl($url);
        return new self(Extension::TRANSPORT_NAME_WEBHOOK, $url);
    }

    public static function webhookNamed(string $name): self
    {
        self::assertWebhookName($name);
        return new self(Extension::TRANSPORT_NAME_WEBHOOK_NAMED, $name);
    }

    public static function fromString(string $value): self
    {
        if (str_starts_with(strtolower($value), 'https://')) {
            return self::webhook($value);
        }
        if (str_contains($value, '://')) {
            throw new InvalidArgumentException('Microsoft Teams URLs must be approved HTTPS webhook URLs.');
        }
        // Explicit aliases avoid passing secrets into the queue.
        if (str_starts_with($value, Extension::TRANSPORT_NAME_WEBHOOK . ':')) {
            return self::webhookNamed(substr($value, strlen(Extension::TRANSPORT_NAME_WEBHOOK . ':')));
        }

        return self::webhookNamed($value);
    }

    public static function assertWebhookUrl(string $url): void
    {
        $parts = parse_url($url);
        $port = is_array($parts) ? ($parts['port'] ?? null) : null;
        if (
            !is_array($parts)
            || ($parts['scheme'] ?? '') !== 'https'
            || !is_string($parts['host'] ?? null)
            || $parts['host'] === ''
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment'])
            || ($port !== null && $port !== 443)
        ) {
            throw new InvalidArgumentException('Invalid Microsoft Teams webhook URL.');
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? null;
        if (self::isLegacyConnectorUrl($host, $path, $query) || self::isWorkflowUrl($host, $path, $query)) {
            return;
        }

        throw new InvalidArgumentException('Invalid Microsoft Teams webhook URL.');
    }

    public static function assertWebhookName(string $name): void
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $name)) {
            throw new InvalidArgumentException('Invalid Microsoft Teams webhook name.');
        }
    }

    private static function isLegacyConnectorUrl(string $host, string $path, ?string $query): bool
    {
        if ($query !== null && $query !== '') {
            return false;
        }

        $hostAllowed = $host === 'outlook.office.com'
            || $host === 'outlook.office365.com'
            || $host === 'webhook.office.com'
            || str_ends_with($host, '.webhook.office.com');

        return $hostAllowed && preg_match('~^/webhook(?:b2)?/[^/?#]+/.+$~', $path) === 1;
    }

    private static function isWorkflowUrl(string $host, string $path, ?string $query): bool
    {
        if (!self::queryHasSignature($query)) {
            return false;
        }

        $logicApps = self::hostIs($host, 'logic.azure.com')
            || self::hostIs($host, 'logic.azure.us')
            || self::hostIs($host, 'logic.azure.cn');
        if ($logicApps && preg_match('#^/workflows/[^/]+/triggers/#', $path) === 1) {
            return true;
        }

        // Power Automate HTTP trigger API domains, including sovereign clouds:
        // https://learn.microsoft.com/en-us/power-automate/ip-address-configuration
        $powerPlatform = self::hostIs($host, 'environment.api.powerplatform.com')
            || self::hostIs($host, 'environment.api.gov.powerplatform.microsoft.us')
            || self::hostIs($host, 'environment.api.high.powerplatform.microsoft.us')
            || self::hostIs($host, 'environment.api.appsplatform.us')
            || self::hostIs($host, 'environment.api.powerplatform.partner.microsoftonline.cn');

        return $powerPlatform
            && preg_match('#^/powerautomate/automations/direct/(?:cu/[0-9]+/)?workflows/[^/]+/triggers/#', $path) === 1;
    }

    private static function hostIs(string $host, string $suffix): bool
    {
        return $host === $suffix || str_ends_with($host, '.' . $suffix);
    }

    private static function queryHasSignature(?string $query): bool
    {
        if ($query === null || $query === '') {
            return false;
        }

        parse_str($query, $params);
        $signature = $params['sig'] ?? null;

        return is_string($signature) && $signature !== '';
    }
}
