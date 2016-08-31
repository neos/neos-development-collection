<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\TypeConverter\EntityToIdentityConverter;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Service\AuthorizationService;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 *
 * @Flow\Scope("singleton")
 */
class ContentElementWrappingService
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var AuthorizationService
     */
    protected $nodeAuthorizationService;

    /**
     * @Flow\Inject
     * @var HtmlAugmenter
     */
    protected $htmlAugmenter;

    /**
     * @Flow\Inject
     * @var EntityToIdentityConverter
     */
    protected $entityToIdentityConverter;

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param NodeInterface $node
     * @param string $typoScriptPath
     * @param string $content
     * @param boolean $renderCurrentDocumentMetadata When this flag is set we will render the global metadata for the current document
     * @return string
     */
    public function wrapContentObject(NodeInterface $node, $typoScriptPath, $content, $renderCurrentDocumentMetadata = false)
    {
        /** @var $contentContext ContentContext */
        $contentContext = $node->getContext();
        if ($contentContext->getWorkspaceName() === 'live' || !$this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess')) {
            return $content;
        }
        $nodeType = $node->getNodeType();
        $attributes = array();
        $attributes['typeof'] = 'typo3:' . $nodeType->getName();
        $attributes['about'] = $node->getContextPath();

        $classNames = array();
        if ($renderCurrentDocumentMetadata === true) {
            $attributes['data-neos-site-name'] = $contentContext->getCurrentSite()->getName();
            $attributes['data-neos-site-node-context-path'] = $contentContext->getCurrentSiteNode()->getContextPath();
            // Add the workspace of the TYPO3CR context to the attributes
            $attributes['data-neos-context-workspace-name'] = $contentContext->getWorkspaceName();
            $attributes['data-neos-context-dimensions'] = json_encode($contentContext->getDimensions());

            if (!$this->nodeAuthorizationService->isGrantedToEditNode($node)) {
                $attributes['data-node-__read-only'] = 'true';
                $attributes['data-nodedatatype-__read-only'] = 'boolean';
            }
        } else {
            if (!$this->nodeAuthorizationService->isGrantedToEditNode($node)) {
                return $content;
            }

            if ($node->isRemoved()) {
                $classNames[] = 'neos-contentelement-removed';
            }

            if ($node->isHidden()) {
                $classNames[] = 'neos-contentelement-hidden';
            }

            if ($nodeType->isOfType('TYPO3.Neos:ContentCollection')) {
                $attributes['rel'] = 'typo3:content-collection';
                // This is needed since the backend relies on this class (should not be necessary)
                $classNames[] = 'neos-contentcollection';
            } else {
                $classNames[] = 'neos-contentelement';
            }

            $uiConfiguration = $nodeType->hasConfiguration('ui') ? $nodeType->getConfiguration('ui') : array();
            if ((isset($uiConfiguration['inlineEditable']) && $uiConfiguration['inlineEditable'] !== true) || (!isset($uiConfiguration['inlineEditable']) && !$this->hasInlineEditableProperties($node))) {
                $classNames[] = 'neos-not-inline-editable';
            }

            $attributes['tabindex'] = 0;
        }

        if ($node instanceof Node && !$node->dimensionsAreMatchingTargetDimensionValues()) {
            $classNames[] = 'neos-contentelement-shine-through';
        }

        if (count($classNames) > 0) {
            $attributes['class'] = implode(' ', $classNames);
        }

        // Add the actual workspace of the node, the node identifier and the TypoScript path to the attributes
        $attributes['data-node-_identifier'] = $node->getIdentifier();
        $attributes['data-node-__workspace-name'] = $node->getWorkspace()->getName();
        $attributes['data-node-__typoscript-path'] = $typoScriptPath;
        $attributes['data-node-__label'] = $node->getLabel();

        // these properties are needed together with the current NodeType to evaluate Node Type Constraints
        // TODO: this can probably be greatly cleaned up once we do not use CreateJS or VIE anymore.
        if ($node->getParent()) {
            $attributes['data-node-__parent-node-type'] = $node->getParent()->getNodeType()->getName();
        }

        if ($node->isAutoCreated()) {
            $attributes['data-node-_name'] = $node->getName();
            $attributes['data-node-_is-autocreated'] = 'true';
        }

        if ($node->getParent() && $node->getParent()->isAutoCreated()) {
            $attributes['data-node-_parent-is-autocreated'] = 'true';
            // we shall only add these properties if the parent is actually auto-created; as the Node-Type-Switcher in the UI relies on that.
            $attributes['data-node-__parent-node-name'] = $node->getParent()->getName();
            $attributes['data-node-__grandparent-node-type'] = $node->getParent()->getParent()->getNodeType()->getName();
        }

        $attributes = $this->addNodePropertyAttributes($node, $attributes);

        return $this->htmlAugmenter->addAttributes($content, $attributes, 'div', array('typeof'));
    }

    /**
     * Adds node properties to the given $attributes collection and returns the extended array
     *
     * @param NodeInterface $node
     * @param array $attributes
     * @return array the merged attributes
     */
    public function addNodePropertyAttributes(NodeInterface $node, array $attributes)
    {
        foreach ($node->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
            if (substr($propertyName, 0, 2) === '__') {
                // skip fully-private properties
                continue;
            }
            /** @var $contentContext ContentContext */
            $contentContext = $node->getContext();
            if ($propertyName === '_name' && $node === $contentContext->getCurrentSiteNode()) {
                // skip the node name of the site node
                continue;
            }
            // Serialize objects to JSON strings
            $dataType = isset($propertyConfiguration['type']) ? $propertyConfiguration['type'] : 'string';
            $dasherizedPropertyName = $this->dasherize($propertyName);
            $attributes['data-node-' . $dasherizedPropertyName] = $this->getNodeProperty($node, $propertyName, $dataType);
            if ($dataType !== 'string') {
                $prefixedDataType = $dataType === 'jsonEncoded' ? 'typo3:jsonEncoded' : 'xsd:' . $dataType;
                $attributes['data-nodedatatype-' . $dasherizedPropertyName] = $prefixedDataType;
            }
        }
        return $attributes;
    }

    /**
     * TODO This implementation is directly linked to the inspector editors, since they need the actual values,
     * this should change to use TypeConverters
     *
     * @param NodeInterface $node
     * @param string $propertyName
     * @param string $dataType
     * @return string
     */
    protected function getNodeProperty(NodeInterface $node, $propertyName, &$dataType)
    {
        if (substr($propertyName, 0, 1) === '_') {
            $propertyValue = ObjectAccess::getProperty($node, substr($propertyName, 1));
        } else {
            $propertyValue = $node->getProperty($propertyName);
        }

        // Enforce an integer value for integer properties as otherwise javascript will give NaN and VIE converts it to an array containing 16 times 'NaN'
        if ($dataType === 'integer') {
            $propertyValue = (integer)$propertyValue;
        }

        // Serialize boolean values to String
        if ($dataType === 'boolean') {
            return $propertyValue ? 'true' : 'false';
        }

        // Serialize array values to String
        if ($dataType === 'array') {
            return $propertyValue ? json_encode($propertyValue, JSON_UNESCAPED_UNICODE) : null;
        }

        // Serialize date values to String
        if ($dataType === 'DateTime') {
            if (!$propertyValue instanceof \DateTimeInterface) {
                return '';
            }
            $value = clone $propertyValue;
            return $value->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::W3C);
        }

        // Serialize node references to node identifiers
        if ($dataType === 'references') {
            $nodeIdentifiers = array();
            if (is_array($propertyValue)) {
                /** @var $subNode NodeInterface */
                foreach ($propertyValue as $subNode) {
                    $nodeIdentifiers[] = $subNode->getIdentifier();
                }
            }
            return json_encode($nodeIdentifiers);
        }

        // Serialize node reference to node identifier
        if ($dataType === 'reference') {
            if ($propertyValue instanceof NodeInterface) {
                return $propertyValue->getIdentifier();
            } else {
                return '';
            }
        }

        if ($propertyValue instanceof ImageInterface) {
            $propertyMappingConfiguration = new PropertyMappingConfiguration();
            return json_encode($this->entityToIdentityConverter->convertFrom($propertyValue, 'array', array(), $propertyMappingConfiguration));
        }

        // Serialize an Asset to JSON (the NodeConverter expects JSON for object type properties)
        if ($dataType === ltrim(Asset::class, '\\') && $propertyValue !== null) {
            if ($propertyValue instanceof Asset::class) {
                return json_encode($this->persistenceManager->getIdentifierByObject($propertyValue));
            }
        }

        // Serialize an array of Assets to JSON
        if (is_array($propertyValue)) {
            $parsedType = TypeHandling::parseType($dataType);
            if ($parsedType['elementType'] === ltrim(Asset::class, '\\')) {
                $convertedValues = array();
                foreach ($propertyValue as $singlePropertyValue) {
                    if ($singlePropertyValue instanceof Asset::class) {
                        $convertedValues[] = $this->persistenceManager->getIdentifierByObject($singlePropertyValue);
                    }
                }
                return json_encode($convertedValues);
            }
        }
        return $propertyValue === null ? '' : $propertyValue;
    }

    /**
     * @param NodeInterface $node
     * @return boolean
     */
    protected function hasInlineEditableProperties(NodeInterface $node)
    {
        foreach (array_values($node->getNodeType()->getProperties()) as $propertyConfiguration) {
            if (isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['ui']['inlineEditable'] === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts camelCased strings to lower cased and non-camel-cased strings
     *
     * @param string $value
     * @return string
     */
    protected function dasherize($value)
    {
        return strtolower(trim(preg_replace('/[A-Z]/', '-$0', $value), '-'));
    }
}
