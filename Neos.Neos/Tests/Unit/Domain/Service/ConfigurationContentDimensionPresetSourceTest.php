<?php
namespace Neos\Neos\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;

class ConfigurationContentDimensionPresetSourceTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $validConfiguration = [
        'language' => [
            'defaultPreset' => 'all',
            'label' => 'Language',
            'icon' => 'icon-language',
            'position' => 100,
            'presets' => [
                'all' => [
                    'label' => 'All languages',
                    'values' => ['mul_ZZ'],
                    'uriSegment' => 'intl',
                    'position' => 100
                ],
                'de_DE' => [
                    'label' => 'Deutsch (Deutschland)',
                    'values' => ['de_DE', 'de_ZZ', 'mul_ZZ'],
                    'uriSegment' => 'deutsch',
                    'position' => 10
                ]
            ]
        ],
        'targetGroups' => [
            'defaultPreset' => 'all',
            'label' => 'Target Groups',
            'icon' => 'icon-group',
            'position' => 20,
            'presets' => [
                'all' => [
                    'label' => 'All target groups',
                    'values' => ['all'],
                    'uriSegment' => 'all',
                    'position' => 100
                ]
            ]
        ]
    ];

    /**
     * @test
     */
    public function findPresetByUriSegmentWithExistingUriSegmentReturnsPreset()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->findPresetByUriSegment('language', 'deutsch');
        self::assertArrayHasKey('values', $preset);
        self::assertEquals(['de_DE', 'de_ZZ', 'mul_ZZ'], $preset['values']);
    }

    /**
     * @test
     */
    public function findPresetByUriSegmentWithoutExistingUriSegmentReturnsNull()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->findPresetByUriSegment('language', 'english');
        self::assertNull($preset);
    }

    /**
     * @test
     */
    public function findPresetByDimensionValuesWithExistingValuesReturnsPreset()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->findPresetByDimensionValues('language', ['de_DE', 'de_ZZ', 'mul_ZZ']);
        self::assertArrayHasKey('uriSegment', $preset);
        self::assertEquals('deutsch', $preset['uriSegment']);
    }

    /**
     * @test
     */
    public function findPresetByDimensionValuesWithoutExistingUriSegmentReturnsNull()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->findPresetByDimensionValues('language', ['ja_JP', 'mul_ZZ']);
        self::assertNull($preset);
    }
}
