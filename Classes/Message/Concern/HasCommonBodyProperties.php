<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Concern;

/**
 * Common properties shared by all Adaptive Card body elements.
 *
 * @see https://adaptivecards.io/explorer/
 */
trait HasCommonBodyProperties
{
    private ?string $id = null;
    private ?bool $isVisible = null;
    private ?bool $separator = null;
    private ?string $spacing = null;
    private ?string $height = null;

    /**
     * Unique element identifier. Useful for ToggleVisibility actions.
     */
    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Control initial visibility of this element.
     */
    public function isVisible(bool $visible = true): static
    {
        $this->isVisible = $visible;
        return $this;
    }

    /**
     * Draw a separating line above this element when true.
     */
    public function separator(bool $separator = true): static
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Amount of spacing above this element.
     *
     * Allowed values: None, Small, Default, Medium, Large, ExtraLarge, Padding
     */
    public function spacing(string $spacing): static
    {
        $this->spacing = $spacing;
        return $this;
    }

    /**
     * Height of this element.
     *
     * Allowed values: auto, stretch
     */
    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    protected function buildCommonBodyProperties(): array
    {
        $props = [];

        if ($this->id !== null) {
            $props['id'] = $this->id;
        }
        if ($this->isVisible !== null) {
            $props['isVisible'] = $this->isVisible;
        }
        if ($this->separator !== null) {
            $props['separator'] = $this->separator;
        }
        if ($this->spacing !== null) {
            $props['spacing'] = $this->spacing;
        }
        if ($this->height !== null) {
            $props['height'] = $this->height;
        }

        return $props;
    }
}
