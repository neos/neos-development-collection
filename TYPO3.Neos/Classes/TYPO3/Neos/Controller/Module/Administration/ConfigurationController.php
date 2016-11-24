<?php
namespace TYPO3\Neos\Controller\Module\Administration;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\ConfigurationSchemaValidator;
use Neos\Flow\Configuration\Exception\SchemaValidationException;
use Neos\Flow\Utility\SchemaGenerator;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use Neos\Flow\Error\Message;

/**
 * The Neos Configuration module controller
 */
class ConfigurationController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var ConfigurationSchemaValidator
     */
    protected $configurationSchemaValidator;

    /**
     * @Flow\Inject
     * @var SchemaGenerator
     */
    protected $schemaGenerator;

    /**
     * @param string $type
     * @return void
     */
    public function indexAction($type = 'Settings')
    {
        $availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
        $this->view->assignMultiple(array(
            'type' => $type,
            'availableConfigurationTypes' => $availableConfigurationTypes
        ));

        if (in_array($type, $availableConfigurationTypes)) {
            $this->view->assign('configuration', $this->configurationManager->getConfiguration($type));

            try {
                $this->view->assign('validationResult', $this->configurationSchemaValidator->validate($type));
            } catch (SchemaValidationException $exception) {
                $this->addFlashMessage(htmlspecialchars($exception->getMessage()), 'An error occurred during validation of the configuration.', Message::SEVERITY_ERROR, array(), 1412373972);
            }
        } else {
            $this->addFlashMessage('Configuration type not found.', '', Message::SEVERITY_ERROR, array(), 1412373998);
        }
    }
}
