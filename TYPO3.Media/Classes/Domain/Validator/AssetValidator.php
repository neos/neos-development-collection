<?php
namespace TYPO3\Media\Domain\Validator;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Validation\Validator\ConjunctionValidator;

/**
 * Conjunction validator that loads all implementations of the
 * \TYPO3\Media\Domain\Validator\AssetValidatorInterface and merges
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
        $assetValidatorImplementationClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('TYPO3\\Media\\Domain\\Validator\\AssetValidatorInterface');
        foreach ($assetValidatorImplementationClassNames as $assetValidatorImplementationClassName) {
            $this->addValidator($this->objectManager->get($assetValidatorImplementationClassName));
        }
    }
}
