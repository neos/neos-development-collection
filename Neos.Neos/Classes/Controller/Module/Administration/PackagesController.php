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
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Message;
use Neos\Error\Messages\Warning;
use Neos\Flow\Package;
use Neos\Flow\Package\Exception\ProtectedPackageKeyException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\Exception;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;

/**
 * @Flow\Scope("singleton")
 */
class PackagesController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @return void
     */
    public function indexAction()
    {
        $packageGroups = array();
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            /** @var Package $package */
            $packagePath = substr($package->getPackagepath(), strlen(FLOW_PATH_PACKAGES));
            $packageGroup = substr($packagePath, 0, strpos($packagePath, '/'));
            $packageGroups[$packageGroup][$package->getPackageKey()] = array(
                'sanitizedPackageKey' => str_replace('.', '', $package->getPackageKey()),
                'version' => $package->getInstalledVersion(),
                'name' => $package->getComposerManifest('name'),
                'type' => $package->getComposerManifest('type'),
                'description' => $package->getComposerManifest('description'),
                'isActive' => $this->packageManager->isPackageActive($package->getPackageKey()),
                'isFrozen' => $this->packageManager->isPackageFrozen($package->getPackageKey()),
                'isProtected' => $package->isProtected()
            );
        }
        ksort($packageGroups);
        foreach (array_keys($packageGroups) as $packageGroup) {
            ksort($packageGroups[$packageGroup]);
        }
        $this->view->assignMultiple(array(
            'packageGroups' => $packageGroups,
            'isDevelopmentContext' => $this->objectManager->getContext()->isDevelopment()
        ));
    }
}
