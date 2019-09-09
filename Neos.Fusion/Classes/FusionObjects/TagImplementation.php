<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * A Fusion object for tag based content
 *
 * //tsPath attributes An array with attributes for this tag (optional)
 * //tsPath content Content for the body of the tag (optional)
 * @api
 */
class TagImplementation extends AbstractFusionObject
{
    /**
     * List of self-closing tags
     *
     * @var array
     */
    protected static $SELF_CLOSING_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    /**
     * The tag name (e.g. 'body', 'head', 'title', ...)
     *
     * @return string
     */
    public function getTagName()
    {
        $tagName = $this->fusionValue('tagName');
        if ($tagName === null) {
            $tagName = 'div';
        }
        return $tagName;
    }

    /**
     * Whether to leave out the closing tag (defaults to false)
     *
     * @return boolean
     */
    public function getOmitClosingTag()
    {
        return $this->fusionValue('omitClosingTag');
    }

    /**
     * Whether to force a self closing tag (e.g. '<div />')
     *
     * @param string $tagName
     * @return boolean
     */
    public function isSelfClosingTag($tagName)
    {
        return in_array($tagName, self::$SELF_CLOSING_TAGS, true) || (boolean)$this->fusionValue('selfClosingTag');
    }

    /**
     * Return a tag
     *
     * @return mixed
     */
    public function evaluate()
    {
        $tagName = $this->getTagName();
        $omitClosingTag = $this->getOmitClosingTag();
        $selfClosingTag = $this->isSelfClosingTag($tagName);
        $content = '';
        if (!$omitClosingTag && !$selfClosingTag) {
            $content = $this->fusionValue('content');
        }
        $attributes = $this->fusionValue('attributes');
        if (is_array($attributes)) {
            $renderedAttributes = self::renderAttributes($attributes, true);
        } else {
            $renderedAttributes = (string)$attributes;
        }
        return '<' . $tagName . $renderedAttributes . ($selfClosingTag ? ' /' : '') . '>' . (!$omitClosingTag && !$selfClosingTag ? $content . '</' . $tagName . '>' : '');
    }

    /**
     * @param array $attributes
     * @param bool $allowEmpty
     */
    public static function renderAttributes(array $attributes, $allowEmpty = true): string
    {
        $renderedAttributes = '';
        foreach ($attributes as $attributeName => $attributeValue) {
            $encodedAttributeName = htmlspecialchars($attributeName, ENT_COMPAT, 'UTF-8', false);
            if ($attributeValue === null || $attributeValue === false) {
                // No op
            } elseif ($attributeValue === true || $attributeValue === '') {
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
