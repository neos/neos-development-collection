<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Model\Site;

/**
 * Testcase for the "Site" domain model
 *
 */
class SiteTest extends UnitTestCase
{
    /**
     * @test
     */
    public function aNameCanBeSetAndRetrievedFromTheSite()
    {
        $site = new Site('');
        $site->setName('My cool website');
        $this->assertSame('My cool website', $site->getName());
    }

    /**
     * @test
     */
    public function theDefaultStateOfASiteIsOffline()
    {
        $site = new Site('');
        $this->assertSame(Site::STATE_OFFLINE, $site->getState());
    }

    /**
     * @test
     */
    public function theStateCanBeSetAndRetrieved()
    {
        $site = new Site('');
        $site->setState(Site::STATE_ONLINE);
        $this->assertSame(Site::STATE_ONLINE, $site->getState());
    }

    /**
     * @test
     */
    public function theSiteResourcesPackageKeyCanBeSetAndRetrieved()
    {
        $site = new Site('');
        $site->setSiteResourcesPackageKey('Foo');
        $this->assertSame('Foo', $site->getSiteResourcesPackageKey());
    }
}
