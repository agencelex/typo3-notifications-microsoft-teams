<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Divides the card into multiple columns side by side.
 *
 * @see https://adaptivecards.io/explorer/ColumnSet.html
 *
 * Example:
 *   ColumnSet::make([
 *       Column::make([Image::make('https://example.com/avatar.png')->style('Person')])->width('auto'),
 *       Column::make([
 *           TextBlock::make('Jane Doe')->weight('Bolder'),
 *           TextBlock::make('Engineering lead')->isSubtle(true)->spacing('None'),
 *       ])->width('stretch'),
 *   ])
 */
final class ColumnSet implements Renderable
{
    use HasCommonBodyProperties;

    private ?bool $bleed = null;
    private ?string $horizontalAlignment = null;
    private ?string $minHeight = null;
    private ?Renderable $selectAction = null;
    private ?string $style = null;

    /** @param Column[] $columns */
    private function __construct(private array $columns = []) {}

    /** @param Column[] $columns */
    public static function make(array $columns = []): static
    {
        return new static($columns);
    }

    public function add(Column ...$columns): static
    {
        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }

    /**
     * Extend the column set to cover surrounding padding when true.
     */
    public function bleed(bool $bleed = true): static
    {
        $this->bleed = $bleed;
        return $this;
    }

    /**
     * Horizontal alignment of the column set.
     *
     * Allowed values: Left, Center, Right
     */
    public function horizontalAlignment(string $alignment): static
    {
        $this->horizontalAlignment = $alignment;
        return $this;
    }

    /**
     * Minimum height of the column set (CSS value, e.g. "100px").
     */
    public function minHeight(string $minHeight): static
    {
        $this->minHeight = $minHeight;
        return $this;
    }

    /**
     * Action triggered when the column set is clicked.
     */
    public function selectAction(Renderable $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    /**
     * Container style for background colouring.
     *
     * Allowed values: default, emphasis, good, attention, warning, accent
     */
    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    public function toArray(): array
    {
        $array = array_merge(
            [
                'type' => 'ColumnSet',
                'columns' => array_map(static fn(Column $col) => $col->toArray(), $this->columns),
            ],
            $this->buildCommonBodyProperties(),
        );

        if ($this->bleed !== null) {
            $array['bleed'] = $this->bleed;
        }
        if ($this->horizontalAlignment !== null) {
            $array['horizontalAlignment'] = $this->horizontalAlignment;
        }
        if ($this->minHeight !== null) {
            $array['minHeight'] = $this->minHeight;
        }
        if ($this->selectAction !== null) {
            $array['selectAction'] = $this->selectAction->toArray();
        }
        if ($this->style !== null) {
            $array['style'] = $this->style;
        }

        return $array;
    }
}
