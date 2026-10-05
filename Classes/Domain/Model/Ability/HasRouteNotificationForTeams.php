<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Domain\Model\Ability;

use Lex\Notifications\Notification;

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
 *   public function routeNotificationForTeams(?Notification $notification = null): ?string
 *   {
 *       return $settings['teams_webhook_url'] ?? null;
 *   }
 */
trait HasRouteNotificationForTeams
{
    abstract public function routeNotificationForTeams(?Notification $notification = null): ?string;
}
