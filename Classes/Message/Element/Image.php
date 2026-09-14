<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Displays an image in an Adaptive Card body.
 *
 * @see https://adaptivecards.io/explorer/Image.html
 *
 * Example:
 *   Image::make('https://example.com/logo.png')
 *       ->altText('Company logo')
 *       ->size('Medium')
 *       ->style('Person')
 */
class Image implements Renderable
{
    use HasCommonBodyProperties;

    protected ?string $altText = null;
    protected ?string $backgroundColor = null;
    protected ?string $horizontalAlignment = null;
    protected ?Renderable $selectAction = null;
    protected ?string $size = null;
    protected ?string $style = null;
    protected ?string $width = null;

    private function __construct(protected string $url) {}

    public static function make(string $url): static
    {
        return new static($url);
    }

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Alternate text for accessibility.
     */
    public function altText(string $altText): static
    {
        $this->altText = $altText;
        return $this;
    }

    /**
     * Background colour for transparent images (CSS colour value).
     */
    public function backgroundColor(string $color): static
    {
        $this->backgroundColor = $color;
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
     * Action triggered when the image is clicked.
     */
    public function selectAction(Renderable $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    /**
     * Image display size.
     *
     * Allowed values: Auto, Stretch, Small, Medium, Large
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Image style.
     *
     * Allowed values: Default, Person (renders as a circle)
     */
    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * Explicit pixel width (e.g. "80px").
     */
    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function toArray(): array
    {
        $array = array_merge(
            ['type' => 'Image', 'url' => $this->url],
            $this->buildCommonBodyProperties(),
        );

        if ($this->altText !== null) {
            $array['altText'] = $this->altText;
        }
        if ($this->backgroundColor !== null) {
            $array['backgroundColor'] = $this->backgroundColor;
        }
        if ($this->horizontalAlignment !== null) {
            $array['horizontalAlignment'] = $this->horizontalAlignment;
        }
        if ($this->selectAction !== null) {
            $array['selectAction'] = $this->selectAction->toArray();
        }
        if ($this->size !== null) {
            $array['size'] = $this->size;
        }
        if ($this->style !== null) {
            $array['style'] = $this->style;
        }
        if ($this->width !== null) {
            $array['width'] = $this->width;
        }

        return $array;
    }
}
