<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Element;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonBodyProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Displays a collection of Image elements in a tiled layout.
 *
 * @see https://adaptivecards.io/explorer/ImageSet.html
 *
 * Example:
 *   ImageSet::make([
 *       Image::make('https://example.com/a.png'),
 *       Image::make('https://example.com/b.png'),
 *   ])->size('Small')
 */
class ImageSet implements Renderable
{
    use HasCommonBodyProperties;

    protected ?string $imageSize = null;

    /** @param Image[] $images */
    protected function __construct(protected array $images = []) {}

    /** @param Image[] $images */
    public static function make(array $images = []): static
    {
        return new static($images);
    }

    public function add(Image ...$images): static
    {
        $this->images = array_merge($this->images, $images);
        return $this;
    }

    /**
     * Size applied to every image in the set.
     *
     * Allowed values: Auto, Stretch, Small, Medium, Large
     */
    public function size(string $size): static
    {
        $this->imageSize = $size;
        return $this;
    }

    public function toArray(): array
    {
        $array = array_merge(
            [
                'type' => 'ImageSet',
                'images' => array_map(static fn(Image $img) => $img->toArray(), $this->images),
            ],
            $this->buildCommonBodyProperties(),
        );

        if ($this->imageSize !== null) {
            $array['imageSize'] = $this->imageSize;
        }

        return $array;
    }
}
