# lex_notifications_microsoft_teams

Microsoft Teams channel provider for [lex_notifications](https://github.com/agencelex/typo3-notifications). Send rich **Adaptive Cards** to any Teams channel from any TYPO3 extension, scheduler task, middleware, or domain service — using the same Laravel-inspired notification API you already know.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Setting Up a Teams Webhook](#setting-up-a-teams-webhook)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
  - [CanSendToTeams — on the Notification class](#cansendtoteams--on-the-notification-class)
  - [HasRouteNotificationForTeams — on the notifiable model](#hasroutenotificationforteams--on-the-notifiable-model)
- [Routing the Webhook URL](#routing-the-webhook-url)
- [Building Adaptive Cards](#building-adaptive-cards)
  - [AdaptiveCard](#adaptivecard)
  - [TextBlock](#textblock)
  - [FactSet & Fact](#factset--fact)
  - [Image & ImageSet](#image--imageset)
  - [Container](#container)
  - [ColumnSet & Column](#columnset--column)
  - [RichTextBlock](#richtextblock)
  - [ActionSet](#actionset)
  - [Actions](#actions)
- [Real-World Examples](#real-world-examples)
  - [Order Confirmation](#order-confirmation)
  - [Server Alert](#server-alert)
  - [New User Registration with Avatar](#new-user-registration-with-avatar)
  - [Content Approval Request](#content-approval-request)
  - [Multi-Channel Notification (Teams + Mail)](#multi-channel-notification-teams--mail)
- [References](#references)

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | `^8.2` |
| TYPO3 | `^13.4 \|\| ^14.3` |
| agencelex/notifications | `^1.3` |

---

## Installation

```bash
composer require agencelex/notifications-microsoft-teams
```

Activate the extension in the TYPO3 Extension Manager or via `composer` (Composer-mode installations activate it automatically).

---

## Setting Up a Teams Webhook

This extension sends messages via the **Teams Workflows** webhook (the modern replacement for the deprecated Office 365 Connectors).

1. Open the Teams channel you want to post to.
2. Click **…** → **Workflows**.
3. Search for **"Post to a channel when a webhook request is received"** and select it.
4. Follow the wizard — give it a name and click **Next**.
5. Copy the generated **webhook URL** (it looks like `https://prod.webhook.office.com/webhookb2/…`).

> **Official guide:** [Create an Incoming Webhook — Microsoft Learn](https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook)

---

## Quick Start

```php
use Lex\Notifications\Notification;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Action\OpenUrl;
use Lex\Notifications\MicrosoftTeams\Message\Element\TextBlock;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

class HelloTeamsNotification extends Notification
{
    use CanSendToTeams;

    public function via(object $notifiable): array
    {
        return [TeamsChannel::CHANNEL];
    }

    public function toTeams(object $notifiable): TeamsMessage
    {
        return TeamsMessage::create()
            ->card(
                AdaptiveCard::make()
                    ->body([
                        TextBlock::make('Hello from TYPO3!')->weight('Bolder')->size('Large'),
                        TextBlock::make('Your notification system is working.')->wrap(true),
                    ])
                    ->actions([
                        OpenUrl::make('Visit site', 'https://example.com'),
                    ])
            );
    }
}
```

Send it from anywhere:

```php
// Inject NotificationDispatcherInterface via constructor
$this->notificationDispatcher->send($frontendUser, new HelloTeamsNotification());
```

---

## Core Concepts

This extension provides two independent traits. Each addresses a different side of the notification contract.

---

### `CanSendToTeams` — on the Notification class

```
Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams
```

Add this trait to any class that extends `Lex\Notifications\Notification` to declare that it can be sent to Teams. The trait enforces a single contract: the class **must** implement `toTeams(object $notifiable): TeamsMessage`.

```php
use Lex\Notifications\Notification;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

class MyNotification extends Notification
{
    use CanSendToTeams;

    // Enforced by the trait — omitting this causes a fatal error
    public function toTeams(object $notifiable): TeamsMessage
    {
        return TeamsMessage::create()->card(AdaptiveCard::make()->body([...]));
    }
}
```

`TeamsChannel` checks for the presence of `toTeams()` at runtime and throws an `\InvalidArgumentException` (code `1789350405`) if the notification was dispatched to the `teams` channel without implementing it.

---

### `HasRouteNotificationForTeams` — on the notifiable model

```
Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\HasRouteNotificationForTeams
```

Add this trait to the **notifiable model** (e.g. a `FrontendUser`, a `BackendUser`, or any domain entity using the `Notifiable` trait) to declare that it can provide a Teams webhook URL. The trait enforces that the class implements `routeNotificationForTeams(): string`.

```php
use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\HasRouteNotificationForTeams;

class MyFrontendUser extends AbstractEntity
{
    use Notifiable;
    use HasRouteNotificationForTeams;

    public function routeNotificationForTeams(): string
    {
        return 'https://prod.webhook.office.com/webhookb2/...';
    }
}
```

---

## Routing the Webhook URL

The channel resolves the webhook URL in this order — the first non-empty value wins:

| Priority | Source | How |
|----------|--------|-----|
| 1 (highest) | `TeamsMessage::webhookUrl()` | Call `->webhookUrl('https://…')` when building the message |
| 2 | Notifiable model | Implement `routeNotificationForTeams(): string` (via `HasRouteNotificationForTeams`) |

### Option A — Per-message (highest priority)

Useful when the URL differs per notification, or when the notifiable has no concept of a webhook URL:

```php
public function toTeams(object $notifiable): TeamsMessage
{
    return TeamsMessage::create()
        ->webhookUrl('https://prod.webhook.office.com/webhookb2/...')
        ->card(AdaptiveCard::make()->body([...]));
}
```

### Option B — On the notifiable model (recommended for most cases)

The channel automatically calls `routeNotificationForTeams()` when no URL is set on the message:

```php
class Department extends AbstractEntity
{
    use Notifiable;
    use HasRouteNotificationForTeams;

    public function routeNotificationForTeams(): string
    {
        return $this->teamsWebhookUrl; // stored on the entity
    }
}
```

---

## Building Adaptive Cards

Adaptive Cards are JSON documents that Teams renders natively. Use the fluent PHP builders below — no JSON authoring required.

> **Interactive designer:** [Adaptive Cards Designer](https://adaptivecards.io/designer/)
> **Full schema reference:** [Adaptive Cards Schema Explorer](https://adaptivecards.io/explorer/)
> **Teams-specific guidance:** [Adaptive Cards in Teams — Microsoft Learn](https://learn.microsoft.com/en-us/microsoftteams/platform/task-modules-and-cards/cards/cards-reference#adaptive-card)

---

### AdaptiveCard

The root container for all card content.

```php
AdaptiveCard::make()
    ->version('1.6')                         // optional, default 1.6
    ->body([...elements...])                 // set all body elements at once
    ->add(TextBlock::make('More text'))      // append individual elements
    ->actions([...actions...])               // card-level action buttons
    ->addAction(OpenUrl::make('...', '…'))   // append individual actions
    ->minHeight('200px')                     // optional minimum card height
    ->verticalContentAlignment('Center')     // Top | Center | Bottom
    ->backgroundImage('https://…/bg.png')   // optional background image URL
    ->rtl(true)                              // right-to-left text
    ->speak('Screen reader text');           // accessibility narration
```

---

### TextBlock

Displays plain or formatted text. `wrap(true)` is enabled by default.

```php
TextBlock::make('Hello, world!')
    ->size('Large')                 // Default | Small | Medium | Large | ExtraLarge
    ->weight('Bolder')              // Default | Lighter | Bolder
    ->color('Accent')               // Default | Dark | Light | Accent | Good | Warning | Attention
    ->horizontalAlignment('Center') // Left | Center | Right
    ->fontType('Monospace')         // Default | Monospace
    ->isSubtle(true)                // reduced emphasis
    ->wrap(false)                   // disable wrapping
    ->maxLines(3)                   // truncate after N lines
    ->style('heading')              // default | heading | columnHeader
    ->spacing('Medium')             // None | Small | Default | Medium | Large | ExtraLarge | Padding
    ->separator(true);              // draw a line above this element
```

---

### FactSet & Fact

Renders a two-column key/value table — ideal for structured metadata.

```php
FactSet::make([
    Fact::make('Customer', 'Jane Doe'),
    Fact::make('Order #',  '98765'),
    Fact::make('Total',    '€149.00'),
    Fact::make('Status',   'Paid'),
])->spacing('Medium');
```

---

### Image & ImageSet

```php
// Single image
Image::make('https://example.com/logo.png')
    ->altText('Company logo')
    ->size('Medium')                // Auto | Stretch | Small | Medium | Large
    ->style('Person')               // Default | Person (renders as a circle avatar)
    ->horizontalAlignment('Center')
    ->backgroundColor('#f0f0f0')    // CSS colour for transparent PNGs
    ->width('80px')                 // explicit pixel width
    ->selectAction(OpenUrl::make('Visit', 'https://example.com'));

// Collection of images
ImageSet::make([
    Image::make('https://example.com/a.png'),
    Image::make('https://example.com/b.png'),
    Image::make('https://example.com/c.png'),
])->size('Small');
```

---

### Container

Groups elements with shared styling or a background.

```php
Container::make([
    TextBlock::make('Section Title')->weight('Bolder'),
    TextBlock::make('Body text goes here.')->wrap(true),
    FactSet::make([Fact::make('Key', 'Value')]),
])
->style('emphasis')                 // default | emphasis | good | attention | warning | accent
->bleed(true)                       // extend to cover surrounding padding
->minHeight('100px')
->verticalContentAlignment('Top')   // Top | Center | Bottom
->selectAction(OpenUrl::make('Open', 'https://example.com'));
```

---

### ColumnSet & Column

Divides content into side-by-side columns.

```php
ColumnSet::make([
    Column::make([
        Image::make('https://example.com/avatar.png')
            ->style('Person')
            ->size('Small'),
    ])->width('auto'),

    Column::make([
        TextBlock::make('Jane Doe')->weight('Bolder'),
        TextBlock::make('Engineering Lead')->isSubtle(true)->spacing('None'),
    ])->width('stretch'),
])->spacing('Medium');
```

Column `width` accepts:
- `"auto"` — shrinks to fit content
- `"stretch"` — fills remaining space
- `"50px"` — explicit pixel width
- `"1"`, `"2"`, … — proportional weight

---

### RichTextBlock

Inline-styled text in a single paragraph. Mix weights, colours, and sizes without separate `TextBlock` elements.

```php
RichTextBlock::make([
    ['type' => 'TextRun', 'text' => 'Deploy status: '],
    ['type' => 'TextRun', 'text' => 'SUCCESS', 'color' => 'Good', 'weight' => 'Bolder'],
])
->addInline(['type' => 'TextRun', 'text' => ' — no issues found.', 'isSubtle' => true]);
```

---

### ActionSet

Embeds action buttons at a specific position in the body (rather than always at the bottom).

```php
ActionSet::make([
    OpenUrl::make('View in browser', 'https://example.com'),
    Submit::make('Acknowledge', ['action' => 'ack']),
]);
```

---

### Actions

All actions accept common properties: `->id()`, `->style()`, `->iconUrl()`, `->tooltip()`, `->isEnabled()`, `->mode()`.

#### `Action\OpenUrl` — open a URL

```php
use Lex\Notifications\MicrosoftTeams\Message\Action\OpenUrl;

OpenUrl::make('View Order', 'https://example.com/orders/123')
    ->style('positive')   // default | positive | destructive
    ->iconUrl('https://example.com/icons/view.png')
    ->tooltip('Opens the order detail page');
```

#### `Action\Submit` — submit form data

```php
use Lex\Notifications\MicrosoftTeams\Message\Action\Submit;

Submit::make('Approve', ['action' => 'approve', 'orderId' => 123])
    ->style('positive')
    ->associatedInputs('Auto'); // Auto | None
```

#### `Action\ShowCard` — reveal an inline card

```php
use Lex\Notifications\MicrosoftTeams\Message\Action\ShowCard;

ShowCard::make('Show Details',
    AdaptiveCard::make()->body([
        TextBlock::make('Here are the full details…')->wrap(true),
    ])
);
```

#### `Action\ToggleVisibility` — show/hide elements

```php
use Lex\Notifications\MicrosoftTeams\Message\Action\ToggleVisibility;

// Toggle by element id
ToggleVisibility::make('Toggle Details', ['detailsContainer']);

// Force a specific state
ToggleVisibility::make('Show Details', [
    ['elementId' => 'detailsContainer', 'isVisible' => true],
    ['elementId' => 'summaryContainer',  'isVisible' => false],
]);
```

---

## Real-World Examples

### Order Confirmation

Notifies the fulfilment team when a new order is placed.

```php
use Lex\Notifications\Notification;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\Action\OpenUrl;
use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Element\Fact;
use Lex\Notifications\MicrosoftTeams\Message\Element\FactSet;
use Lex\Notifications\MicrosoftTeams\Message\Element\TextBlock;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

class OrderPlacedNotification extends Notification
{
    use CanSendToTeams;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return [TeamsChannel::CHANNEL];
    }

    public function toTeams(object $notifiable): TeamsMessage
    {
        return TeamsMessage::create()
            ->card(
                AdaptiveCard::make()
                    ->body([
                        TextBlock::make('New Order Received')
                            ->size('Large')
                            ->weight('Bolder')
                            ->color('Accent'),
                        TextBlock::make('Order #' . $this->order->getNumber())
                            ->isSubtle(true)
                            ->spacing('None'),
                        FactSet::make([
                            Fact::make('Customer', $this->order->getCustomerName()),
                            Fact::make('Items',    (string) $this->order->getItemCount()),
                            Fact::make('Total',    $this->order->getFormattedTotal()),
                            Fact::make('Payment',  $this->order->getPaymentMethod()),
                            Fact::make('Shipping', $this->order->getShippingMethod()),
                        ])->spacing('Medium'),
                    ])
                    ->actions([
                        OpenUrl::make('View Order', $this->order->getBackendUrl())
                            ->style('positive'),
                        OpenUrl::make('View Customer', $this->order->getCustomerUrl()),
                    ])
            );
    }
}
```

The notifiable (e.g. the fulfilment channel entity) provides the webhook URL:

```php
class FulfilmentChannel extends AbstractEntity
{
    use Notifiable;
    use HasRouteNotificationForTeams;

    public function routeNotificationForTeams(): string
    {
        return $this->teamsWebhookUrl;
    }
}
```

```php
$this->notificationDispatcher->send($fulfilmentChannel, new OrderPlacedNotification($order));
```

---

### Server Alert

Sends a critical alert with severity colour coding when a monitor threshold is exceeded.

```php
use Lex\Notifications\Notification;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\Action\OpenUrl;
use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Element\Fact;
use Lex\Notifications\MicrosoftTeams\Message\Element\FactSet;
use Lex\Notifications\MicrosoftTeams\Message\Element\TextBlock;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

class ServerAlertNotification extends Notification
{
    use CanSendToTeams;

    public function __construct(
        private readonly string $serverName,
        private readonly string $metric,
        private readonly string $value,
        private readonly string $threshold,
        private readonly string $dashboardUrl,
        private readonly string $webhookUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return [TeamsChannel::CHANNEL];
    }

    public function toTeams(object $notifiable): TeamsMessage
    {
        return TeamsMessage::create()
            ->webhookUrl($this->webhookUrl) // URL embedded directly — no notifiable routing needed
            ->card(
                AdaptiveCard::make()
                    ->body([
                        TextBlock::make('CRITICAL — ' . $this->serverName)
                            ->size('Large')
                            ->weight('Bolder')
                            ->color('Attention'),
                        TextBlock::make('Threshold exceeded. Immediate attention required.')
                            ->wrap(true)
                            ->isSubtle(true),
                        FactSet::make([
                            Fact::make('Metric',    $this->metric),
                            Fact::make('Value',     $this->value),
                            Fact::make('Threshold', $this->threshold),
                            Fact::make('Time',      (new \DateTimeImmutable())->format('Y-m-d H:i:s T')),
                        ])->spacing('Medium'),
                    ])
                    ->actions([
                        OpenUrl::make('Open Dashboard', $this->dashboardUrl)
                            ->style('destructive'),
                    ])
            );
    }
}
```

```php
// Dispatch from a Scheduler task — no special notifiable needed
$this->notificationDispatcher->send(new \stdClass(), new ServerAlertNotification(
    serverName:   'prod-web-01',
    metric:       'CPU Usage',
    value:        '98%',
    threshold:    '85%',
    dashboardUrl: 'https://grafana.internal/d/servers',
    webhookUrl:   'https://prod.webhook.office.com/webhookb2/...',
));
```

---

### New User Registration with Avatar

Posts a profile card with a person-style avatar when a new frontend user registers.

```php
use Lex\Notifications\Notification;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\Action\OpenUrl;
use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Element\Column;
use Lex\Notifications\MicrosoftTeams\Message\Element\ColumnSet;
use Lex\Notifications\MicrosoftTeams\Message\Element\Fact;
use Lex\Notifications\MicrosoftTeams\Message\Element\FactSet;
use Lex\Notifications\MicrosoftTeams\Message\Element\Image;
use Lex\Notifications\MicrosoftTeams\Message\Element\TextBlock;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

class UserRegisteredNotification extends Notification
{
    use CanSendToTeams;

    public function __construct(private readonly FrontendUser $user) {}

    public function via(object $notifiable): array
    {
        return [TeamsChannel::CHANNEL];
    }

    public function toTeams(object $notifiable): TeamsMessage
    {
        $avatarUrl = sprintf(
            'https://ui-avatars.com/api/?name=%s&size=128&background=0D3880&color=fff',
            urlencode($this->user->getName()),
        );

        return TeamsMessage::create()
            ->card(
                AdaptiveCard::make()
                    ->body([
                        TextBlock::make('New User Registered')
                            ->size('Large')
                            ->weight('Bolder'),
                        ColumnSet::make([
                            Column::make([
                                Image::make($avatarUrl)
                                    ->style('Person')
                                    ->size('Small'),
                            ])->width('auto'),
                            Column::make([
                                TextBlock::make($this->user->getName())
                                    ->weight('Bolder'),
                                TextBlock::make($this->user->getEmail())
                                    ->isSubtle(true)
                                    ->spacing('None'),
                            ])->width('stretch'),
                        ])->spacing('Medium'),
                        FactSet::make([
                            Fact::make('Username',   $this->user->getUsername()),
                            Fact::make('Registered', (new \DateTimeImmutable())->format('d/m/Y H:i')),
                            Fact::make('User group', $this->user->getUsergroup()->first()?->getTitle() ?? '—'),
                        ]),
                    ])
                    ->actions([
                        OpenUrl::make('Edit user', $this->user->getBackendEditUrl()),
                    ])
            );
    }
}
```

---

### Content Approval Request

Sent to the editorial team when an editor submits a page for review. Uses `ToggleVisibility` to reveal the full editorial note on demand.

```php
use Lex\Notifications\Notification;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\Action\OpenUrl;
use Lex\Notifications\MicrosoftTeams\Message\Action\ToggleVisibility;
use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Element\Container;
use Lex\Notifications\MicrosoftTeams\Message\Element\Fact;
use Lex\Notifications\MicrosoftTeams\Message\Element\FactSet;
use Lex\Notifications\MicrosoftTeams\Message\Element\TextBlock;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;

class ContentApprovalRequestNotification extends Notification
{
    use CanSendToTeams;

    public function __construct(
        private readonly PageRecord $page,
        private readonly BackendUser $submitter,
        private readonly string $note,
    ) {}

    public function via(object $notifiable): array
    {
        return [TeamsChannel::CHANNEL];
    }

    public function toTeams(object $notifiable): TeamsMessage
    {
        return TeamsMessage::create()
            ->card(
                AdaptiveCard::make()
                    ->body([
                        TextBlock::make('Content Approval Required')
                            ->size('Large')
                            ->weight('Bolder'),
                        FactSet::make([
                            Fact::make('Page',         $this->page->getTitle()),
                            Fact::make('Submitted by', $this->submitter->getRealName()),
                            Fact::make('URL',          $this->page->getSlug()),
                        ])->spacing('Medium'),
                        Container::make([
                            TextBlock::make('Editorial Note')->weight('Bolder'),
                            TextBlock::make($this->note)->wrap(true)->isSubtle(true),
                        ])
                        ->id('editorialNote')
                        ->isVisible(false)
                        ->style('emphasis')
                        ->spacing('Medium'),
                    ])
                    ->actions([
                        OpenUrl::make('Preview page', $this->page->getFrontendPreviewUrl()),
                        OpenUrl::make('Open in backend', $this->page->getBackendUrl()),
                        ToggleVisibility::make('Show editorial note', ['editorialNote']),
                    ])
            );
    }
}
```

---

### Multi-Channel Notification (Teams + Mail)

Both traits can be composed freely. Include `teams` alongside any other channel in `via()`.

```php
use Lex\Notifications\Notification;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel;
use Lex\Notifications\MicrosoftTeams\Domain\Model\Ability\CanSendToTeams;
use Lex\Notifications\MicrosoftTeams\Message\AdaptiveCard;
use Lex\Notifications\MicrosoftTeams\Message\Element\TextBlock;
use Lex\Notifications\MicrosoftTeams\Message\TeamsMessage;
use TYPO3\CMS\Core\Mail\MailMessage;

class DeploymentFinishedNotification extends Notification
{
    use CanSendToTeams;

    public function __construct(
        private readonly string $environment,
        private readonly string $version,
        private readonly bool $success,
    ) {}

    public function via(object $notifiable): array
    {
        return [TeamsChannel::CHANNEL, NotificationChannel::CHANNEL_MAIL];
    }

    public function toTeams(object $notifiable): TeamsMessage
    {
        $status = $this->success ? 'SUCCESS' : 'FAILED';
        $color  = $this->success ? 'Good'    : 'Attention';

        return TeamsMessage::create()
            ->card(
                AdaptiveCard::make()
                    ->body([
                        TextBlock::make("Deploy {$status} — {$this->environment}")
                            ->size('Large')
                            ->weight('Bolder')
                            ->color($color),
                        TextBlock::make("Version {$this->version} deployed to {$this->environment}.")
                            ->wrap(true),
                    ])
            );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Deploy {$this->environment}: " . ($this->success ? 'SUCCESS' : 'FAILED'))
            ->text("Version {$this->version} was deployed to {$this->environment}.");
    }
}
```

---

## References

| Resource | Link |
|----------|------|
| Set up a Teams Incoming Webhook | [Microsoft Learn — Incoming Webhooks](https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook) |
| Adaptive Card schema explorer | [adaptivecards.io/explorer](https://adaptivecards.io/explorer/) |
| Adaptive Cards interactive designer | [adaptivecards.io/designer](https://adaptivecards.io/designer/) |
| Adaptive Cards in Microsoft Teams | [Microsoft Learn — Cards reference](https://learn.microsoft.com/en-us/microsoftteams/platform/task-modules-and-cards/cards/cards-reference#adaptive-card) |
| Teams Workflows (Power Automate) | [Microsoft Learn — Power Automate & Teams](https://learn.microsoft.com/en-us/power-automate/teams/teams-app-create) |
| Message card object reference | [Microsoft Learn — Message card reference](https://learn.microsoft.com/en-us/outlook/actionable-messages/message-card-reference) |
| lex_notifications (core) | [GitHub — agencelex/typo3-notifications](https://github.com/agencelex/typo3-notifications) |
