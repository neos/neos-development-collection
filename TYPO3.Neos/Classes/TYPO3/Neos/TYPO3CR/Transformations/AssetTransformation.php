<?php
namespace TYPO3\Neos\TYPO3CR\Transformations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\Audio;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Video;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

/**
 * Convert serialized Assets to references.
 */
class AssetTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related interface.
     *
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return true;
    }

    /**
     * Change the property on the given node.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        foreach ($node->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
            if (isset($propertyConfiguration['type']) && in_array(trim($propertyConfiguration['type']), $this->getHandledObjectTypes())) {
                if (!isset($nodeProperties)) {
                    $nodeRecordQuery = $this->entityManager->getConnection()->prepare('SELECT properties FROM typo3_typo3cr_domain_model_nodedata WHERE persistence_object_identifier=?');
                    $nodeRecordQuery->execute([$this->persistenceManager->getIdentifierByObject($node)]);
                    $nodeRecord = $nodeRecordQuery->fetch(\PDO::FETCH_ASSOC);
                    $nodeProperties = unserialize($nodeRecord['properties']);
                }

                if (!isset($nodeProperties[$propertyName]) || !is_object($nodeProperties[$propertyName])) {
                    continue;
                }

                /** @var Asset $assetObject */
                $assetObject = $nodeProperties[$propertyName];
                $nodeProperties[$propertyName] = null;

                $stream = $assetObject->getResource()->getStream();

                if ($stream === false) {
                    continue;
                }

                fclose($stream);
                $objectType = TypeHandling::getTypeForValue($assetObject);
                $objectIdentifier = ObjectAccess::getProperty($assetObject, 'Persistence_Object_Identifier', true);

                $nodeProperties[$propertyName] = array(
                    '__flow_object_type' => $objectType,
                    '__identifier' => $objectIdentifier
                );
            }
        }

        if (isset($nodeProperties)) {
            $nodeUpdateQuery = $this->entityManager->getConnection()->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties=? WHERE persistence_object_identifier=?');
            $nodeUpdateQuery->execute([serialize($nodeProperties), $this->persistenceManager->getIdentifierByObject($node)]);
        }
    }

    /**
     * @return array
     */
    protected function getHandledObjectTypes()
    {
        return array(
            Asset::class,
            Audio::class,
            Document::class,
            Video::class
        );
    }
}
