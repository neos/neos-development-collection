<?php
namespace Neos\Neos\Routing;

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
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Utility\Arrays;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class BackendModuleRoutePartHandler extends DynamicRoutePart
{
    const MATCHRESULT_FOUND = true;
    const MATCHRESULT_NOSUCHMODULE = -1;
    const MATCHRESULT_NOCONTROLLER = -2;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Iterate through the segments of the current request path
     * find the corresponding module configuration and set controller & action
     * accordingly
     *
     * @param string $value
     * @return boolean|integer
     */
    protected function matchValue($value)
    {
        $format = pathinfo($value, PATHINFO_EXTENSION);
        if ($format !== '') {
            $value = substr($value, 0, strlen($value) - strlen($format) - 1);
        }
        $segments = Arrays::trimExplode('/', $value);

        $currentModuleBase = $this->settings['modules'];
        if ($segments === [] || !isset($currentModuleBase[$segments[0]])) {
            return self::MATCHRESULT_NOSUCHMODULE;
        }

        $modulePath = [];
        $level = 0;
        $moduleConfiguration = null;
        $moduleController = null;
        $moduleAction = 'index';
        foreach ($segments as $segment) {
            if (isset($currentModuleBase[$segment])) {
                $modulePath[] = $segment;
                $moduleConfiguration = $currentModuleBase[$segment];

                if (isset($moduleConfiguration['controller'])) {
                    $moduleController = $moduleConfiguration['controller'];
                } else {
                    $moduleController = null;
                }

                if (isset($moduleConfiguration['submodules'])) {
                    $currentModuleBase = $moduleConfiguration['submodules'];
                } else {
                    $currentModuleBase = [];
                }
            } else {
                if ($level === count($segments) - 1) {
                    $moduleMethods = array_change_key_case(
                        array_flip(get_class_methods($moduleController)),
                        CASE_LOWER
                    );
                    if (array_key_exists($segment . 'action', $moduleMethods)) {
                        $moduleAction = $segment;
                        break;
                    }
                }
                return self::MATCHRESULT_NOSUCHMODULE;
            }
            $level++;
        }

        if ($moduleController === null) {
            return self::MATCHRESULT_NOCONTROLLER;
        }

        $this->value = [
            'module' => implode('/', $modulePath),
            'controller' => $moduleController,
            'action' => $moduleAction
        ];

        if ($format !== '') {
            $this->value['format'] = $format;
        }

        return self::MATCHRESULT_FOUND;
    }

    /**
     * @param string $requestPath
     * @return string
     */
    protected function findValueToMatch($requestPath)
    {
        return $requestPath;
    }

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
            $this->value = $value['module'];
        } else {
            $this->value = $value;
        }
        return true;
    }
}
