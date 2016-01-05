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

/**
 * Testcase for the "Site" domain model
 *
 */
class SiteTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function aNameCanBeSetAndRetrievedFromTheSite()
    {
        $site = new \TYPO3\Neos\Domain\Model\Site('');
        $site->setName('My cool website');
        $this->assertSame('My cool website', $site->getName());
    }

    /**
     * @test
     */
    public function theDefaultStateOfASiteIsOffline()
    {
        $site = new \TYPO3\Neos\Domain\Model\Site('');
        $this->assertSame(\TYPO3\Neos\Domain\Model\Site::STATE_OFFLINE, $site->getState());
    }

    /**
     * @test
     */
    public function theStateCanBeSetAndRetrieved()
    {
        $site = new \TYPO3\Neos\Domain\Model\Site('');
        $site->setState(\TYPO3\Neos\Domain\Model\Site::STATE_ONLINE);
        $this->assertSame(\TYPO3\Neos\Domain\Model\Site::STATE_ONLINE, $site->getState());
    }

    /**
     * @test
     */
    public function theSiteResourcesPackageKeyCanBeSetAndRetrieved()
    {
        $site = new \TYPO3\Neos\Domain\Model\Site('');
        $site->setSiteResourcesPackageKey('Foo');
        $this->assertSame('Foo', $site->getSiteResourcesPackageKey());
    }
}
