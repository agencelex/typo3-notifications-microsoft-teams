<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message;

/**
 * Wraps an AdaptiveCard into the Microsoft Teams Workflow webhook payload.
 *
 * The destination is the notifiable route: an HTTPS webhook URL, or a webhook
 * name configured for this extension. See TeamsRoute.
 *
 * @see https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook
 *
 * Example — inside a Notification::toTeams() method:
 *
 *   return TeamsMessage::create()
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
class TeamsMessage
{
    protected ?string $webhookUrl = null;
    protected ?AdaptiveCard $card = null;

    protected function __construct() {}

    public static function create(): static
    {
        return new static();
    }

    /**
     * Remember a webhook URL on this message.
     *
     * TeamsChannel delivers to the notifiable route (a webhook URL or a
     * configured webhook name) and does not read this value.
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
