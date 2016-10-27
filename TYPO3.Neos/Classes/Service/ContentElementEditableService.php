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
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Service\AuthorizationService;

/**
 * The content element editable service adds the necessary markup around
 * a content element such that it can be edited using the inline editing
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementEditableService
{

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var AuthorizationService
     */
    protected $nodeAuthorizationService;

    /**
     * @Flow\Inject
     * @var HtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param NodeInterface $node
     * @param string $property
     * @param string $content
     * @return string
     */
    public function wrapContentProperty(NodeInterface $node, $property, $content)
    {
        /** @var $contentContext ContentContext */
        $contentContext = $node->getContext();
        if ($contentContext->getWorkspaceName() === 'live' || !$this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess')) {
            return $content;
        }

        if (!$this->nodeAuthorizationService->isGrantedToEditNode($node)) {
            return $content;
        }

        $attributes = array();
        $attributes['class'] = 'neos-inline-editable';
        $attributes['property'] = 'typo3:' . $property ;
        $attributes['data-neos-node-type'] = $node->getNodeType()->getName();

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'span');
    }
}
