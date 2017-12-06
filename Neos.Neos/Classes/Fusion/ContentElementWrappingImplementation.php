<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Service\ContentElementWrappingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Adds meta data attributes to the processed Content Element
 */
class ContentElementWrappingImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ContentElementWrappingService
     */
    protected $contentElementWrappingService;

    /**
     * The string to be processed
     *
     * @return string
     */
    public function getValue()
    {
        return $this->fusionValue('value');
    }

    /**
     * Evaluate this Fusion object and return the result
     *
     * @return mixed
     */
    public function evaluate()
    {
        $content = $this->getValue();

        /** @var $node NodeInterface */
        $node = $this->fusionValue('node');
        if (!$node instanceof NodeInterface) {
            return $content;
        }

        /** @var $contentContext ContentContext */
        $contentContext = $node->getContext();
        if ($contentContext->getWorkspaceName() === 'live') {
            return $content;
        }

        if (!$this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            return $content;
        }

        if ($node->isRemoved()) {
            $content = '';
        }

        if ($this->fusionValue('renderCurrentDocumentMetadata')) {
            return $this->contentElementWrappingService->wrapCurrentDocumentMetadata($node, $content, $this->getContentElementFusionPath());
        }

        return $this->contentElementWrappingService->wrapContentObject($node, $content, $this->getContentElementFusionPath());
    }

    /**
     * Returns the Fusion path to the wrapped Content Element
     *
     * @return string
     */
    protected function getContentElementFusionPath()
    {
        $fusionPathSegments = explode('/', $this->path);
        $numberOfFusionPathSegments = count($fusionPathSegments);
        if (isset($fusionPathSegments[$numberOfFusionPathSegments - 3])
            && $fusionPathSegments[$numberOfFusionPathSegments - 3] === '__meta'
            && isset($fusionPathSegments[$numberOfFusionPathSegments - 2])
            && $fusionPathSegments[$numberOfFusionPathSegments - 2] === 'process') {

            // cut off the SHORT processing syntax "__meta/process/contentElementWrapping<Neos.Neos:ContentElementWrapping>"
            return implode('/', array_slice($fusionPathSegments, 0, -3));
        }

        if (isset($fusionPathSegments[$numberOfFusionPathSegments - 4])
            && $fusionPathSegments[$numberOfFusionPathSegments - 4] === '__meta'
            && isset($fusionPathSegments[$numberOfFusionPathSegments - 3])
            && $fusionPathSegments[$numberOfFusionPathSegments - 3] === 'process') {

            // cut off the LONG processing syntax "__meta/process/contentElementWrapping/expression<Neos.Neos:ContentElementWrapping>"
            return implode('/', array_slice($fusionPathSegments, 0, -4));
        }
        return $this->path;
    }
}
