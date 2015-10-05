<?php
namespace TYPO3\Neos\Routing;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class BackendModuleArgumentsRoutePartHandler extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Iterate through the configured modules, find the matching module and set
     * the route path accordingly
     *
     * @param array $value (contains action, controller and package of the module controller)
     * @return boolean
     */
    protected function resolveValue($value)
    {
        if (is_array($value)) {
            $this->value = isset($value['@action']) && $value['@action'] !== 'index' ? $value['@action'] : '';
            if ($this->value !== '' && isset($value['@format'])) {
                $this->value .= '.' . $value['@format'];
            }
            $exceedingArguments = array();
            foreach ($value as $argumentKey => $argumentValue) {
                if (substr($argumentKey, 0, 1) !== '@' && substr($argumentKey, 0, 2) !== '__') {
                    $exceedingArguments[$argumentKey] = $argumentValue;
                }
            }
            if ($exceedingArguments !== array()) {
                $exceedingArguments = \TYPO3\Flow\Utility\Arrays::removeEmptyElementsRecursively($exceedingArguments);
                $exceedingArguments = $this->persistenceManager->convertObjectsToIdentityArrays($exceedingArguments);
                $queryString = http_build_query(array($this->name => $exceedingArguments), null, '&');
                if ($queryString !== '') {
                    $this->value .= '?' . $queryString;
                }
            }
        }

        return true;
    }
}
