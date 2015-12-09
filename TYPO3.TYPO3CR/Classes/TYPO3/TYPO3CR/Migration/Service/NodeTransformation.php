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
use TYPO3\TYPO3CR\Domain\Model\NodeData;

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
     * @Flow\Inject(lazy=false)
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
     * @var array
     */
    protected $contextVariables;

    /**
     * Executes all configured transformations starting on the given node.
     *
     * @param NodeData $nodeData
     * @param array $transformationConfigurations
     * @param array $additionalContextVariables
     * @return void
     */
    public function execute(NodeData $nodeData, array $transformationConfigurations, array $additionalContextVariables = [])
    {
        $this->setContextVariables($nodeData, $additionalContextVariables);
        $transformationConjunction = $this->buildTransformationConjunction($transformationConfigurations);
        /** @var \TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface $transformation */
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
     * @return \TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface
     * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException if a given setting is not supported
     */
    protected function buildTransformationObject(array $transformationConfiguration)
    {
        $transformationClassName = $this->resolveTransformationClassName($transformationConfiguration['type']);
        $transformation = new $transformationClassName();

        foreach ($transformationConfiguration['settings'] as $settingName => $settingValue) {
            $settingValue = $this->parseSetting($settingValue);
            if (!\TYPO3\Flow\Reflection\ObjectAccess::setProperty($transformation, $settingName, $settingValue)) {
                throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('Cannot set setting "' . $settingName . '" on transformation "' . $transformationClassName . '" , check your configuration.', 1343293094);
            }
        }

        return $transformation;
    }

    /**
     * Parse the given transformation setting (recursively if given an array)
     * and evaluate any Eel expressions in it.
     *
     * @param array|string $setting
     * @return array|string
     */
    protected function parseSetting($setting)
    {
        if (is_array($setting)) {
            foreach ($setting as $subSettingName => $subSettingValue) {
                $setting[$subSettingName] = $this->parseSetting($subSettingValue);
            }
        } elseif (is_string($setting)) {
            if (preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $setting)) {
                $setting = EelUtility::evaluateEelExpression($setting, $this->eelEvaluator, $this->contextVariables);
            }
        }
        return $setting;
    }

    /**
     * Sets the contextVariables to the defaultContextConfiguration amended with a node built from the given $nodeData
     * and merges the $additionalContextVariables in as well.
     *
     * @param NodeData $nodeData
     * @param array $additionalContextVariables
     * @return void
     */
    protected function setContextVariables(NodeData $nodeData, array $additionalContextVariables)
    {
        $context = $this->nodeFactory->createContextMatchingNodeData($nodeData);
        $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
        $contextVariables = $this->defaultContextConfiguration;
        $contextVariables['node'] = $node;
        $this->contextVariables = array_merge($contextVariables, $additionalContextVariables);
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
