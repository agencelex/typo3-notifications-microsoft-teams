<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Domain\Model\Ability;

use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

/**
 * Base class for all Microsoft Teams notifications.
 *
 * Use this trait when the notification can also be sent to a Teams channel.
 * Classes must implement the method toTeams() to build the Adaptive Card payload.
 *
 *
 * Example:
 *
 *   class OrderPlacedNotification extends TeamsNotification
 *   {
 *       use CanSendToTeams;
 *
 *       public function toTeams(object $notifiable): TeamsMessage
 *       {
 *           return TeamsMessage::create()
 *               ->card(
 *                   AdaptiveCard::make()
 *                       ->body([TextBlock::make('New order placed')->weight('Bolder')])
 *                       ->actions([Action\OpenUrl::make('View', 'https://example.com')])
 *               );
 *       }
 *   }
 */
 trait CanSendToTeams
{
    abstract public function toTeams(object $notifiable): TeamsMessage;
}
