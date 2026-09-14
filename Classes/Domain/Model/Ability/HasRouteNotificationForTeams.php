<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Domain\Model\Ability;

/**
 * Provides the default Teams webhook routing for a notifiable model.
 *
 * Implement routeNotificationForTeams() and return the Incoming Webhook URL
 * for the Teams channel that should receive notifications for this entity.
 *
 * Example usage on a notifiable model:
 *
 *   use HasRouteNotificationForTeams;
 *
 *   public function routeNotificationForTeams(): string
 *   {
 *       return 'https://prod.webhook.office.com/webhookb2/...';
 *   }
 *
 * You can also override the URL per-message inside the Notification itself
 * via TeamsMessage::webhookUrl(), which takes precedence over this method.
 */
trait HasRouteNotificationForTeams
{
    abstract public function routeNotificationForTeams(): string;
}
