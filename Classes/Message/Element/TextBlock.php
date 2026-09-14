<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Displays text in an Adaptive Card body.
 *
 * @see https://adaptivecards.io/explorer/TextBlock.html
 *
 * Example:
 *   TextBlock::make('Hello World')
 *       ->size('Large')
 *       ->weight('Bolder')
 *       ->color('Accent')
 *       ->wrap(true)
 */
class TextBlock implements Renderable
{
    use HasCommonBodyProperties;

    protected ?string $color = null;
    protected ?string $fontType = null;
    protected ?string $horizontalAlignment = null;
    protected ?bool $isSubtle = null;
    protected ?int $maxLines = null;
    protected ?string $size = null;
    protected ?string $style = null;
    protected ?string $weight = null;
    protected ?bool $wrap = true;

    protected function __construct(protected string $text) {}

    public static function make(string $text): static
    {
        return new static($text);
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Text colour.
     *
     * Allowed values: Default, Dark, Light, Accent, Good, Warning, Attention
     */
    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Font type.
     *
     * Allowed values: Default, Monospace
     */
    public function fontType(string $fontType): static
    {
        $this->fontType = $fontType;
        return $this;
    }

    /**
     * Horizontal alignment.
     *
     * Allowed values: Left, Center, Right
     */
    public function horizontalAlignment(string $alignment): static
    {
        $this->horizontalAlignment = $alignment;
        return $this;
    }

    /**
     * Display text with reduced emphasis when true.
     */
    public function isSubtle(bool $subtle = true): static
    {
        $this->isSubtle = $subtle;
        return $this;
    }

    /**
     * Limit the number of lines rendered.
     */
    public function maxLines(int $lines): static
    {
        $this->maxLines = $lines;
        return $this;
    }

    /**
     * Text size.
     *
     * Allowed values: Default, Small, Medium, Large, ExtraLarge
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Text block style.
     *
     * Allowed values: default, heading, columnHeader
     */
    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * Font weight.
     *
     * Allowed values: Default, Lighter, Bolder
     */
    public function weight(string $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Allow the text to wrap onto multiple lines when true.
     */
    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;
        return $this;
    }

    public function toArray(): array
    {
        $array = array_merge(
            ['type' => 'TextBlock', 'text' => $this->text],
            $this->buildCommonBodyProperties(),
        );

        if ($this->color !== null) {
            $array['color'] = $this->color;
        }
        if ($this->fontType !== null) {
            $array['fontType'] = $this->fontType;
        }
        if ($this->horizontalAlignment !== null) {
            $array['horizontalAlignment'] = $this->horizontalAlignment;
        }
        if ($this->isSubtle !== null) {
            $array['isSubtle'] = $this->isSubtle;
        }
        if ($this->maxLines !== null) {
            $array['maxLines'] = $this->maxLines;
        }
        if ($this->size !== null) {
            $array['size'] = $this->size;
        }
        if ($this->style !== null) {
            $array['style'] = $this->style;
        }
        if ($this->weight !== null) {
            $array['weight'] = $this->weight;
        }
        if ($this->wrap !== null) {
            $array['wrap'] = $this->wrap;
        }

        return $array;
    }
}
