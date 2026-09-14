<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Concern;

/**
 * Common properties shared by all Adaptive Card actions.
 *
 * @see https://adaptivecards.io/explorer/Action.OpenUrl.html
 */
trait HasCommonActionProperties
{
    private ?string $id = null;
    private ?string $iconUrl = null;
    private ?string $style = null;
    private ?string $tooltip = null;
    private ?bool $isEnabled = null;
    private ?string $mode = null;

    /**
     * Unique action identifier.
     */
    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * URL of an icon to display on the action button.
     */
    public function iconUrl(string $url): static
    {
        $this->iconUrl = $url;
        return $this;
    }

    /**
     * Visual style of the action button.
     *
     * Allowed values: default, positive, destructive
     */
    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * Tooltip text shown on hover.
     */
    public function tooltip(string $tooltip): static
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    /**
     * Disable the action when set to false.
     */
    public function isEnabled(bool $enabled = true): static
    {
        $this->isEnabled = $enabled;
        return $this;
    }

    /**
     * Position of the action.
     *
     * Allowed values: primary, secondary
     */
    public function mode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    protected function buildCommonActionProperties(): array
    {
        $props = [];

        if ($this->id !== null) {
            $props['id'] = $this->id;
        }
        if ($this->iconUrl !== null) {
            $props['iconUrl'] = $this->iconUrl;
        }
        if ($this->style !== null) {
            $props['style'] = $this->style;
        }
        if ($this->tooltip !== null) {
            $props['tooltip'] = $this->tooltip;
        }
        if ($this->isEnabled !== null) {
            $props['isEnabled'] = $this->isEnabled;
        }
        if ($this->mode !== null) {
            $props['mode'] = $this->mode;
        }

        return $props;
    }
}
