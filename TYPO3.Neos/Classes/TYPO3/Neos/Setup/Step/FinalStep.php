<?php
namespace TYPO3\Neos\Setup\Step;

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
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Form\Core\Model\FormDefinition;
use Neos\Setup\Step\AbstractStep;

/**
 * @Flow\Scope("singleton")
 */
class FinalStep extends AbstractStep
{
    /**
     * Returns the form definitions for the step
     *
     * @param FormDefinition $formDefinition
     * @return void
     */
    protected function buildForm(FormDefinition $formDefinition)
    {
        $page1 = $formDefinition->createPage('page1');
        $page1->setRenderingOption('header', 'Setup complete');

        $congratulations = $page1->createElement('congratulationsSection', 'Neos.Form:Section');
        $congratulations->setLabel('Congratulations');

        $success = $congratulations->createElement('success', 'Neos.Form:StaticText');
        $success->setProperty('text', 'You have successfully installed Neos! If you need help getting started, please refer to the Neos documentation.');
        $success->setProperty('elementClassAttribute', 'alert alert-success');

        $docs = $congratulations->createElement('docsLink', 'Neos.Setup:LinkElement');
        $docs->setLabel('Read the documentation');
        $docs->setProperty('href', 'https://neos.readthedocs.org/');
        $docs->setProperty('target', '_blank');

        $contextEnv = Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
        $applicationContext = new ApplicationContext($contextEnv);
        if (!$applicationContext->isProduction()) {
            $context = $page1->createElement('contextSection', 'Neos.Form:Section');
            $context->setLabel('Define application context');
            $contextInfo = $context->createElement('contextInfo', 'Neos.Form:StaticText');
            $contextInfo->setProperty('text', 'Your Neos installation is currently not running in "Production" context. If you want to experience Neos with its full speed, you should now change your FLOW_CONTEXT environment variable to "Production".');
            $contextDocs = $context->createElement('contextDocsLink', 'Neos.Setup:LinkElement');
            $contextDocs->setLabel('Read about application contexts');
            $contextDocs->setProperty('href', 'http://flowframework.readthedocs.org/en/stable/TheDefinitiveGuide/PartIII/Bootstrapping.html#the-typo3-flow-application-context');
            $contextDocs->setProperty('target', '_blank');
        }

        $frontend = $page1->createElement('frontendSection', 'Neos.Form:Section');
        $frontend->setLabel('View the site');

        $link = $frontend->createElement('link', 'Neos.Setup:LinkElement');
        $link->setLabel('Go to the frontend');
        $link->setProperty('href', '/');
        $link->setProperty('elementClassAttribute', 'btn btn-large btn-primary');

        $backend = $page1->createElement('backendSection', 'Neos.Form:Section');
        $backend->setLabel('Start editing');

        $backendLink = $backend->createElement('backendLink', 'Neos.Setup:LinkElement');
        $backendLink->setLabel('Go to the backend');
        $backendLink->setProperty('href', '/neos');
        $backendLink->setProperty('elementClassAttribute', 'btn btn-large btn-primary');

        $loggedOut = $page1->createElement('loggedOut', 'Neos.Form:StaticText');
        $loggedOut->setProperty('text', 'You have automatically been logged out for security reasons since this is the final step. Refresh the page to log in again if you missed something.');
        $loggedOut->setProperty('elementClassAttribute', 'alert alert-info');
    }
}
