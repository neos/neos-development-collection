<?php
namespace TYPO3\TYPO3\Controller\Module\Administration;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TYPO3 Package Management module controller
 *
 * @FLOW3\Scope("singleton")
 */
class PackagesController extends \TYPO3\TYPO3\Controller\Module\StandardController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\Doctrine\Service
	 */
	protected $doctrineService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @return void
	 */
	public function indexAction() {
		$packages = array();
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$packages[$package->getPackageKey()] = array(
				'sanitizedPackageKey' => str_replace('.', '', $package->getPackageKey()),
				'version' => $package->getPackageMetaData()->getVersion(),
				'title' => $package->getPackageMetaData()->getTitle(),
				'description' => $package->getPackageMetaData()->getDescription(),
				'metaData' => $package->getPackageMetaData(),
				'isActive' => $this->packageManager->isPackageActive($package->getPackageKey()),
				'isFrozen' => $this->packageManager->isPackageFrozen($package->getPackageKey()),
				'isProtected' => $package->isProtected()
			);
		}
		$this->view->assignMultiple(array(
			'packages' => $packages,
			'isDevelopmentContext' => $this->objectManager->getContext()->isDevelopment()
		));
	}

	/**
	 * Deactivate package
	 *
	 * @param string $packageKey Package to deactivate
	 * @return void
	 */
	public function deactivateAction($packageKey) {
		try {
			$this->packageManager->deactivatePackage($packageKey);
			$this->doctrineService->updateSchema();
			$message = new \TYPO3\FLOW3\Error\Message($packageKey . ' was deactivated', 1343231678);
		} catch (\TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error('The package ' . $packageKey . ' is protected and can not be deactivated', 1343231679);
		}
		$this->flashMessageContainer->addMessage($message);
		$this->redirect('index');
	}

	/**
	 * Activate package
	 *
	 * @param string $packageKey Package to activate
	 * @return void
	 */
	public function activateAction($packageKey) {
		try {
			$this->packageManager->activatePackage($packageKey);
			$this->doctrineService->updateSchema();
			$message = new \TYPO3\FLOW3\Error\Message('The package ' . $packageKey . ' is activated', 1343231680);
		} catch (\TYPO3\FLOW3\Package\Exception\UnknownPackageException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error('The package ' . $packageKey . ' is not present and can not be activated', 1343231681);
		}
		$this->flashMessageContainer->addMessage($message);
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
			$message = new \TYPO3\FLOW3\Error\Message($packageKey . ' has been imported', 1343231682);
		} catch (\TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231683);
		} catch (\TYPO3\FLOW3\Package\Exception\PackageRepositoryException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231684);
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
		try {
			$this->packageManager->deletePackage($packageKey);
			$message = new \TYPO3\FLOW3\Error\Message($packageKey . ' has been deleted', 1343231685);
		} catch (\TYPO3\FLOW3\Package\Exception\UnknownPackageException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231686);
		} catch (\TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231687);
		} catch (\TYPO3\FLOW3\Package\Exception $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231688);
		}
		$this->flashMessageContainer->addMessage($message);
		$this->redirect('index');
	}

	/**
	 * Freeze package
	 *
	 * @param string $packageKey Package to freeze
	 * @return void
	 */
	public function freezeAction($packageKey) {
		try {
			$this->packageManager->freezePackage($packageKey);
			$message = new \TYPO3\FLOW3\Error\Message($packageKey . ' has been frozen', 1343231689);
		} catch (\LogicException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231690);
		} catch (\TYPO3\FLOW3\Package\Exception\UnknownPackageException $exception) {
			$message = new \TYPO3\FLOW3\Error\Error($exception->getMessage(), 1343231691);
		}
		$this->flashMessageContainer->addMessage($message);
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
		$this->redirect('index');
	}

}
?>