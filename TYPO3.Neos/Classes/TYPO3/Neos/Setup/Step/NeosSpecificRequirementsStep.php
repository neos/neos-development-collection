<?php
namespace TYPO3\Neos\Setup\Step;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Neos".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class NeosSpecificRequirementsStep extends \TYPO3\Setup\Step\AbstractStep {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\Source\YamlSource
	 */
	protected $configurationSource;

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Imagine\ImagineFactory
	 */
	protected $imagineFactory;

	/**
	 * @Flow\Inject
	 * @var PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * {@inheritdoc}
	 */
	protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition) {
		$page1 = $formDefinition->createPage('page1');
		$page1->setRenderingOption('header', 'Neos requirements check');

		$imageSection = $page1->createElement('connectionSection', 'TYPO3.Form:Section');
		$imageSection->setLabel('Image Manipulation');

		$formElement = $imageSection->createElement('imageLibrariesInfo', 'TYPO3.Form:StaticText');
		$formElement->setProperty('text', 'We checked for supported image manipulation libraries on your server.
		Only one is needed and we select the best one available for you.
		Using GD in production environment is not recommended as it has some issues and can easily lead to blank pages due to memory exhaustion.');
		$formElement->setProperty('elementClassAttribute', 'alert alert-primary');

		$foundImageHandler = FALSE;
		foreach (array('gd', 'gmagick', 'imagick') as $extensionName) {
			$formElement = $imageSection->createElement($extensionName, 'TYPO3.Form:StaticText');

			if (extension_loaded($extensionName)) {
				$unsupportedFormats = $this->findUnsupportedImageFormats($extensionName);
				if(count($unsupportedFormats) === 0) {
					$formElement->setProperty('text', 'PHP extension "' . $extensionName .'" is installed');
					$formElement->setProperty('elementClassAttribute', 'alert alert-info');
					$foundImageHandler = $extensionName;
				} else {
					$formElement->setProperty('text', 'PHP extension "' . $extensionName . '" is installed but lacks support for ' . implode(', ', $unsupportedFormats));
					$formElement->setProperty('elementClassAttribute', 'alert alert-default');
				}
			} else {
				$formElement->setProperty('text', 'PHP extension "' . $extensionName . '" is not installed');
				$formElement->setProperty('elementClassAttribute', 'alert alert-default');
			}
		}

		if ($foundImageHandler === FALSE) {
			$formElement = $imageSection->createElement('noImageLibrary', 'TYPO3.Form:StaticText');
			$formElement->setProperty('text', 'No suitable PHP extension for image manipulation was found. You can continue the setup but be aware that Neos might not work correctly without one of these extensions.');
			$formElement->setProperty('elementClassAttribute', 'alert alert-error');
		} else {
			$formElement = $imageSection->createElement('configuredImageLibrary', 'TYPO3.Form:StaticText');
			$formElement->setProperty('text', 'Neos will be configured to use extension "' . $foundImageHandler . '"');
			$formElement->setProperty('elementClassAttribute', 'alert alert-success');
			$hiddenField = $imageSection->createElement('imagineDriver', 'TYPO3.Form:HiddenField');
			$hiddenField->setDefaultValue(ucfirst($foundImageHandler));
		}
	}

	/**
	 * @param string $driver
	 * @return array Not supported image format
	 */
	protected function findUnsupportedImageFormats($driver) {
		$this->imagineFactory->injectSettings(array('driver' => ucfirst($driver)));
		$imagine = $this->imagineFactory->create();
		$unsupportedFormats = array();

		foreach(array('jpg', 'gif', 'png') as $imageFormat) {
			$imagePath = Files::concatenatePaths(array($this->packageManager->getPackage('TYPO3.Neos')->getResourcesPath(), 'Private/Installer/TestImages/Test.' . $imageFormat));

			try {
				$imagine->open($imagePath);
			} catch (\Exception $exception) {
				$unsupportedFormats[] = sprintf('"%s"', $imageFormat);
			}
		}

		return $unsupportedFormats;
	}

	/**
	 * {@inheritdoc}
	 */
	public function postProcessFormValues(array $formValues) {
		$this->distributionSettings = Arrays::setValueByPath($this->distributionSettings, 'TYPO3.Imagine.driver', $formValues['imagineDriver']);
		$this->configurationSource->save(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $this->distributionSettings);

		$this->configurationManager->flushConfigurationCache();
	}

}