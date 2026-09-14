<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message;

/**
 * Wraps an AdaptiveCard into the Microsoft Teams Workflow webhook payload.
 *
 * The webhook URL can be set directly on the message or resolved at send time
 * via the notifiable's routeNotificationForTeams() method.
 *
 * @see https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook
 *
 * Example — inside a Notification::toTeams() method:
 *
 *   return TeamsMessage::create()
 *       ->webhookUrl('https://prod.webhook.office.com/webhookb2/...')
 *       ->card(
 *           AdaptiveCard::make()
 *               ->body([
 *                   TextBlock::make('New order received')->weight('Bolder')->size('Large'),
 *               ])
 *               ->actions([
 *                   Action\OpenUrl::make('View Order', 'https://example.com/order/123'),
 *               ])
 *       );
 */
final class TeamsMessage
{
    private ?string $webhookUrl = null;
    private ?AdaptiveCard $card = null;

    private function __construct() {}

    public static function create(): static
    {
        return new static();
    }

    /**
     * Override the webhook URL for this specific message.
     *
     * When omitted, TeamsChannel falls back to the notifiable's
     * routeNotificationForTeams() method.
     */
    public function webhookUrl(string $url): static
    {
        $this->webhookUrl = $url;
        return $this;
    }

    /**
     * Attach the Adaptive Card to send.
     */
    public function card(AdaptiveCard $card): static
    {
        $this->card = $card;
        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    /**
     * Build the Teams Workflow webhook payload.
     *
     * Format: https://learn.microsoft.com/en-us/connectors/teams/#post-message-in-a-chat-or-channel
     */
    public function toArray(): array
    {
        return [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => $this->card?->toArray() ?? [],
                ],
            ],
        ];
    }
}
