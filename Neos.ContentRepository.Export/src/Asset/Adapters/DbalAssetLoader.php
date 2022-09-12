<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\Adapters;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Export\Asset\AssetLoaderInterface;
use Neos\ContentRepository\Export\Asset\ValueObject\AssetType;
use Neos\ContentRepository\Export\Asset\ValueObject\ImageAdjustmentType;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;


final class DbalAssetLoader implements AssetLoaderInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function findAssetById(string $assetId): SerializedAsset|SerializedImageVariant
    {
        $row = $this->connection->fetchAssociative('
            SELECT
                a.persistence_object_identifier identifier,
                a.dtype type,
                a.title,
                a.copyrightNotice,
                a.caption,
                a.assetSourceIdentifier,
                r.filename resource_filename,
                r.collectionName resource_collectionName,
                r.mediaType resource_mediaType,
                r.sha1 resource_sha1,
                v.originalasset originalAssetIdentifier,
                v.name,
                v.width,
                v.height,
                v.presetIdentifier,
                v.presetVariantName
            FROM
                neos_media_domain_model_asset a
            INNER JOIN
                neos_flow_resourcemanagement_persistentresource r ON r.persistence_object_identifier = a.resource
            LEFT JOIN
                neos_media_domain_model_imagevariant v ON v.persistence_object_identifier = a.persistence_object_identifier
            WHERE
                a.persistence_object_identifier = :assetId',
            ['assetId' => $assetId]
        );
        if ($row === false) {
            throw new \InvalidArgumentException(sprintf('Failed to load asset with id "%s"', $assetId), 1658495421);
        }
        if ($row['originalAssetIdentifier'] !== null) {
            $imageAdjustmentRows = $this->connection->fetchAllAssociative('SELECT * FROM neos_media_domain_model_adjustment_abstractimageadjustment WHERE imagevariant = :assetId ORDER BY position', ['assetId' => $assetId]);
            $imageAdjustments = [];
            foreach ($imageAdjustmentRows as $imageAdjustmentRow) {
                $type = match ($imageAdjustmentRow['dtype']) {
                    'neos_media_adjustment_resizeimageadjustment' => ImageAdjustmentType::RESIZE_IMAGE,
                    'neos_media_adjustment_cropimageadjustment' => ImageAdjustmentType::CROP_IMAGE,
                    'neos_media_adjustment_qualityimageadjustment' => ImageAdjustmentType::QUALITY_IMAGE,
                };
                #unset($imageAdjustmentRow['persistence_object_identifier'], $imageAdjustmentRow['imagevariant'], $imageAdjustmentRow['dtype']);
                #$imageAdjustmentRow = array_filter($imageAdjustmentRow, static fn ($value) => $value !== null);
                $imageAdjustments[] = ['type' => $type->value, 'properties' => $type->convertProperties($imageAdjustmentRow)];
            }
            return SerializedImageVariant::fromArray([
                'identifier' => $row['identifier'],
                'originalAssetIdentifier' => $row['originalAssetIdentifier'],
                'name' => $row['name'],
                'width' => $row['width'],
                'height' => $row['height'],
                'presetIdentifier' => $row['presetIdentifier'],
                'presetVariantName' => $row['presetVariantName'],
                'imageAdjustments' => $imageAdjustments
            ]);
        }
        $row = array_filter($row, static fn ($value) => $value !== null);
        foreach ($row as $key => $value) {
            if (!str_starts_with($key, 'resource_')) {
                continue;
            }
            $row['resource'][substr($key, 9)] = $value;
            unset($row[$key]);
        }
        $row['type'] = match ($row['type']) {
           'neos_media_image' => AssetType::IMAGE->value,
           'neos_media_audio' => AssetType::AUDIO->value,
           'neos_media_document' => AssetType::DOCUMENT->value,
           'neos_media_video' => AssetType::VIDEO->value,
        };
        return SerializedAsset::fromArray($row);
    }
}
