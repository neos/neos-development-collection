<?php
namespace Neos\Neos\Tests\Functional\Configuration;

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
use Neos\Flow\Tests\Functional\Configuration\ConfigurationValidationTest as FlowConfigurationValidationTest;

/**
 * Testcase for Configuration Validation
 */
class ConfigurationValidationTest extends FlowConfigurationValidationTest
{

    /**
     * The application-contexts that are validated
     *
     * @var array<string>
     */
    protected $contextNames = ['Development', 'Production', 'Testing'];

    /**
     * The configuration-types that are validated
     *
     * @var array<string>
     */
    protected $configurationTypes = ['Caches', 'Objects', 'Policy', 'Routes', 'Settings', 'NodeTypes'];

    /**
     * The packages that are searched for schemas
     *
     * @var array<string>
     */
    protected $schemaPackageKeys = ['Neos.Flow', 'Neos.Neos', 'Neos.ContentRepository'];

    /**
     * The packages that contain the configuration that is validated
     *
     * @var array<string>
     */
    protected $configurationPackageKeys = [
        'Neos.Flow', 'Neos.FluidAdaptor', 'Neos.Eel', 'Neos.Kickstart',
        'Neos.ContentRepository', 'Neos.Neos', 'Neos.Fusion', 'Neos.Media',
        'Neos.Media.Browser', 'Neos.NodeTypes'
    ];
}
