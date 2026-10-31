<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Channel;

use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\MicrosoftTeams\Configuration\TeamsConfiguration;
use Lex\Notifications\MicrosoftTeams\Configuration\TeamsRoute;
use Lex\Notifications\MicrosoftTeams\Extension;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;
use Lex\Notifications\Notification;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Sends Adaptive Card notifications to a Microsoft Teams channel via an
 * Incoming Webhook (Teams Workflows / Power Automate).
 *
 * Set up an Incoming Webhook in Teams:
 *   1. In the target Teams channel, open "Workflows" (Power Automate).
 *   2. Add a "Post to a channel when a webhook request is received" flow.
 *   3. Copy the generated webhook URL.
 *   4. Pass that URL, or a name configured under
 *      $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['notifications_microsoft_teams']['webhooks'].
 *      Prefer TeamsRoute::webhookNamed() when the notification can be queued:
 *      the URL is a secret and is resolved only when the message is sent.
 *
 * Usage — extend TeamsNotification and implement toTeams():
 *
 *   class MyNotification extends TeamsNotification
 *   {
 *       public function toTeams(object $notifiable): TeamsMessage
 *       {
 *           return TeamsMessage::create()
 *               ->card(
 *                   AdaptiveCard::make()
 *                       ->body([TextBlock::make('Hello Teams!')])
 *               );
 *       }
 *   }
 */
#[AutoconfigureTag('notifications.channel')]
class TeamsChannel implements ChannelInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const CHANNEL = Extension::CHANNEL_NAME;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly TeamsConfiguration $configuration,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $webhookUrl = $this->resolveWebhookUrl($notifiable, $notification);

        if ($webhookUrl !== null) {

            $message = $this->getMessage($notifiable, $notification);

            $payload = json_encode($message->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            try {
                $response = $this->requestFactory->request($webhookUrl, 'POST', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $payload,
                ]);

                $statusCode = $response->getStatusCode();

                if ($statusCode < 200 || $statusCode >= 300) {
                    $this->logger?->warning(
                        'TeamsChannel: webhook returned HTTP {status} for notification {notification}.',
                        [
                            'status' => $statusCode,
                            'notification' => $notification->getType(),
                            'response' => (string) $response->getBody(),
                        ],
                    );
                }
            } catch (\Throwable $exception) {
                $this->logger?->error(
                    'TeamsChannel: failed to deliver notification {notification}: {message}',
                    [
                        'notification' => $notification->getType(),
                        'message' => $exception->getMessage(),
                        'exception' => $exception,
                    ],
                );
            }
        }
    }

    private function resolveWebhookUrl(object $notifiable, Notification $notification): ?string
    {
        if (!method_exists($notifiable, 'routeNotificationFor')) {
            throw new \InvalidArgumentException('Notifiable must implement routeNotificationFor().');
        }

        $rawRoute = $notifiable->routeNotificationFor(self::CHANNEL, $notification);
        if ($rawRoute === null || $rawRoute === '') {
            return null;
        }

        $route = match (true) {
            $rawRoute instanceof TeamsRoute => $rawRoute,
            is_string($rawRoute) => TeamsRoute::fromString($rawRoute),
            default => throw new \InvalidArgumentException('Teams route must be TeamsRoute or a destination string.'),
        };

        return match ($route->transport) {
            Extension::TRANSPORT_NAME_WEBHOOK => self::directWebhookUrl($route->destination),
            Extension::TRANSPORT_NAME_WEBHOOK_NAMED => $this->configuration->namedWebhook($route->destination),
            default => throw new \InvalidArgumentException('Unknown Microsoft Teams transport.'),
        };
    }

    private static function directWebhookUrl(string $url): string
    {
        TeamsRoute::assertWebhookUrl($url);
        return $url;
    }

    public function getName(): string
    {
        return self::CHANNEL;
    }

    protected function getMessage(object $notifiable, Notification $notification): TeamsMessage {

        if (!method_exists($notification, 'toTeams')) {
            throw new \InvalidArgumentException(sprintf(
                'Notification class "%s" must use the "CanSendToTeams" trait or implement the "toTeams()" method to be sent via the Microsoft Teams channel.',
                get_class($notification)
            ),
                1789350405);
        }

        return $notification->toTeams($notifiable);
    }
}
