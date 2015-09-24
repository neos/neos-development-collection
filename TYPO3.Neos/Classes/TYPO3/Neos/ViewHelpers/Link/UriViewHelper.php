<?php
namespace TYPO3\Neos\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A view helper for creating links to uri's. By default the target
 * is _blank so the links are opening in a new window.
 * = Examples =
 * <code title="Simple external uri">
 * <neos:link.uri uri="http://foobar.com">Foo bar</neos:link.uri>
 * </code>
 * <output>
 * <a href="http://foobar.com" target="_blank">Foo bar</a>
 * </output>
 * <code title="Simple internal uri">
 * <neos:link.uri uri="http://foobar.com" target="null">Foo bar</neos:link.uri>
 * </code>
 * <output>
 * <a href="http://foobar.com">Foo bar</a>
 * </output>
 *
 * @api
 */
class UriViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * @param string $uri
     * @param string $target
     * @return string
     */
    public function render($uri, $target = '_blank')
    {
        $content = $this->renderChildren();
        $this->tag->setContent($content);
        $this->tag->addAttribute('href', $uri);
        $this->tag->addAttribute('target', $target);
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

}