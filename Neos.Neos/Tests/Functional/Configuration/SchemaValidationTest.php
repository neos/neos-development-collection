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
use Neos\Flow\Tests\Functional\Configuration\SchemaValidationTest as FlowSchemaValidationTest;

/**
 * Testcase for the Flow Validation Framework
 *
 */
class SchemaValidationTest extends FlowSchemaValidationTest
{

    /**
     * @var array<string>
     */
    protected $schemaPackageKeys = ['Neos.ContentRepository', 'Neos.Neos'];
}
