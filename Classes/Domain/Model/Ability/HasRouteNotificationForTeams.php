<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Domain\Model\Ability;

use Lex\Notifications\MicrosoftTeams\Configuration\TeamsRoute;
use Lex\Notifications\Notification;

/**
 * Provides the default Teams webhook routing for a notifiable model.
 *
 * Implement routeNotificationForTeams() and return a webhook URL or a name
 * configured in the extension's webhooks map.
 *
 * Example usage on a notifiable model:
 *
 *   use HasRouteNotificationForTeams;
 *
 *   public function routeNotificationForTeams(?Notification $notification = null): TeamsRoute|string|null
 *   {
 *       return TeamsRoute::webhookNamed('monitoring');
 *   }
 */
trait HasRouteNotificationForTeams
{
    abstract public function routeNotificationForTeams(?Notification $notification = null): TeamsRoute|string|null;
}
