<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Renders a paragraph of richly formatted text using inline TextRun elements.
 *
 * Each inline element is an associative array following the TextRun spec:
 *   ['type' => 'TextRun', 'text' => '...', 'color' => 'Accent', 'weight' => 'Bolder', ...]
 *
 * You may also use a plain string, which is promoted to `['type' => 'TextRun', 'text' => '...']`.
 *
 * @see https://adaptivecards.io/explorer/RichTextBlock.html
 *
 * Example:
 *   RichTextBlock::make([
 *       ['type' => 'TextRun', 'text' => 'Status: '],
 *       ['type' => 'TextRun', 'text' => 'Active', 'color' => 'Good', 'weight' => 'Bolder'],
 *   ])
 */
class RichTextBlock implements Renderable
{
    use HasCommonBodyProperties;

    protected ?string $horizontalAlignment = null;

    /** @param array<string|array<string, mixed>> $inlines */
    protected function __construct(private array $inlines = []) {}

    /** @param array<string|array<string, mixed>> $inlines */
    public static function make(array $inlines = []): static
    {
        return new static($inlines);
    }

    /** @param array<string, mixed>|string $inline */
    public function addInline(array|string $inline): static
    {
        $this->inlines[] = $inline;
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

    public function toArray(): array
    {
        $normalised = array_map(
            static fn($inline) => is_string($inline)
                ? ['type' => 'TextRun', 'text' => $inline]
                : $inline,
            $this->inlines,
        );

        $array = array_merge(
            ['type' => 'RichTextBlock', 'inlines' => $normalised],
            $this->buildCommonBodyProperties(),
        );

        if ($this->horizontalAlignment !== null) {
            $array['horizontalAlignment'] = $this->horizontalAlignment;
        }

        return $array;
    }
}
