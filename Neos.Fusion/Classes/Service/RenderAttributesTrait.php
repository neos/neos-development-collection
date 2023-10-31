<?php
declare(strict_types=1);

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

/**
 * @internal
 */
trait RenderAttributesTrait
{
    /**
     * Render the tag attributes for the given key->values as string,
     * if a value is an iterable it will be concatenated with spaces as separator
     *
     * @param iterable<mixed,int|string|null|bool|array<string|bool|null|\Stringable>> $attributes
     * @param bool $allowEmpty
     */
    protected function renderAttributes(iterable $attributes, bool $allowEmpty = true): string
    {
        $renderedAttributes = '';
        foreach ($attributes as $attributeName => $attributeValue) {
            if ($attributeValue === null || $attributeValue === false) {
                continue;
            }
            if (is_array($attributeValue)) {
                // [] => empty attribute ? questionable
                // [true] => empty attribute
                // [false] => empty attribute
                // ["foo", null, false, "bar"] => "foo bar"
                // [""] => empty attribute
                $joinedAttributeValue = '';
                foreach ($attributeValue as $attributeValuePart) {
                    if ($attributeValuePart instanceof \Stringable) {
                        $attributeValuePart = $attributeValuePart->__toString();
                    } elseif ($attributeValuePart instanceof \BackedEnum) {
                        $attributeValuePart = $attributeValuePart->value;
                    }
                    $joinedAttributeValue .= match (gettype($attributeValuePart)) {
                        'boolean', 'NULL' => '',
                        'string' => ' ' . trim($attributeValuePart),
                        default => throw new \InvalidArgumentException('$attributes may contain values of type array<string|bool|null|\Stringable> type: array<' . get_debug_type($attributeValuePart) . '> given')
                    };
                }
                $attributeValue = trim($joinedAttributeValue);
            } elseif ($attributeValue instanceof \Stringable) {
                $attributeValue = $attributeValue->__toString();
            } elseif ($attributeValue instanceof \BackedEnum) {
                $attributeValue = $attributeValue->value;
            }
            $encodedAttributeName = htmlspecialchars((string)$attributeName, ENT_COMPAT, 'UTF-8', false);
            if ($attributeValue === true || $attributeValue === '') {
                $renderedAttributes .= ' ' . $encodedAttributeName . ($allowEmpty ? '' : '=""');
            } else {
                $encodedAttributeValue = htmlspecialchars((string)$attributeValue, ENT_COMPAT, 'UTF-8', false);
                $renderedAttributes .= ' ' . $encodedAttributeName . '="' . $encodedAttributeValue . '"';
            }
        }
        return $renderedAttributes;
    }
}
