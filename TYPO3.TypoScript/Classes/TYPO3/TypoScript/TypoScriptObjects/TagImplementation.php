<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A TypoScript object for tag based content
 *
 * //tsPath attributes An array with attributes for this tag (optional)
 * //tsPath content Content for the body of the tag (optional)
 * @api
 */
class TagImplementation extends AbstractTypoScriptObject
{
    /**
     * List of self-closing tags
     *
     * @var array
     */
    protected static $SELF_CLOSING_TAGS = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');

    /**
     * The tag name (e.g. 'body', 'head', 'title', ...)
     *
     * @return string
     */
    public function getTagName()
    {
        $tagName = $this->tsValue('tagName');
        if ($tagName === null) {
            $tagName = 'div';
        }
        return $tagName;
    }

    /**
     * Whether to leave out the closing tag (defaults to FALSE)
     *
     * @return boolean
     */
    public function getOmitClosingTag()
    {
        return $this->tsValue('omitClosingTag');
    }

    /**
     * Whether to force a self closing tag (e.g. '<div />')
     *
     * @param string $tagName
     * @return boolean
     */
    public function isSelfClosingTag($tagName)
    {
        return in_array($tagName, self::$SELF_CLOSING_TAGS, true) || (boolean)$this->tsValue('selfClosingTag');
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
            $content = $this->tsValue('content');
        }
        return '<' . $tagName . $this->tsValue('attributes') . ($selfClosingTag ? ' /' : '') . '>' . (!$omitClosingTag && !$selfClosingTag ? $content . '</' . $tagName . '>' : '');
    }
}
