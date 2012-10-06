<?php
namespace TYPO3\TYPO3\Controller\Module\Administration;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The TYPO3 Package Management module controller
 *
 * @Flow\Scope("singleton")
 */
class PackagesController extends \TYPO3\TYPO3\Controller\Module\StandardController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\Doctrine\Service
	 */
	protected $doctrineService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @return void
	 */
	public function indexAction() {
		$packageGroups = array();
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$packagePath = substr($package->getPackagepath(), strlen(FLOW_PATH_PACKAGES));
			$packageGroup = substr($packagePath, 0, strpos($packagePath, '/'));
			$packageGroups[$packageGroup][$package->getPackageKey()] = array(
				'sanitizedPackageKey' => str_replace('.', '', $package->getPackageKey()),
				'version' => $package->getPackageMetaData()->getVersion(),
				'name' => $package->getComposerManifest('name'),
				'type' => $package->getComposerManifest('type'),
				'description' => $package->getPackageMetaData()->getDescription(),
				'metaData' => $package->getPackageMetaData(),
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
	public function activateAction($packageKey) {
		$this->flashMessageContainer->addMessage($this->activatePackage($packageKey));
		$this->redirect('index');
	}

	/**
	 * Deactivate package
	 *
	 * @param string $packageKey Package to deactivate
	 * @return void
	 */
	public function deactivateAction($packageKey) {
		$this->flashMessageContainer->addMessage($this->deactivatePackage($packageKey));
		$this->redirect('index');
	}
	/**
	 * Import package
	 *
	 * @param string $packageKey Package to import
	 * @return void
	 */
	public function importAction($packageKey) {
		try {
			$this->packageManager->importPackage($packageKey);
			$message = new \TYPO3\Flow\Error\Message($packageKey . ' has been imported', 1343231682);
		} catch (\TYPO3\Flow\Package\Exception\PackageKeyAlreadyExistsException $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231683);
		} catch (\TYPO3\Flow\Package\Exception\PackageRepositoryException $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231684);
		}
		$this->flashMessageContainer->addMessage($message);
		$this->redirect('index');
	}

	/**
	 * Delete package
	 *
	 * @param string $packageKey Package to delete
	 * @return void
	 */
	public function deleteAction($packageKey) {
		$this->flashMessageContainer->addMessage($this->deletePackage($packageKey));
		$this->redirect('index');
	}

	/**
	 * Freeze package
	 *
	 * @param string $packageKey Package to freeze
	 * @return void
	 */
	public function freezeAction($packageKey) {
		$this->flashMessageContainer->addMessage($this->freezePackage($packageKey));
		$this->redirect('index');
	}

	/**
	 * Unfreeze package
	 *
	 * @param string $packageKey Package to freeze
	 * @return void
	 */
	public function unfreezeAction($packageKey) {
		$this->packageManager->unfreezePackage($packageKey);
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Message($packageKey . ' has been unfrozen', 1347464246));
		$this->redirect('index');
	}

	/**
	 * @param array $packageKeys
	 * @param string $action
	 * @return void
	 * @throws \RuntimeException
	 */
	public function batchAction(array $packageKeys, $action) {
		switch ($action) {
			case 'freeze':
				$frozenPackages = array();
				foreach ($packageKeys as $packageKey) {
					$message = $this->freezePackage($packageKey);
					if ($message instanceof \TYPO3\Flow\Error\Error || $message instanceof \TYPO3\Flow\Error\Warning) {
						$this->flashMessageContainer->addMessage($message);
					} else {
						array_push($frozenPackages, $packageKey);
					}
				}
				if (count($frozenPackages) > 0) {
					$message = new \TYPO3\Flow\Error\Message('Following packages have been frozen: ' . implode(', ', $frozenPackages));
				} else {
					$message = new \TYPO3\Flow\Error\Warning('Unable to freeze the selected packages');
				}
			break;
			case 'unfreeze':
				foreach ($packageKeys as $packageKey) {
					$this->packageManager->unfreezePackage($packageKey);
				}
				$message = new \TYPO3\Flow\Error\Message('Following packages have been unfrozen: ' . implode(', ', $packageKeys));
			break;
			case 'activate':
				$activatedPackages = array();
				foreach ($packageKeys as $packageKey) {
					$message = $this->activatePackage($packageKey);
					if ($message instanceof \TYPO3\Flow\Error\Error || $message instanceof \TYPO3\Flow\Error\Warning) {
						$this->flashMessageContainer->addMessage($message);
					} else {
						array_push($activatedPackages, $packageKey);
					}
				}
				if (count($activatedPackages) > 0) {
					$message = new \TYPO3\Flow\Error\Message('Following packages have been activated: ' . implode(', ', $activatedPackages));
				} else {
					$message = new \TYPO3\Flow\Error\Warning('Unable to activate the selected packages');
				}
			break;
			case 'deactivate':
				$deactivatedPackages = array();
				foreach ($packageKeys as $packageKey) {
					$message = $this->deactivatePackage($packageKey);
					if ($message instanceof \TYPO3\Flow\Error\Error || $message instanceof \TYPO3\Flow\Error\Warning) {
						$this->flashMessageContainer->addMessage($message);
					} else {
						array_push($deactivatedPackages, $packageKey);
					}
				}
				if (count($deactivatedPackages) > 0) {
					$message = new \TYPO3\Flow\Error\Message('Following packages have been deactivated: ' . implode(', ', $deactivatedPackages));
				} else {
					$message = new \TYPO3\Flow\Error\Warning('Unable to deactivate the selected packages');
				}
			break;
			case 'delete':
				$deletedPackages = array();
				foreach ($packageKeys as $packageKey) {
					$message = $this->deletePackage($packageKey);
					if ($message instanceof \TYPO3\Flow\Error\Error || $message instanceof \TYPO3\Flow\Error\Warning) {
						$this->flashMessageContainer->addMessage($message);
					} else {
						array_push($deletedPackages, $packageKey);
					}
				}
				if (count($deletedPackages) > 0) {
					$message = new \TYPO3\Flow\Error\Message('Following packages have been deleted: ' . implode(', ', $deletedPackages));
				} else {
					$message = new \TYPO3\Flow\Error\Warning('Unable to delete the selected packages');
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
	 * @return \TYPO3\Flow\Error\Error|\TYPO3\Flow\Error\Message
	 */
	protected function activatePackage($packageKey) {
		try {
			$this->packageManager->activatePackage($packageKey);
			$this->doctrineService->updateSchema();
			$message = new \TYPO3\Flow\Error\Message('The package ' . $packageKey . ' is activated', 1343231680);
		} catch (\TYPO3\Flow\Package\Exception\UnknownPackageException $exception) {
			$message = new \TYPO3\Flow\Error\Error('The package ' . $packageKey . ' is not present and can not be activated', 1343231681);
		}
		return $message;
	}

	/**
	 * @param string $packageKey
	 * @return \TYPO3\Flow\Error\Error|\TYPO3\Flow\Error\Message
	 */
	protected function deactivatePackage($packageKey) {
		try {
			$this->packageManager->deactivatePackage($packageKey);
			$this->doctrineService->updateSchema();
			$message = new \TYPO3\Flow\Error\Message($packageKey . ' was deactivated', 1343231678);
		} catch (\TYPO3\Flow\Package\Exception\ProtectedPackageKeyException $exception) {
			$message = new \TYPO3\Flow\Error\Error('The package ' . $packageKey . ' is protected and can not be deactivated', 1343231679);
		}
		return $message;
	}

	/**
	 * @param string $packageKey
	 * @return \TYPO3\Flow\Error\Error|\TYPO3\Flow\Error\Message
	 */
	protected function deletePackage($packageKey) {
		try {
			$this->packageManager->deletePackage($packageKey);
			$message = new \TYPO3\Flow\Error\Message($packageKey . ' has been deleted', 1343231685);
		} catch (\TYPO3\Flow\Package\Exception\UnknownPackageException $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231686);
		} catch (\TYPO3\Flow\Package\Exception\ProtectedPackageKeyException $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231687);
		} catch (\TYPO3\Flow\Package\Exception $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231688);
		}
		return $message;
	}

	/**
	 * @param string $packageKey
	 * @return \TYPO3\Flow\Error\Error|\TYPO3\Flow\Error\Message
	 */
	protected function freezePackage($packageKey) {
		try {
			$this->packageManager->freezePackage($packageKey);
			$message = new \TYPO3\Flow\Error\Message($packageKey . ' has been frozen', 1343231689);
		} catch (\LogicException $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231690);
		} catch (\TYPO3\Flow\Package\Exception\UnknownPackageException $exception) {
			$message = new \TYPO3\Flow\Error\Error($exception->getMessage(), 1343231691);
		}
		return $message;
	}

}
?>