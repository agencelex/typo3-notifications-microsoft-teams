<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Configuration;

use Lex\Notifications\MicrosoftTeams\Extension;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Centralized TYPO3 configuration; webhook URLs are resolved only at send time. */
readonly class TeamsConfiguration
{
    public function __construct(
        #[Autowire(expression: 'service("extension-configuration").get("' . Extension::KEY . '")')]
        protected array $settings
    ) {}

    public function namedWebhook(string $name): string
    {
        $webhooks = $this->settings['webhooks'] ?? [];
        $value = is_array($webhooks) ? ($webhooks[$name] ?? null) : null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException('Named Microsoft Teams webhook is not configured: ' . $name);
        }
        TeamsRoute::assertWebhookUrl($value);

        return $value;
    }
}
