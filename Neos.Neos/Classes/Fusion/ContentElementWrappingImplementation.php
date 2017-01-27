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
     * Evaluate this TypoScript object and return the result
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
            return $this->contentElementWrappingService->wrapCurrentDocumentMetadata($node, $content, $this->getContentElementTypoScriptPath());
        }

        return $this->contentElementWrappingService->wrapContentObject($node, $content, $this->getContentElementTypoScriptPath());
    }

    /**
     * Returns the TypoScript path to the wrapped Content Element
     *
     * @return string
     */
    protected function getContentElementTypoScriptPath()
    {
        $typoScriptPathSegments = explode('/', $this->path);
        $numberOfTypoScriptPathSegments = count($typoScriptPathSegments);
        if (isset($typoScriptPathSegments[$numberOfTypoScriptPathSegments - 3])
            && $typoScriptPathSegments[$numberOfTypoScriptPathSegments - 3] === '__meta'
            && isset($typoScriptPathSegments[$numberOfTypoScriptPathSegments - 2])
            && $typoScriptPathSegments[$numberOfTypoScriptPathSegments - 2] === 'process') {

            // cut of the processing segments "__meta/process/contentElementWrapping<Neos.Neos:ContentElementWrapping>"
            return implode('/', array_slice($typoScriptPathSegments, 0, -3));
        }
        return $this->path;
    }
}
