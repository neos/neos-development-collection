<?php
namespace TYPO3\TYPO3CR\Migration\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Migration\Exception\MigrationException;
use TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface;

/**
 * Service that executes a series of configured transformations on a node.
 *
 * @Flow\Scope("singleton")
 */
class NodeTransformation
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array<\TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface>
     */
    protected $transformationConjunctions = array();

    /**
     * Executes all configured transformations starting on the given node.
     *
     * @param NodeData $nodeData
     * @param array $transformationConfigurations
     * @return void
     */
    public function execute(NodeData $nodeData, array $transformationConfigurations)
    {
        $transformationConjunction = $this->buildTransformationConjunction($transformationConfigurations);
        foreach ($transformationConjunction as $transformation) {
            if ($transformation->isTransformable($nodeData)) {
                $transformation->execute($nodeData);
            }
        }
    }

    /**
     * @param array $transformationConfigurations
     * @return array<\TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface>
     */
    protected function buildTransformationConjunction(array $transformationConfigurations)
    {
        $conjunctionIdentifier = md5(serialize($transformationConfigurations));
        if (isset($this->transformationConjunctions[$conjunctionIdentifier])) {
            return $this->transformationConjunctions[$conjunctionIdentifier];
        }

        $conjunction = array();
        foreach ($transformationConfigurations as $transformationConfiguration) {
            $conjunction[] = $this->buildTransformationObject($transformationConfiguration);
        }
        $this->transformationConjunctions[$conjunctionIdentifier] = $conjunction;

        return $conjunction;
    }

    /**
     * Builds a transformation object from the given configuration.
     *
     * @param array $transformationConfiguration
     * @return TransformationInterface
     * @throws MigrationException if a given setting is not supported
     */
    protected function buildTransformationObject($transformationConfiguration)
    {
        $transformationClassName = $this->resolveTransformationClassName($transformationConfiguration['type']);
        $transformation = new $transformationClassName();

        foreach ($transformationConfiguration['settings'] as $settingName => $settingValue) {
            if (!ObjectAccess::setProperty($transformation, $settingName, $settingValue)) {
                throw new MigrationException('Cannot set setting "' . $settingName . '" on transformation "' . $transformationClassName . '" , check your configuration.', 1343293094);
            }
        }

        return $transformation;
    }

    /**
     * Tries to resolve the given transformation name into a class name.
     *
     * The name can be a fully qualified class name or a name relative to the
     * TYPO3\TYPO3CR\Migration\Transformations namespace.
     *
     * @param string $transformationName
     * @return string
     * @throws MigrationException
     */
    protected function resolveTransformationClassName($transformationName)
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($transformationName);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\TYPO3CR\Migration\Transformations\\' . $transformationName);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        throw new MigrationException('A transformation with the name "' . $transformationName . '" could not be found.', 1343293064);
    }
}
