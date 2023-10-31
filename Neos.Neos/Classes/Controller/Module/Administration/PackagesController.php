<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Module\Administration;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Neos\Controller\Module\AbstractModuleController;

#[Flow\Scope('singleton')]
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
    public function indexAction()
    {
        $packageGroups = [];
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            /** @var Package $package */
            /** @phpstan-ignore-next-line FLOW_PATH_PACKAGES is known at this point */
            $packagePath = substr($package->getPackagepath(), strlen(FLOW_PATH_PACKAGES));
            $packageGroup = substr($packagePath, 0, strpos($packagePath, '/') ?: null);
            $packageGroups[$packageGroup][$package->getPackageKey()] = [
                'sanitizedPackageKey' => str_replace('.', '', $package->getPackageKey()),
                'version' => $package->getInstalledVersion(),
                'name' => $package->getComposerManifest('name'),
                'type' => $package->getComposerManifest('type'),
                'description' => $package->getComposerManifest('description'),
                'isFrozen' => $this->packageManager->isPackageFrozen($package->getPackageKey())
            ];
        }
        ksort($packageGroups);
        foreach (array_keys($packageGroups) as $packageGroup) {
            ksort($packageGroups[$packageGroup]);
        }
        $this->view->assignMultiple([
            'packageGroups' => $packageGroups,
            'isDevelopmentContext' => $this->objectManager->getContext()->isDevelopment()
        ]);
    }
}
