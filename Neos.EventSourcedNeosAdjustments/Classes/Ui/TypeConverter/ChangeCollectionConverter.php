<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\TypeConverter;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error;
use Neos\EventSourcedNeosAdjustments\Ui\ContentRepository\Service\NodeService;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\ChangeCollection;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\ChangeInterface;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes\Property;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\ObjectAccess;

/**
 * An Object Converter for ChangeCollections.
 *
 * @Flow\Scope("singleton")
 */
class ChangeCollectionConverter extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = ChangeCollection::class;

    /**
     * @var integer
     */
    protected $priority = 5;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    protected $disallowedPayloadProperties = [
        'subject',
        'reference'
    ];

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui", path="changes.types")
     * @var array
     */
    protected $typeMap;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Converts a accordingly formatted, associative array to a change collection
     *
     * @param array $source
     * @param string $targetType not used
     * @param array $subProperties not used
     * @param \Neos\Flow\Property\PropertyMappingConfigurationInterface $configuration not used
     * @return mixed An object or \Neos\Error\Messages\Error if the input format is not supported
     *               or could not be converted for other reasons
     * @throws \Exception
     */
    public function convertFrom(
        $source,
        $targetType,
        array $subProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        if (!is_array($source)) {
            return new Error(sprintf('Cannot convert %s to ChangeCollection.', gettype($source)));
        }

        $changeCollection = new ChangeCollection();
        foreach ($source as $changeData) {
            $convertedData = $this->convertChangeData($changeData);

            if ($convertedData instanceof Error) {
                return $convertedData;
            }

            $changeCollection->add($convertedData);
        }

        return $changeCollection;
    }

    /**
     * Convert array to change interface
     *
     * @param array $changeData
     * @return ChangeInterface|Error
     */
    protected function convertChangeData($changeData)
    {
        $type = $changeData['type'];

        if (!isset($this->typeMap[$type])) {
            return new Error(sprintf('Could not convert change type %s, it is unknown to the system', $type));
        }

        $changeClass = $this->typeMap[$type];
        $changeClassInstance = $this->objectManager->get($changeClass);

        $subjectContextPath = $changeData['subject'];
        $subject = $this->nodeService->getNodeFromContextPath($subjectContextPath);

        if ($subject instanceof Error) {
            return $subject;
        }

        $changeClassInstance->setSubject($subject);

        if (isset($changeData['reference']) && method_exists($changeClassInstance, 'setReference')) {
            $referenceContextPath = $changeData['reference'];
            $reference = $this->nodeService->getNodeFromContextPath($referenceContextPath);

            if ($reference instanceof Error) {
                return $reference;
            }

            $changeClassInstance->setReference($reference);
        }

        if (isset($changeData['payload'])) {
            foreach ($changeData['payload'] as $propertyName => $value) {
                if (!in_array($propertyName, $this->disallowedPayloadProperties)) {
                    $methodParameters = $this->reflectionService->getMethodParameters(
                        $changeClass,
                        ObjectAccess::buildSetterMethodName($propertyName)
                    );
                    $methodParameter = current($methodParameters);
                    $targetType = $methodParameter['type'];

                    // Fixme: The type conversion runs depending on the target node property type inside Property::class
                    // This is why we are not allowed to modify the value in any way.
                    // Without this condition the object was parsed to a string leading to fatal errors
                    // when changing images in the UI.
                    if ($propertyName !== 'value' && $targetType !== Property::class) {
                        $value = $this->propertyMapper->convert($value, $targetType);
                    }

                    ObjectAccess::setProperty($changeClassInstance, $propertyName, $value);
                }
            }
        }

        return $changeClassInstance;
    }
}
