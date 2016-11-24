<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
