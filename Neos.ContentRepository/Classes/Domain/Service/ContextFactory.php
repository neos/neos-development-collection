<?php
namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Utility\Arrays;
use Neos\Flow\Utility\Now;
use Neos\ContentRepository\Domain\Model\ContentDimension;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Exception\InvalidNodeContextException;

/**
 * The ContextFactory makes sure you don't create context instances with
 * the same properties twice. Calling create() with the same parameters
 * a second time will return the _same_ Context instance again.
 * Refer to 'ContextFactoryInterface' instead of 'ContextFactory' when
 * injecting this factory into your own class.
 *
 * @Flow\Scope("singleton")
 */
class ContextFactory implements ContextFactoryInterface
{
    /**
     * @var array<Context>
     */
    protected $contextInstances = [];

    /**
     * The context implementation this factory will create
     *
     * @var string
     */
    protected $contextImplementation = Context::class;

    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * Create the context from the given properties. If a context with those properties was already
     * created before then the existing one is returned.
     *
     * The context properties to give depend on the implementation of the context object, for the
     * Neos\ContentRepository\Domain\Service\Context it should look like this:
     *
     * array(
     *        'workspaceName' => 'live',
     *        'currentDateTime' => new \Neos\Flow\Utility\Now(),
     *        'dimensions' => array(...),
     *        'targetDimensions' => array('language' => 'de', 'persona' => 'Lisa'),
     *        'invisibleContentShown' => false,
     *        'removedContentShown' => false,
     *        'inaccessibleContentShown' => false
     * )
     *
     * This array also shows the defaults that get used if you don't provide a certain property.
     *
     * @param array $contextProperties
     * @return Context
     * @api
     */
    public function create(array $contextProperties = [])
    {
        $contextProperties = $this->mergeContextPropertiesWithDefaults($contextProperties);
        $contextIdentifier = $this->getIdentifier($contextProperties);
        if (!isset($this->contextInstances[$contextIdentifier])) {
            $this->validateContextProperties($contextProperties);
            $context = $this->buildContextInstance($contextProperties);
            $this->contextInstances[$contextIdentifier] = $context;
        }

        return $this->contextInstances[$contextIdentifier];
    }

    /**
     * Creates the actual Context instance.
     * This needs to be overridden if the Builder is extended.
     *
     * @param array $contextProperties
     * @return Context
     */
    protected function buildContextInstance(array $contextProperties)
    {
        $contextProperties = $this->removeDeprecatedProperties($contextProperties);
        return new Context($contextProperties['workspaceName'], $contextProperties['currentDateTime'], $contextProperties['dimensions'], $contextProperties['targetDimensions'], $contextProperties['invisibleContentShown'], $contextProperties['removedContentShown'], $contextProperties['inaccessibleContentShown']);
    }

    /**
     * Merges the given context properties with sane defaults for the context implementation.
     *
     * @param array $contextProperties
     * @return array
     */
    protected function mergeContextPropertiesWithDefaults(array $contextProperties)
    {
        $contextProperties = $this->removeDeprecatedProperties($contextProperties);

        $defaultContextProperties = [
            'workspaceName' => 'live',
            'currentDateTime' => $this->now,
            'dimensions' => [],
            'targetDimensions' => [],
            'invisibleContentShown' => false,
            'removedContentShown' => false,
            'inaccessibleContentShown' => false
        ];

        $mergedProperties = Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, true);

        $this->mergeDimensionValues($contextProperties, $mergedProperties);
        $this->mergeTargetDimensionContextProperties($contextProperties, $mergedProperties, $defaultContextProperties);

        return $mergedProperties;
    }

    /**
     * Provides a way to identify a context to prevent duplicate context objects.
     *
     * @param array $contextProperties
     * @return string
     */
    protected function getIdentifier(array $contextProperties)
    {
        return md5($this->securityContext->getContextHash() . $this->getIdentifierSource($contextProperties));
    }

    /**
     * This creates the actual identifier and needs to be overridden by builders extending this.
     *
     * @param array $contextProperties
     * @return string
     */
    protected function getIdentifierSource(array $contextProperties)
    {
        ksort($contextProperties);
        $identifierSource = $this->contextImplementation;
        foreach ($contextProperties as $propertyName => $propertyValue) {
            if ($propertyName === 'dimensions') {
                $stringParts = [];
                foreach ($propertyValue as $dimensionName => $dimensionValues) {
                    $stringParts[] = $dimensionName . '=' . implode(',', $dimensionValues);
                }
                $stringValue = implode('&', $stringParts);
            } elseif ($propertyName === 'targetDimensions') {
                $stringParts = [];
                foreach ($propertyValue as $dimensionName => $dimensionValue) {
                    $stringParts[] = $dimensionName . '=' . $dimensionValue;
                }
                $stringValue = implode('&', $stringParts);
            } else {
                $stringValue = $propertyValue instanceof \DateTimeInterface ? $propertyValue->getTimestamp() : (string)$propertyValue;
            }
            $identifierSource .= ':' . $stringValue;
        }

        return $identifierSource;
    }

    /**
     * @param array $contextProperties
     * @return void
     * @throws InvalidNodeContextException
     */
    protected function validateContextProperties($contextProperties)
    {
        if (isset($contextProperties['workspaceName'])) {
            if (!is_string($contextProperties['workspaceName']) || $contextProperties['workspaceName'] === '') {
                throw new InvalidNodeContextException('You tried to set a workspaceName in the context that was either no string or an empty string.', 1373144966);
            }
        }
        if (isset($contextProperties['invisibleContentShown'])) {
            if (!is_bool($contextProperties['invisibleContentShown'])) {
                throw new InvalidNodeContextException('You tried to set invisibleContentShown in the context and did not provide a boolean value.', 1373145239);
            }
        }
        if (isset($contextProperties['removedContentShown'])) {
            if (!is_bool($contextProperties['removedContentShown'])) {
                throw new InvalidNodeContextException('You tried to set removedContentShown in the context and did not provide a boolean value.', 1373145239);
            }
        }
        if (isset($contextProperties['inaccessibleContentShown'])) {
            if (!is_bool($contextProperties['inaccessibleContentShown'])) {
                throw new InvalidNodeContextException('You tried to set inaccessibleContentShown in the context and did not provide a boolean value.', 1373145239);
            }
        }
        if (isset($contextProperties['currentDateTime'])) {
            if (!$contextProperties['currentDateTime'] instanceof \DateTimeInterface) {
                throw new InvalidNodeContextException('You tried to set currentDateTime in the context and did not provide a DateTime object as value.', 1373145297);
            }
        }

        $dimensions = $this->getAvailableDimensions();
        /** @var ContentDimension $dimension */
        foreach ($dimensions as $dimension) {
            if (!isset($contextProperties['dimensions'][$dimension->getIdentifier()])
                || !is_array($contextProperties['dimensions'][$dimension->getIdentifier()])
                || $contextProperties['dimensions'][$dimension->getIdentifier()] === []
            ) {
                throw new InvalidNodeContextException(sprintf('You have to set a non-empty array with one or more values for content dimension "%s" in the context', $dimension->getIdentifier()), 1390300646);
            }
        }

        foreach ($contextProperties['targetDimensions'] as $dimensionName => $dimensionValue) {
            if (!isset($contextProperties['dimensions'][$dimensionName])) {
                throw new InvalidNodeContextException(sprintf('Failed creating a %s because the specified target dimension "%s" does not exist', $this->contextImplementation, $dimensionName), 1391340781);
            }
            if ($dimensionValue !== null && !in_array($dimensionValue, $contextProperties['dimensions'][$dimensionName])) {
                throw new InvalidNodeContextException(sprintf('Failed creating a %s because the specified target dimension value "%s" for dimension "%s" is not in the list of dimension values (%s)', $this->contextImplementation, $dimensionValue, $dimensionName, implode(', ', $contextProperties['dimensions'][$dimensionName])), 1391340741);
            }
        }
    }

    /**
     * Removes context properties which have been previously allowed but are not supported
     * anymore and should be silently ignored
     *
     * @param array $contextProperties
     * @return array
     */
    protected function removeDeprecatedProperties(array $contextProperties)
    {
        if (isset($contextProperties['locale'])) {
            unset($contextProperties['locale']);
        }
        return $contextProperties;
    }

    /**
     * @return array<\Neos\ContentRepository\Domain\Model\ContentDimension>
     */
    protected function getAvailableDimensions()
    {
        return $this->contentDimensionRepository->findAll();
    }

    /**
     * Reset instances (internal)
     */
    public function reset()
    {
        $this->contextInstances = [];
    }

    /**
     * @param array $contextProperties
     * @param array $mergedProperties
     * @param array $defaultContextProperties
     * @return mixed
     */
    protected function mergeTargetDimensionContextProperties(array $contextProperties, &$mergedProperties, $defaultContextProperties)
    {
        // Use first value of each dimension as default target dimension value
        $defaultContextProperties['targetDimensions'] = array_map(function ($values) {
            return reset($values);
        }, $mergedProperties['dimensions']);
        if (!isset($contextProperties['targetDimensions'])) {
            $contextProperties['targetDimensions'] = [];
        }
        $mergedProperties['targetDimensions'] = Arrays::arrayMergeRecursiveOverrule($defaultContextProperties['targetDimensions'], $contextProperties['targetDimensions']);
    }

    /**
     * @param array $contextProperties
     * @param array $mergedProperties
     * @return void
     * @throws InvalidNodeContextException
     */
    protected function mergeDimensionValues(array $contextProperties, array &$mergedProperties)
    {
        $dimensions = $this->getAvailableDimensions();
        foreach ($dimensions as $dimension) {
            /** @var ContentDimension $dimension */
            $identifier = $dimension->getIdentifier();
            $values = [$dimension->getDefault()];
            if (isset($contextProperties['dimensions'][$identifier])) {
                if (!is_array($contextProperties['dimensions'][$identifier])) {
                    throw new InvalidNodeContextException(sprintf('The given dimension fallback chain for "%s" should be an array of string, but "%s" was given.', $identifier, gettype($contextProperties['dimensions'][$identifier])), 1407417930);
                }
                $values = Arrays::arrayMergeRecursiveOverrule($values, $contextProperties['dimensions'][$identifier]);
            }
            $mergedProperties['dimensions'][$identifier] = $values;
        }
    }

    /**
     * Returns all known instances of Context.
     *
     * @return array<Context>
     */
    public function getInstances()
    {
        return $this->contextInstances;
    }
}
