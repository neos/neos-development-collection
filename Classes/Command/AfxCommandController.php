<?php
namespace PackageFactory\AtomicFusion\AFX\Command;

/*                                                                             *
 * This script belongs to the Neos package "Packagefactory.AtomicFusion.AFX".  *
 *                                                                             *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Files;
use PackageFactory\AtomicFusion\AFX\Package as AfxPackage;
use PackageFactory\AtomicFusion\AFX\Service\AfxService;

/**
 * @Flow\Scope("singleton")
 */
class AfxCommandController extends CommandController
{

    /**
     * @var PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * Expand afx in fusion code to pure fusion, this can be usefull before
     * removing the afx package.
     *
     * @param string $packageKey the key of the fusion file package
     * @param boolean $yes confirm execution without further input
     * @return void
     */
    public function ejectCommand($packageKey, $yes = false)
    {

        if ($this->packageManager->isPackageAvailable($packageKey) == false) {
            $this->outputLine('Package %s is not available', [$packageKey]);
            $this->quit(1);
        }

        $this->outputLine(
            'This command will wxpand all afx`...` expressions in fusion files of package %s to fusion',
            [$packageKey]
        );

        if (!$yes) {
            $this->outputLine("Are you sure you want to do this?  Type 'yes' to continue: ");
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) != 'yes') {
                $this->outputLine('exit');
                $this->quit(1);
            } else {
                $this->outputLine();
                $this->outputLine();
            }
        }

        $package = $this->packageManager->getPackage($packageKey);
        $fusionPath = $package->getResourcesPath() . 'Private/Fusion';
        if (!file_exists($fusionPath)) {
            $this->outputLine('Fusion path %s is not found', [$fusionPath]);
            $this->quit(1);
        }

        $fusionFiles = Files::readDirectoryRecursively($fusionPath, 'fusion');
        foreach ($fusionFiles as $fusionFile) {
            $fusionCode = file_get_contents($fusionFile);
            if (preg_match(AfxPackage::SCAN_PATTERN_AFX, $fusionCode)) {
                $this->outputLine(' - Expand afx in fusion file %s ', [$fusionFile]);
                $fusionCodeProcessed = preg_replace_callback(
                    AfxPackage::SCAN_PATTERN_AFX,
                    function ($matches) {
                        $indentation = $matches[1];
                        $property = $matches[2];
                        $afx = $matches[3];
                        $fusion = $indentation . $property . ' = ' . AfxService::convertAfxToFusion($afx, $indentation);
                        return $fusion;
                    },
                    $fusionCode
                );
                file_put_contents($fusionFile, $fusionCodeProcessed);
            }
        }
    }


    /**
     * Show the afx detection and expansion to pure fusion, this is useful for learning and understanding
     *
     * @param string $packageKey the key of the fusion file package
     * @return void
     */
    public function showCommand($packageKey)
    {

        if ($this->packageManager->isPackageAvailable($packageKey) == false) {
            $this->outputLine('Package %s is not available', [$packageKey]);
            $this->quit(1);
        }

        $this->outputLine(
            'This command will wxpand all afx`...` expressions in fusion files of package %s to fusion',
            [$packageKey]
        );

        $package = $this->packageManager->getPackage($packageKey);
        $fusionPath = $package->getResourcesPath() . 'Private/Fusion';
        if (!file_exists($fusionPath)) {
            $this->outputLine('Fusion path %s is not found', [$fusionPath]);
            $this->quit(1);
        }

        $fusionFiles = Files::readDirectoryRecursively($fusionPath, 'fusion');
        foreach ($fusionFiles as $fusionFile) {
            $fusionCode = file_get_contents($fusionFile);
            if (preg_match(AfxPackage::SCAN_PATTERN_AFX, $fusionCode)) {
                $this->outputLine();
                $this->outputLine(' - Found afx in fusion file %s', [$fusionFile]);
                $fusionCodeProcessed = preg_replace_callback(
                    AfxPackage::SCAN_PATTERN_AFX,
                    function ($matches) {
                        $indentation = $matches[1];
                        $property = $matches[2];
                        $afx = $matches[3];
                        $fusion = $indentation . $property . ' = ' . AfxService::convertAfxToFusion($afx, $indentation);

                        $this->outputLine(' --');
                        $this->outputFormatted('<error>%s</error>', [$matches[0]]);
                        $this->outputLine(' --');
                        $this->outputFormatted('<info>%s</info>', [$fusion]);

                        return $fusion;
                    },
                    $fusionCode
                );
            }
        }
    }
}
