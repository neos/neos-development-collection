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

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Service\ContentElementEditableService;

/**
 * Adds meta data attributes to the processed Property to enable in place editing
 */
class ContentElementEditableImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ContentElementEditableService
     */
    protected $contentElementEditableService;

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

        $node = $this->fusionValue('node');
        if (!$node instanceof NodeInterface) {
            return $content;
        }

        /** @var string $property */
        $property = $this->fusionValue('property');

        if (!$this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            return $content;
        }

        return $this->contentElementEditableService->wrapContentProperty($node, $property, $content);
    }
}
