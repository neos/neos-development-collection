<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Service;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementWrappingService
{

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     */
    public function wrapContentObject(
        Node $node,
        string $content,
        string $fusionPath
    ): ?string {
        // TODO: reenable permissions
        //if ($this->nodeAuthorizationService->isGrantedToEditNode($node) === false) {
        //    return $content;
        //}
        $nodeAddressJson = NodeAddress::fromNode($node)->toJson();
        $htmlCommentStart = '<!--__NEOS_UI_NODE_START__ ' . $nodeAddressJson . '__' . $fusionPath . '-->';
        $htmlCommentEnd = '<!--__NEOS_UI_NODE_END__' . $nodeAddressJson . '-->';
        return $htmlCommentStart . $content . $htmlCommentEnd;
    }
}
