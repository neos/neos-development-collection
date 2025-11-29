<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\Domain\Link;

use GuzzleHttp\Psr7\Uri;
use Neos\Neos\Domain\Link\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @test
     */
    public function emptyLink()
    {
        $link = Link::create(
            href: new Uri('#')
        );
        self::assertEquals('', (string)$link->href);
        self::assertEquals(null, $link->title);
        self::assertEquals(null, $link->target);
        self::assertEquals([], $link->rel);
        self::assertEquals(false, $link->download);

        // another way to create it
        self::assertEquals(
            $link,
            Link::fromString('#')
        );
    }

    /**
     * @test
     */
    public function linkWithAttributes()
    {
        $link = Link::create(
            href: new Uri('/bar'),
            title: 'My link',
            target: Link::TARGET_BLANK,
            rel: [Link::REL_NOFOLLOW],
            download: true
        );

        self::assertEquals('/bar', (string)$link->href);
        self::assertEquals('My link', $link->title);
        self::assertEquals('_blank', $link->target);
        self::assertEquals(['nofollow'], $link->rel);
        self::assertEquals(true, $link->download);
    }

    /**
     * @test
     */
    public function linkWithAttributesViaWither()
    {
        $emptyLink = Link::create(
            href: new Uri('#')
        );

        $link = $emptyLink->with(
            href: new Uri('/bar'),
            title: 'My link',
            target: Link::TARGET_BLANK,
            rel: [Link::REL_NOFOLLOW],
            download: true
        );

        self::assertEquals('/bar', (string)$link->href);
        self::assertEquals('My link', $link->title);
        self::assertEquals('_blank', $link->target);
        self::assertEquals(['nofollow'], $link->rel);
        self::assertEquals(true, $link->download);
    }

    /**
     * @test
     */
    public function linkWithAttributesOneUpdated()
    {
        $link = Link::create(
            href: new Uri('/bar'),
            title: 'My link',
            target: Link::TARGET_BLANK,
            rel: [Link::REL_NOFOLLOW],
            download: true
        )->with(
            title: 'My updated link'
        );

        self::assertEquals('/bar', (string)$link->href);
        self::assertEquals('My updated link', $link->title);
        self::assertEquals('_blank', $link->target);
        self::assertEquals(['nofollow'], $link->rel);
        self::assertEquals(true, $link->download);
    }

    /**
     * @test
     */
    public function linkWithAttributesCanBeResetViaWith()
    {
        $link = Link::create(
            href: new Uri('/bar'),
            title: 'My link',
            target: Link::TARGET_BLANK,
            rel: [Link::REL_NOFOLLOW],
            download: true
        )->with(
            title: '',
            target: '',
            rel: [],
            download: false
        );

        self::assertEquals('/bar', (string)$link->href);
        self::assertEquals(null, $link->title);
        self::assertEquals(null, $link->target);
        self::assertEquals([], $link->rel);
        self::assertEquals(false, $link->download);
    }
}
