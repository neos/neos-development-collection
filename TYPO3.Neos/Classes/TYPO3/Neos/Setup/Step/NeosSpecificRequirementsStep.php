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
use TYPO3\Flow\Utility\Arrays;

/**
 * @Flow\Scope("singleton")
 */
class NeosSpecificRequirementsStep extends \TYPO3\Setup\Step\AbstractStep {

	/**
	 * @var \TYPO3\Flow\Configuration\Source\YamlSource
	 * @Flow\Inject
	 */
	protected $configurationSource;

	/**
	 * {@inheritdoc}
	 */
	protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition) {
		$page1 = $formDefinition->createPage('page1');
		$page1->setRenderingOption('header', 'Neos requirements check');

		$imageSection = $page1->createElement('connectionSection', 'TYPO3.Form:Section');
		$imageSection->setLabel('Image Manipulation');

		$foundImageHandler = FALSE;
		foreach (array('gd', 'gmagick', 'imagick') as $extensionName) {
			if (extension_loaded($extensionName)) {
				$formElement = $imageSection->createElement($extensionName, 'TYPO3.Form:StaticText');
				$formElement->setProperty('text', 'PHP extension "' . $extensionName .'" is installed');
				$formElement->setProperty('elementClassAttribute', 'alert alert-info');
				$foundImageHandler = $extensionName;
			} else {
				$formElement = $imageSection->createElement($extensionName, 'TYPO3.Form:StaticText');
				$formElement->setProperty('text', 'PHP extension "' . $extensionName . '" is not installed');
				$formElement->setProperty('elementClassAttribute', 'alert alert-warning');
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
	 * {@inheritdoc}
	 */
	public function postProcessFormValues(array $formValues) {
		$this->distributionSettings = Arrays::setValueByPath($this->distributionSettings, 'TYPO3.Imagine.driver', $formValues['imagineDriver']);
		$this->configurationSource->save(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $this->distributionSettings);

		$this->configurationManager->flushConfigurationCache();
	}

}