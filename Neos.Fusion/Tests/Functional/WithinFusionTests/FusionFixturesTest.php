<?php

namespace Neos\Fusion\Tests\Functional\WithinFusionTests;

use Neos\Fusion\Testing\Tests\FusionTestCase;

class FusionFixturesTest extends FusionTestCase
{
    public static function getFixturesRootFusion(): string
    {
        return __DIR__ . '/FusionFixtures/Root.fusion';
    }
}
