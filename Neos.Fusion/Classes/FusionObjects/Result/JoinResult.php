<?php
namespace Neos\Fusion\FusionObjects\Result;

use function React\Promise\reduce;

class JoinResult implements HtmlStringable
{

    /**
     * @var array
     */
    protected $items;

    /**
     * @var string
     */
    protected $glue;

    /**
     * @param string $tagName
     * @param string|null $content
     * @param array|null $attributes
     * @param bool $selfClosingTag
     * @param bool $omitClosingTag
     */
    public function __construct(string $glue = '', array $items)
    {
        $this->items = $items;
        $this->glue = $glue;
    }

    function toHtmlString()
    {
        return implode(
            $this->glue,
            array_map(
                function ($item) {
                    return ($item instanceof HtmlStringable) ? $item->toHtmlString() : htmlspecialchars((string) $item);
                },
                $this->items
            )
        );
    }

    public function __toString()
    {
        return implode($this->glue, $this->items);
    }
}
