<?php
namespace TYPO3\Neos\ViewHelpers;

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
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * View helper to wrap nodes for editing in the backend
 *
 * **Deprecated!** This ViewHelper is no longer needed as wrapping is now done with a TypoScript processor.
 *
 * @deprecated since 1.0
 */
class ContentElementViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * Initialize arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
    }

    /**
     * This ViewHelper is no longer used
     *
     * @param NodeInterface $node
     * @param boolean $page
     * @param string $tag
     * @return string The wrapped output
     * @deprecated
     */
    public function render(NodeInterface $node, $page = false, $tag = 'div')
    {
        $this->tag->setTagName($tag);
        $this->tag->setContent($this->renderChildren());
        return $this->tag->render();
    }
}
