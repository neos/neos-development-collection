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
use TYPO3\Eel\Utility as EelUtility;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;

/**
 * Service that executes a series of configured transformations on a node.
 *
 * @Flow\Scope("singleton")
 */
class NodeTransformation
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array<\TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface>
     */
    protected $transformationConjunctions = array();

    /**
     * @Flow\Inject
     * @var \TYPO3\Eel\EelEvaluatorInterface
     */
    protected $eelEvaluator;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\InjectConfiguration("transformations.eel.defaultContext")
     * @var array
     */
    protected $defaultContextConfiguration;

    /**
     * @return void
     */
    public function initializeObject()
    {
        if ($this->eelEvaluator instanceof \TYPO3\Flow\Object\DependencyInjection\DependencyProxy) {
            $this->eelEvaluator->_activateDependency();
        }
    }

    /**
     * Executes all configured transformations starting on the given node.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $nodeData
     * @param array $transformationConfigurations
     * @param array $additionalContextVariables
     * @return void
     */
    public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $nodeData, array $transformationConfigurations, array $additionalContextVariables = [])
    {
        $contextVariables = $this->getContextVariables($nodeData, $additionalContextVariables);
        $transformationConjunction = $this->buildTransformationConjunction($transformationConfigurations, $contextVariables);
        foreach ($transformationConjunction as $transformation) {
            if ($transformation->isTransformable($nodeData)) {
                $transformation->execute($nodeData);
            }
        }
    }

    /**
     * @param array $transformationConfigurations
     * @param array $contextVariables
     * @return array<\TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface>
     */
    protected function buildTransformationConjunction(array $transformationConfigurations, array $contextVariables)
    {
        $conjunctionIdentifier = md5(serialize($transformationConfigurations));
        if (isset($this->transformationConjunctions[$conjunctionIdentifier])) {
            return $this->transformationConjunctions[$conjunctionIdentifier];
        }

        $conjunction = array();
        foreach ($transformationConfigurations as $transformationConfiguration) {
            $conjunction[] = $this->buildTransformationObject($transformationConfiguration, $contextVariables);
        }
        $this->transformationConjunctions[$conjunctionIdentifier] = $conjunction;

        return $conjunction;
    }

    /**
     * Builds a transformation object from the given configuration.
     *
     * @param array $transformationConfiguration
     * @param array $contextVariables
     * @return \TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface
     * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException if a given setting is not supported
     */
    protected function buildTransformationObject($transformationConfiguration, $contextVariables)
    {
        $transformationClassName = $this->resolveTransformationClassName($transformationConfiguration['type']);
        $transformation = new $transformationClassName();

        foreach ($transformationConfiguration['settings'] as $settingName => $settingValue) {
            if (preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $settingValue)) {
                $settingValue = EelUtility::evaluateEelExpression($settingValue, $this->eelEvaluator, $contextVariables);
            }
            if (!\TYPO3\Flow\Reflection\ObjectAccess::setProperty($transformation, $settingName, $settingValue)) {
                throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('Cannot set setting "' . $settingName . '" on transformation "' . $transformationClassName . '" , check your configuration.', 1343293094);
            }
        }

        return $transformation;
    }

    /**
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $nodeData
     * @param array $additionalContextVariables
     * @return array
     */
    protected function getContextVariables(\TYPO3\TYPO3CR\Domain\Model\NodeData $nodeData, $additionalContextVariables)
    {
        $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
        $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
        $contextVariables = $this->defaultContextConfiguration;
        $contextVariables['node'] = $node;
        return array_merge($contextVariables, $additionalContextVariables);
    }

    /**
     * Tries to resolve the given transformation name into a class name.
     *
     * The name can be a fully qualified class name or a name relative to the
     * TYPO3\TYPO3CR\Migration\Transformations namespace.
     *
     * @param string $transformationName
     * @return string
     * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
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

        throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('A transformation with the name "' . $transformationName . '" could not be found.', 1343293064);
    }
}
