<?php
namespace Neos\Fusion\FusionObjects\Result;

class TagResult
{

    /**
     * @var string
     */
    protected $tagName;

    /**
     * @var mixed
     */
    protected $attributes;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var bool
     */
    protected $omitClosingTag;

    /**
     * @var bool
     */
    protected $selfClosingTag;

    /**
     * @var bool
     */
    protected $allowEmptyAttributes;

    /**
     * TagResult constructor.
     * @param string $tagName
     * @param array $attributes
     * @param string $content
     * @param bool $omitClosingTag
     * @param bool $selfClosingTag
     * @param bool $allowEmptyAttributes
     */
    public function __construct(string $tagName, $attributes, $content, bool $omitClosingTag, bool $selfClosingTag, bool $allowEmptyAttributes)
    {
        $this->tagName = $tagName;
        $this->attributes = $attributes;
        $this->content = $content;
        $this->omitClosingTag = $omitClosingTag;
        $this->selfClosingTag = $selfClosingTag;
        $this->allowEmptyAttributes = $allowEmptyAttributes;
    }

    public function __toString()
    {
        if (is_iterable($this->attributes)) {
            $renderedAttributes = self::renderAttributes($this->attributes, $this->allowEmptyAttributes);
        } else {
            $renderedAttributes = (string)$this->attributes;
        }
        return '<' . $this->tagName . $renderedAttributes . ($this->selfClosingTag ? ' /' : '') . '>' . (!$this->omitClosingTag && !$this->selfClosingTag ? $this->content . '</' . $this->tagName . '>' : '');
    }

    /**
     * Render the tag attributes for the given key->values as atring,
     * if an value is an iterable it will be concatenated with spaces as seperator
     *
     * @param iterable $attributes
     * @param bool $allowEmpty
     */
    protected function renderAttributes(iterable $attributes, $allowEmpty = true): string
    {
        $renderedAttributes = '';
        foreach ($attributes as $attributeName => $attributeValue) {
            if ($attributeValue === null || $attributeValue === false) {
                continue;
            }
            $encodedAttributeName = htmlspecialchars($attributeName, ENT_COMPAT, 'UTF-8', false);
            if ($attributeValue === true || $attributeValue === '') {
                $renderedAttributes .= ' ' . $encodedAttributeName . ($allowEmpty ? '' : '=""');
            } else {
                if (is_array($attributeValue)) {
                    $joinedAttributeValue = '';
                    foreach ($attributeValue as $attributeValuePart) {
                        if ((string)$attributeValuePart !== '') {
                            $joinedAttributeValue .= ' ' . trim($attributeValuePart);
                        }
                    }
                    $attributeValue = trim($joinedAttributeValue);
                }
                $encodedAttributeValue = htmlspecialchars($attributeValue, ENT_COMPAT, 'UTF-8', false);
                $renderedAttributes .= ' ' . $encodedAttributeName . '="' . $encodedAttributeValue . '"';
            }
        }
        return $renderedAttributes;
    }
}
