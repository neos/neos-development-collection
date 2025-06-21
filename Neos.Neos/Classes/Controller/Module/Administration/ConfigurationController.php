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
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\ConfigurationSchemaValidator;
use Neos\Flow\Configuration\Exception\SchemaValidationException;
use Neos\Neos\Controller\Module\ModuleTranslationTrait;
use Neos\Utility\Arrays;
use Neos\Utility\SchemaGenerator;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Error\Messages\Message;

/**
 * The Neos Configuration module controller
 */
class ConfigurationController extends AbstractModuleController
{
    use ModuleTranslationTrait;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject(lazy = false)
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
        $this->view->assignMultiple([
            'type' => $type,
            'availableConfigurationTypes' => $availableConfigurationTypes
        ]);

        if (in_array($type, $availableConfigurationTypes)) {
            $this->view->assign('configuration', self::scrubConfiguredSecrets(
                $this->configurationManager->getConfiguration($type),
                $this->moduleConfiguration['settings']['automaticSecretScrubbingPattern'] ?? null,
                $this->moduleConfiguration['settings']['configurationPathsWithSecrets'][$type] ?? []
            ));

            try {
                $this->view->assign('validationResult', $this->configurationSchemaValidator->validate($type));
            } catch (SchemaValidationException $exception) {
                $this->addFlashMessage(
                    htmlspecialchars($exception->getMessage()),
                    $this->getModuleLabel('configuration.anErrorOccurredDuringValidationOfTheConfiguration.title'),
                    Message::SEVERITY_ERROR,
                    [],
                    1412373972
                );
            }
        } else {
            $this->addFlashMessage(
                $this->getModuleLabel('configuration.configurationTypeNotFound.body'),
                '',
                Message::SEVERITY_ERROR,
                [],
                1412373998
            );
        }
    }

    public static function scrubConfiguredSecrets(array $configuration, ?string $automaticSecretScrubbingPattern, array $pathsToBeScrubbed, string $currentPathPrefix = ''): array
    {
        $scrubbedConfiguration = $configuration;
        foreach ($scrubbedConfiguration as $key => $value) {
            $path = $currentPathPrefix . $key;
            if (is_array($value)) {
                $scrubbedConfiguration[$key] = self::scrubConfiguredSecrets($value, $automaticSecretScrubbingPattern, $pathsToBeScrubbed, $path . '.');
                continue;
            }

            if (in_array($path, $pathsToBeScrubbed, true)) {
                // If the path is in the list of paths to be scrubbed, replace the value with '***'
                $scrubbedConfiguration[$key] = '***';
                continue;
            }

            if ($automaticSecretScrubbingPattern && preg_match(
                $automaticSecretScrubbingPattern,
                    (string)$key
            )) {
                // If the key matches the automatic secret scrubbing pattern, replace the value with '***'
                $scrubbedConfiguration[$key] = '***';
            }
        }

        return $scrubbedConfiguration;
    }
}
