<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Channel;

use Lex\Notifications\Channel\ChannelInterface;
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
 *   4. Provide the URL via TeamsMessage::webhookUrl() or by implementing
 *      routeNotificationForTeams(): string on the notifiable model.
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

    public const CHANNEL = 'teams';

    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $message = $this->getMessage($notifiable, $notification);

        $webhookUrl = $message->getWebhookUrl();

        if ($webhookUrl === null && method_exists($notifiable, 'routeNotificationForTeams')) {
            $webhookUrl = $notifiable->routeNotificationForTeams();
        }

        if (empty($webhookUrl)) {
            $this->logger?->warning(
                'TeamsChannel: no webhook URL resolved for notification {notification}.',
                ['notification' => $notification->getType()],
            );
            return;
        }

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
