<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Exception;

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
     * @return string
     */
    public function addAttributes($html, array $attributes, $fallbackTagName = 'div', array $exclusiveAttributes = null)
    {
        if ($attributes === array()) {
            return $html;
        }
        $rootElement = $this->getHtmlRootElement($html);
        if ($rootElement === null || $this->elementHasAttributes($rootElement, $exclusiveAttributes)) {
            return sprintf('<%s%s>%s</%s>', $fallbackTagName, $this->renderAttributes($attributes), $html, $fallbackTagName);
        }
        $this->mergeAttributes($rootElement, $attributes);
        return preg_replace('/<(' . $rootElement->nodeName . ')\b[^>]*>/xi', '<$1' . addcslashes($this->renderAttributes($attributes), '\\\$') . '>', $html, 1);
    }

    /**
     * Detects a unique root tag in the given $html string and returns its DOMNode representation - or NULL if no unique root element could be found
     *
     * @param string $html
     * @return \DOMNode
     */
    protected function getHtmlRootElement($html)
    {
        if (trim($html) === '') {
            return null;
        }
        $domDocument = new \DOMDocument('1.0', 'UTF-8');
        // ignore parsing errors
        $useInternalErrorsBackup = libxml_use_internal_errors(true);
        $domDocument->loadHTML($html);
        $xPath = new \DOMXPath($domDocument);
        $rootElement = $xPath->query('//html/body/*');
        if ($useInternalErrorsBackup !== true) {
            libxml_use_internal_errors($useInternalErrorsBackup);
        }
        if ($rootElement === false || $rootElement->length !== 1) {
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
            $oldAttributeValue = $attribute->hasChildNodes() ? $attribute->value : null;
            $newAttributeValue = isset($newAttributes[$attribute->name]) ? $newAttributes[$attribute->name] : null;
            $mergedAttributes = array();
            if ($newAttributeValue !== null && $newAttributeValue !== $oldAttributeValue) {
                $mergedAttributes[] = $newAttributeValue;
            }
            if ($oldAttributeValue !== null) {
                $mergedAttributes[] = $oldAttributeValue;
            }
            $newAttributes[$attribute->name] = $mergedAttributes !== array() ? implode(' ', $mergedAttributes) : null;
        }
    }

    /**
     * Renders the given key/value pair to a valid attribute string in the format <key1>="<value1>" <key2>="<value2>"...
     *
     * @param array $attributes The attributes to render in the format array('<attributeKey>' => '<attributeValue>', ...)
     * @return string
     * @throws Exception
     */
    protected function renderAttributes(array $attributes)
    {
        $renderedAttributes = '';
        foreach ($attributes as $attributeName => $attributeValue) {
            $encodedAttributeName = htmlspecialchars($attributeName, ENT_COMPAT, 'UTF-8');
            if ($attributeValue === null) {
                $renderedAttributes .= ' ' . $encodedAttributeName;
            } else {
                if (is_array($attributeValue) || (is_object($attributeValue) && !method_exists($attributeValue, '__toString'))) {
                    throw new Exception(sprintf('Only attributes with string values can be rendered, attribute %s is of type %s', $attributeName, gettype($attributeValue)));
                }

                $encodedAttributeValue = htmlspecialchars((string)$attributeValue, ENT_COMPAT, 'UTF-8');
                $renderedAttributes .= ' ' . $encodedAttributeName . '="' . $encodedAttributeValue . '"';
            }
        }
        return $renderedAttributes;
    }

    /**
     * Checks whether the given $element contains at least one of the specified $attributes (case insensitive)
     *
     * @param \DOMNode $element
     * @param array $attributes array of attribute names to check (lowercase)
     * @return boolean TRUE if at least one of the $attributes is contained in the given $element, otherwise FALSE
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
