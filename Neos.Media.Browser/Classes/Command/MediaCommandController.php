<?php

namespace Neos\Media\Browser\Command;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\AssetSource\MediaAssetSourceAware;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class MediaCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * Remove unused assets
     *
     * This command iterates over all existing assets, checks their usage count and lists the assets which are not
     * reported as used by any AssetUsageStrategies. The unused assets can than be removed.
     *
     * @param string $assetSource If specified, only assets of this asset source are considered. For example "neos" or "my-asset-management-system"
     * @param bool $quiet If set, only errors will be displayed.
     * @param bool $assumeYes If set, "yes" is assumed for the "shall I remove ..." dialogs
     * @return void
     */
    public function removeUnusedCommand(string $assetSource = '', bool $quiet = false, bool $assumeYes = false)
    {
        $iterator = $this->assetRepository->findAllIterator();
        $assetCount = $this->assetRepository->countAll();
        $unusedAssets = [];
        $tableRowsByAssetSource = [];
        $unusedAssetCount = 0;
        $unusedAssetsTotalSize = 0;

        $filterByAssetSourceIdentifier = $assetSource;
        if ($filterByAssetSourceIdentifier === '') {
            !$quiet && $this->outputLine('<b>Searching for unused assets:</b>');
        } else {
            !$quiet && $this->outputLine('<b>Searching for unused assets of asset source "%s":</b>', [$filterByAssetSourceIdentifier]);
        }

        !$quiet && $this->output->progressStart($assetCount);

        foreach ($this->assetRepository->iterate($iterator) as $asset) {
            if ($asset instanceof Asset && $asset->getUsageCount() === 0) {
                if (!$asset instanceof MediaAssetSourceAware) {
                    continue;
                }
                if ($filterByAssetSourceIdentifier !== '' && $asset->getAssetSourceIdentifier() !== $filterByAssetSourceIdentifier) {
                    continue;
                }

                $assetSource = $asset->getAssetSource();
                $fileSize = str_pad(Files::bytesToSizeString($asset->getResource()->getFileSize()), 9, ' ', STR_PAD_LEFT);

                $unusedAssets[] = $asset;
                $tableRowsByAssetSource[$assetSource->getIdentifier()][] = [
                    $asset->getIdentifier(),
                    $asset->getResource()->getFilename(),
                    $fileSize
                ];
                $unusedAssetCount++;
                $unusedAssetsTotalSize += $asset->getResource()->getFileSize();
            }
            !$quiet && $this->output->progressAdvance(1);
        }

        !$quiet && $this->output->progressFinish();

        if ($unusedAssetCount === 0) {
            !$quiet && $this->output->outputLine(PHP_EOL . sprintf('No unused assets found.', $unusedAssetCount));
            exit;
        }

        foreach ($tableRowsByAssetSource as $assetSourceIdentifier => $tableRows) {
            !$quiet && $this->outputLine(PHP_EOL . 'Found the following unused assets from asset source <success>%s</success>: ' . PHP_EOL, [$assetSourceIdentifier]);

            !$quiet && $this->output->outputTable(
                $tableRows,
                ['Asset identifier', 'Filename', 'Size']
            );
        }

        !$quiet && $this->outputLine(PHP_EOL . 'Total size of unused assets: %s' . PHP_EOL, [Files::bytesToSizeString($unusedAssetsTotalSize)]);

        if ($assumeYes === false) {
            if (!$this->output->askConfirmation(sprintf('Do you want to remove <b>%s</b> unused assets?', $unusedAssetCount))) {
                exit(1);
            }
        }

        !$quiet && $this->output->progressStart($unusedAssetCount);
        foreach ($unusedAssets as $asset) {
            !$quiet && $this->output->progressAdvance(1);
            $this->assetRepository->remove($asset);
        }
        !$quiet && $this->output->progressFinish();
    }
}
