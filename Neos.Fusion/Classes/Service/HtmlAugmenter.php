<?php
namespace Neos\Fusion\Service;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A tool that can augment HTML for example by adding arbitrary attributes.
 * This is used in order to add meta data arguments to content elements in the Backend.
 *
 * Usage:
 *
 * $html = '<div foo="existing">Some HTML code</div>';
 * $result = (new HtmlAugmenter())->addAttributes($html, array('foo' => 'bar', 'bar' => 'baz'));
 *
 * // will return '<div foo="existing bar" bar="baz">Some HTML code</div'
 *
 * @Flow\Scope("singleton")
 */
class HtmlAugmenter
{
    use RenderAttributesTrait;

    /**
     * Adds the given $attributes to the $html by augmenting the root element.
     * Attributes are merged with the existing root element's attributes.
     * If no unique root node can be determined, a wrapping tag is added with all the given attributes. The name of this
     * tag can be specified with $fallbackTagName.
     *
     * @param string $html The HTML code to augment
     * @param array $attributes Attributes to be added to the root element in the format array('<attribute-name>' => '<attribute-value>', ...)
     * @param string $fallbackTagName The root element tag name if one needs to be added
     * @param array $exclusiveAttributes A list of lowercase(!) attribute names that should be exclusive to the root element. If the existing root element contains one of these a new root element is wrapped
     * @param bool $allowEmptyAttributes Allow empty attributes without a value
     */
    public function addAttributes($html, array $attributes, $fallbackTagName = 'div', array $exclusiveAttributes = null, bool $allowEmptyAttributes = true)
    {
        if ($attributes === []) {
            return $html;
        }
        $rootElement = $this->getHtmlRootElement($html);
        if ($rootElement === null || $this->elementHasAttributes($rootElement, $exclusiveAttributes)) {
            return sprintf('<%s%s>%s</%s>', $fallbackTagName, $this->renderAttributes($attributes, $allowEmptyAttributes), $html, $fallbackTagName);
        }
        $this->mergeAttributes($rootElement, $attributes);
        return preg_replace('/<(' . $rootElement->nodeName . ')\b[^>]*>/xi', '<$1' . addcslashes($this->renderAttributes($attributes, $allowEmptyAttributes), '\\\$') . '>', $html, 1);
    }

    /**
     * Detects a unique root tag in the given $html string and returns its DOMNode representation - or NULL if no unique root element could be found
     *
     * @param string $html
     * @return \DOMNode
     */
    protected function getHtmlRootElement($html)
    {
        $html = trim($html);
        if ($html === '') {
            return null;
        }
        $domDocument = new \DOMDocument('1.0', 'UTF-8');
        // ignore parsing errors
        $useInternalErrorsBackup = libxml_use_internal_errors(true);
        $domDocument->loadHTML((substr($html, 0, 5) === '<?xml') ? $html : '<?xml encoding="UTF-8"?>' . $html);
        $xPath = new \DOMXPath($domDocument);
        $rootElement = $xPath->query('//html/body/*|//html/head/*');
        if ($useInternalErrorsBackup !== true) {
            libxml_use_internal_errors($useInternalErrorsBackup);
        }
        if ($rootElement === false || $rootElement->length !== 1) {
            return null;
        }
        // detect whether loadHTML has wrapped plaintext in a p-tag without asking for permission
        if ($rootElement instanceof \DOMNodeList && $rootElement->item(0)->tagName === 'p' && preg_match('/^<p/ui', $html) === 0) {
            return null;
        }
        return $rootElement->item(0);
    }

    /**
     * Merges the attributes of $element with the given $newAttributes
     * If an attribute exists in both collections, it is merged to "<new attribute> <old attribute>" (if both values differ)
     *
     * @param \DOMNode $element
     * @param array $newAttributes
     * @return void
     */
    protected function mergeAttributes(\DOMNode $element, array &$newAttributes)
    {
        /** @var $attribute \DOMAttr */
        foreach ($element->attributes as $attribute) {
            $oldAttributeValue = $attribute->hasChildNodes() ? $attribute->value : true;
            $hasNewAttributeValue = isset($newAttributes[$attribute->name]);
            $newAttributeValue = $newAttributes[$attribute->name] ?? null;

            if ($hasNewAttributeValue === false) {
                $combinedValue = $oldAttributeValue;
            } elseif ($newAttributeValue === $oldAttributeValue) {
                $combinedValue = $oldAttributeValue;
            } elseif ($newAttributeValue === null || $newAttributeValue === false) {
                $combinedValue = null;
            } elseif ($newAttributeValue === true) {
                $combinedValue = true;
            } elseif (is_array($newAttributeValue)) {
                $combinedValue = [...$newAttributeValue, $oldAttributeValue];
            } elseif (is_object($newAttributeValue) && $newAttributeValue instanceof \Stringable) {
                $combinedValue = [(string)$newAttributeValue, $oldAttributeValue];
            } else {
                $combinedValue = [$newAttributeValue, $oldAttributeValue];
            }

            $newAttributes[$attribute->name] = $combinedValue;
        }
    }

    /**
     * Checks whether the given $element contains at least one of the specified $attributes (case insensitive)
     *
     * @param \DOMNode $element
     * @param array $attributes array of attribute names to check (lowercase)
     * @return boolean true if at least one of the $attributes is contained in the given $element, otherwise false
     */
    protected function elementHasAttributes(\DOMNode $element, array $attributes = null)
    {
        if ($attributes === null) {
            return false;
        }
        /** @var $attribute \DOMAttr */
        foreach ($element->attributes as $attribute) {
            if (in_array(strtolower($attribute->name), $attributes)) {
                return true;
            }
        }
        return false;
    }
}
