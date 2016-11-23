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
use TYPO3\Flow\ResourceManagement\ResourceManager;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\TypeConverter\ProcessingInstructionsConverter;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

/**
 * Convert serialized (old resource management) ImageVariants to new ImageVariants.
 */
class ImageVariantTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ProcessingInstructionsConverter
     */
    protected $processingInstructionsConverter;

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
            if (isset($propertyConfiguration['type']) && ($propertyConfiguration['type'] === ImageInterface::class || preg_match('/array\<.*\>/', $propertyConfiguration['type']))) {
                if (!isset($nodeProperties)) {
                    $nodeRecordQuery = $this->entityManager->getConnection()->prepare('SELECT properties FROM typo3_typo3cr_domain_model_nodedata WHERE persistence_object_identifier=?');
                    $nodeRecordQuery->execute([$this->persistenceManager->getIdentifierByObject($node)]);
                    $nodeRecord = $nodeRecordQuery->fetch(\PDO::FETCH_ASSOC);
                    $nodeProperties = unserialize($nodeRecord['properties']);
                }

                if (!isset($nodeProperties[$propertyName]) || empty($nodeProperties[$propertyName])) {
                    continue;
                }

                if ($propertyConfiguration['type'] === ImageInterface::class) {
                    $adjustments = array();
                    $oldVariantConfiguration = $nodeProperties[$propertyName];
                    if (is_array($oldVariantConfiguration)) {
                        foreach ($oldVariantConfiguration as $variantPropertyName => $property) {
                            switch (substr($variantPropertyName, 3)) {
                                case 'originalImage':
                                    /**
                                     * @var $originalAsset Image
                                     */
                                    $originalAsset = $this->assetRepository->findByIdentifier($this->persistenceManager->getIdentifierByObject($property));
                                    break;
                                case 'processingInstructions':
                                    $adjustments = $this->processingInstructionsConverter->convertFrom($property, 'array');
                                    break;
                            }
                        }

                        $nodeProperties[$propertyName] = null;
                        if (isset($originalAsset)) {
                            $stream = $originalAsset->getResource()->getStream();
                            if ($stream === false) {
                                continue;
                            }

                            fclose($stream);
                            $newImageVariant = new ImageVariant($originalAsset);
                            foreach ($adjustments as $adjustment) {
                                $newImageVariant->addAdjustment($adjustment);
                            }
                            $originalAsset->addVariant($newImageVariant);
                            $this->assetRepository->update($originalAsset);
                            $nodeProperties[$propertyName] = $this->persistenceManager->getIdentifierByObject($newImageVariant);
                        }
                    }
                } elseif (preg_match('/array\<.*\>/', $propertyConfiguration['type'])) {
                    if (is_array($nodeProperties[$propertyName])) {
                        $convertedValue = [];
                        foreach ($nodeProperties[$propertyName] as $entryValue) {
                            if (!is_object($entryValue)) {
                                continue;
                            }

                            $stream = $entryValue->getResource()->getStream();
                            if ($stream === false) {
                                continue;
                            }

                            fclose($stream);
                            $existingObjectIdentifier = null;
                            try {
                                $existingObjectIdentifier = $this->persistenceManager->getIdentifierByObject($entryValue);
                                if ($existingObjectIdentifier !== null) {
                                    $convertedValue[] = $existingObjectIdentifier;
                                }
                            } catch (\Exception $exception) {
                            }
                        }
                        $nodeProperties[$propertyName] = $convertedValue;
                    }
                }
            }
        }

        if (isset($nodeProperties)) {
            $nodeUpdateQuery = $this->entityManager->getConnection()->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties=? WHERE persistence_object_identifier=?');
            $nodeUpdateQuery->execute([serialize($nodeProperties), $this->persistenceManager->getIdentifierByObject($node)]);
        }
    }
}
