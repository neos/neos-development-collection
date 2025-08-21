<?php
namespace Neos\Neos\Controller\Module\Administration;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;

/**
 * @Flow\Scope("singleton")
 */
class PackagesController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @return void
     */
    public function indexAction(string $sortBy = 'name', string $sortDirection = QueryInterface::ORDER_ASCENDING)
    {
        $packageGroups = [];

        foreach ($this->packageManager->getAvailablePackages() as $package) {
            /** @var Package $package */
            $packagePath = substr($package->getPackagepath(), strlen(FLOW_PATH_PACKAGES));
            $packageGroup = substr($packagePath, 0, strpos($packagePath, '/'));

            $packageGroups[$packageGroup][$package->getPackageKey()] = [
                'sanitizedPackageKey' => str_replace('.', '', $package->getPackageKey()),
                'version' => $package->getInstalledVersion(),
                'name' => $package->getComposerManifest('name'),
                'type' => $package->getComposerManifest('type'),
                'description' => $package->getComposerManifest('description'),
                'isFrozen' => $this->packageManager->isPackageFrozen($package->getPackageKey())
            ];
        }

        foreach ($packageGroups as &$packages) {
            uasort($packages, function ($a, $b) use ($sortBy, $sortDirection) {
                $valueA = $a[$sortBy] ?? '';
                $valueB = $b[$sortBy] ?? '';

                $result = strnatcasecmp((string)$valueA, (string)$valueB);
                return $sortDirection === QueryInterface::ORDER_DESCENDING ? -$result : $result;
            });
        }
        unset($packages);

        $this->view->assignMultiple([
            'packageGroups' => $packageGroups,
            'isDevelopmentContext' => $this->objectManager->getContext()->isDevelopment(),
            'sortDirection' => $sortDirection,
            'sortBy' => $sortBy,
        ]);
    }
}
