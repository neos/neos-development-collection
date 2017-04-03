<?php
namespace Neos\Media\Domain\Validator;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Validation\Validator\ConjunctionValidator;

/**
 * Conjunction validator that loads all implementations of the
 * \Neos\Media\Domain\Validator\AssetValidatorInterface and merges
 * all their results
 */
class AssetValidator extends ConjunctionValidator
{
    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Adds all validators that extend the AssetValidatorInterface.
     *
     * @return void
     */
    protected function initializeObject()
    {
        $assetValidatorImplementationClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface(AssetValidatorInterface::class);
        foreach ($assetValidatorImplementationClassNames as $assetValidatorImplementationClassName) {
            $this->addValidator($this->objectManager->get($assetValidatorImplementationClassName));
        }
    }
}
