<?php declare(strict_types=1);

namespace Lex\Notifications\MicrosoftTeams\Message\Action;

use Lex\Notifications\MicrosoftTeams\Message\Concern\HasCommonActionProperties;
use Lex\Notifications\MicrosoftTeams\Message\Contract\Renderable;

/**
 * Gathers form input and submits the data back to the client.
 *
 * @see https://adaptivecards.io/explorer/Action.Submit.html
 *
 * Example:
 *   Action\Submit::make('Confirm', ['action' => 'confirm', 'orderId' => 123])
 */
final class Submit implements Renderable
{
    use HasCommonActionProperties;

    private ?string $associatedInputs = null;

    private function __construct(
        private string $title,
        private array $data = [],
    ) {}

    public static function make(string $title, array $data = []): static
    {
        return new static($title, $data);
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function data(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Controls which inputs are included when the action is submitted.
     *
     * Allowed values: Auto, None
     */
    public function associatedInputs(string $associatedInputs): static
    {
        $this->associatedInputs = $associatedInputs;
        return $this;
    }

    public function toArray(): array
    {
        $array = array_merge(
            [
                'type' => 'Action.Submit',
                'title' => $this->title,
            ],
            $this->buildCommonActionProperties(),
        );

        if (!empty($this->data)) {
            $array['data'] = $this->data;
        }
        if ($this->associatedInputs !== null) {
            $array['associatedInputs'] = $this->associatedInputs;
        }

        return $array;
    }
}
