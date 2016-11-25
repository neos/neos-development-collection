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
use Neos\Neos\Controller\Module\AbstractModuleController;

/**
 * The TYPO3 Package Management module controller
 *
 * @Flow\Scope("singleton")
 */
class PackagesController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\Package\PackageManagerInterface
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

    /**
     * Activate package
     *
     * @param string $packageKey Package to activate
     * @return void
     */
    public function activateAction($packageKey)
    {
        $this->flashMessageContainer->addMessage($this->activatePackage($packageKey));
        $this->redirect('index');
    }

    /**
     * Deactivate package
     *
     * @param string $packageKey Package to deactivate
     * @return void
     */
    public function deactivateAction($packageKey)
    {
        $this->flashMessageContainer->addMessage($this->deactivatePackage($packageKey));
        $this->redirect('index');
    }

    /**
     * Delete package
     *
     * @param string $packageKey Package to delete
     * @return void
     */
    public function deleteAction($packageKey)
    {
        $this->flashMessageContainer->addMessage($this->deletePackage($packageKey));
        $this->redirect('index');
    }

    /**
     * Freeze package
     *
     * @param string $packageKey Package to freeze
     * @return void
     */
    public function freezeAction($packageKey)
    {
        $this->flashMessageContainer->addMessage($this->freezePackage($packageKey));
        $this->redirect('index');
    }

    /**
     * Unfreeze package
     *
     * @param string $packageKey Package to freeze
     * @return void
     */
    public function unfreezeAction($packageKey)
    {
        $this->packageManager->unfreezePackage($packageKey);
        $this->flashMessageContainer->addMessage(new Message('%s has been unfrozen', 1347464246, array($packageKey)));
        $this->redirect('index');
    }

    /**
     * @param array $packageKeys
     * @param string $action
     * @return void
     * @throws \RuntimeException
     */
    public function batchAction(array $packageKeys, $action)
    {
        switch ($action) {
            case 'freeze':
                $frozenPackages = array();
                foreach ($packageKeys as $packageKey) {
                    $message = $this->freezePackage($packageKey);
                    if ($message instanceof Error || $message instanceof Warning) {
                        $this->flashMessageContainer->addMessage($message);
                    } else {
                        array_push($frozenPackages, $packageKey);
                    }
                }
                if (count($frozenPackages) > 0) {
                    $message = new Message('Following packages have been frozen: %s', 1412547087, array(implode(', ', $frozenPackages)));
                } else {
                    $message = new Warning('Unable to freeze the selected packages', 1412547216);
                }
            break;
            case 'unfreeze':
                foreach ($packageKeys as $packageKey) {
                    $this->packageManager->unfreezePackage($packageKey);
                }
                $message = new Message('Following packages have been unfrozen: %s', 1412547219, array(implode(', ', $packageKeys)));
            break;
            case 'activate':
                $activatedPackages = array();
                foreach ($packageKeys as $packageKey) {
                    $message = $this->activatePackage($packageKey);
                    if ($message instanceof Error || $message instanceof Warning) {
                        $this->flashMessageContainer->addMessage($message);
                    } else {
                        array_push($activatedPackages, $packageKey);
                    }
                }
                if (count($activatedPackages) > 0) {
                    $message = new Message('Following packages have been activated: %s', 1412547283, array(implode(', ', $activatedPackages)));
                } else {
                    $message = new Warning('Unable to activate the selected packages', 1412547324);
                }
            break;
            case 'deactivate':
                $deactivatedPackages = array();
                foreach ($packageKeys as $packageKey) {
                    $message = $this->deactivatePackage($packageKey);
                    if ($message instanceof Error || $message instanceof Warning) {
                        $this->flashMessageContainer->addMessage($message);
                    } else {
                        array_push($deactivatedPackages, $packageKey);
                    }
                }
                if (count($deactivatedPackages) > 0) {
                    $message = new Message('Following packages have been deactivated: %s', 1412545904, array(implode(', ', $deactivatedPackages)));
                } else {
                    $message = new Warning('Unable to deactivate the selected packages', 1412545976);
                }
            break;
            case 'delete':
                $deletedPackages = array();
                foreach ($packageKeys as $packageKey) {
                    $message = $this->deletePackage($packageKey);
                    if ($message instanceof Error || $message instanceof Warning) {
                        $this->flashMessageContainer->addMessage($message);
                    } else {
                        array_push($deletedPackages, $packageKey);
                    }
                }
                if (count($deletedPackages) > 0) {
                    $message = new Message('Following packages have been deleted: %s', 1412547479, array(implode(', ', $deletedPackages)));
                } else {
                    $message = new Warning('Unable to delete the selected packages', 1412546138);
                }
            break;
            default:
                throw new \RuntimeException('Invalid action "' . $action . '" given.', 1347463918);
        }

        $this->flashMessageContainer->addMessage($message);
        $this->redirect('index');
    }

    /**
     * @param string $packageKey
     * @return Error|Message
     */
    protected function activatePackage($packageKey)
    {
        try {
            $this->packageManager->activatePackage($packageKey);
            $message = new Message('The package %s is activated', 1343231680, array($packageKey));
        } catch (UnknownPackageException $exception) {
            $message = new Error('The package %s is not present and can not be activated', 1343231681, array($packageKey));
        }
        return $message;
    }

    /**
     * @param string $packageKey
     * @return Error|Message
     */
    protected function deactivatePackage($packageKey)
    {
        try {
            $this->packageManager->deactivatePackage($packageKey);
            $message = new Message('%s was deactivated', 1343231678, array($packageKey));
        } catch (ProtectedPackageKeyException $exception) {
            $message = new Error('The package %s is protected and can not be deactivated', 1343231679, array($packageKey));
        }
        return $message;
    }

    /**
     * @param string $packageKey
     * @return Error|Message
     */
    protected function deletePackage($packageKey)
    {
        try {
            $this->packageManager->deletePackage($packageKey);
            $message = new Message('Package %s has been deleted', 1343231685, array($packageKey));
        } catch (UnknownPackageException $exception) {
            $message = new Error($exception->getMessage(), 1343231686);
        } catch (ProtectedPackageKeyException $exception) {
            $message = new Error($exception->getMessage(), 1343231687);
        } catch (Exception $exception) {
            $message = new Error($exception->getMessage(), 1343231688);
        }
        return $message;
    }

    /**
     * @param string $packageKey
     * @return Error|Message
     */
    protected function freezePackage($packageKey)
    {
        try {
            $this->packageManager->freezePackage($packageKey);
            $message = new Message('Package %s has been frozen', 1343231689, array($packageKey));
        } catch (\LogicException $exception) {
            $message = new Error($exception->getMessage(), 1343231690);
        } catch (UnknownPackageException $exception) {
            $message = new Error($exception->getMessage(), 1343231691);
        }
        return $message;
    }
}
