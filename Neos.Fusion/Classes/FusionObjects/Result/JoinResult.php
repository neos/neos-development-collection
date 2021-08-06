<?php
namespace Neos\Fusion\FusionObjects\Result;

class JoinResult
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

    public function __toString()
    {
        return implode($this->glue, $this->items);
    }
}
