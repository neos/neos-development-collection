<?php
namespace Neos\Neos\TYPO3CR\Transformations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Video;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

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
     * Doctrine's Entity Manager.
     *
     * @Flow\Inject
     * @var EntityManagerInterface
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

                $nodeProperties[$propertyName] = [
                    '__flow_object_type' => $objectType,
                    '__identifier' => $objectIdentifier
                ];
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
        return [
            Asset::class,
            Audio::class,
            Document::class,
            Video::class
        ];
    }
}
