<?php
namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Fusion;

/**
 * This dsl factory takes care of instantiating a Fusion dsl transpilers.
 *
 * @Flow\Scope("singleton")
 */
class DslFactory
{
    /**
     * @Flow\InjectConfiguration("dsl")
     * @var
     */
    protected $dslSettings;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManger;

    /**
     * @param string $identifier
     * @return DslInterface
     * @throws Fusion/Exception
     */
    public function create($identifier)
    {
        if (isset($this->dslSettings) && is_array($this->dslSettings) && isset($this->dslSettings[$identifier])) {
            $dslObjectName = $this->dslSettings[$identifier];
            if (!class_exists($dslObjectName)) {
                throw new Fusion\Exception(sprintf('The fusion dsl-object %s was not found.', $dslObjectName), 1490776462);
            }
            $dslObject = new $dslObjectName();
            if (!$dslObject instanceof DslInterface) {
                throw new Fusion\Exception(sprintf('The fusion dsl-object was of type %s but was supposed to be of type %s', get_class($dslObject), DslInterface::class), 1490776470);
            }
            return new $dslObject();
        }
        throw new Fusion\Exception(sprintf('The fusion dsl-object for the key %s was not configured', $identifier), 1490776550);
    }
}
