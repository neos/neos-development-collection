<?php
namespace Neos\Neos\Tests\Functional\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Neos\Tests\Functional\AbstractNodeTest;

/**
 * Make sure legacy sites.xml structures (1.0 or 1.1) can be imported
 */
class LegacySiteImportServiceTest extends AbstractNodeTest
{
    protected $nodeContextPath = null;

    protected $fixtureFileName = 'Domain/Service/Fixtures/LegacySite.xml';

    /**
     * @test
     */
    public function legacySiteImportYieldsExpectedResult()
    {
        $this->assertSame('<h1>Planned for change.</h1>', $this->getNodeWithContextPath('/sites/neosdemotypo3org/teaser/node52697bdfee199')->getProperty('title'));
    }
}
