<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\ContentElementWrapping;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\Fusion\Service\HtmlAugmenter as FusionHtmlAugmenter;

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
     * @var FusionHtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    public function wrapContentProperty(NodeInterface $node, $property, $content)
    {
        if ($this->isContentStreamOfLiveWorkspace($node->getContentStreamIdentifier())) {
            return $content;
        }

        // TODO: permissions
        //if (!$this->nodeAuthorizationService->isGrantedToEditNode($node)) {
        //    return $content;
        //}

        $attributes = [
            'data-__neos-property' => $property,
            'data-__neos-editable-node-contextpath' => $this->nodeAddressFactory->createFromNode($node)
                ->serializeForUri()
        ];

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'span');
    }

    private function isContentStreamOfLiveWorkspace(ContentStreamIdentifier $contentStreamIdentifier)
    {
        return $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier)
            ->getWorkspaceName()->isLive();
    }
}
