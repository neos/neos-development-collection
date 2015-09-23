<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Service\ConfigurationContentDimensionPresetSource;

class ConfigurationContentDimensionPresetSourceTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $validConfiguration = array(
        'language' => array(
            'defaultPreset' => 'all',
            'label' => 'Language',
            'icon' => 'icon-language',
            'position' => 100,
            'presets' => array(
                'all' => array(
                    'label' => 'All languages',
                    'values' => array('mul_ZZ'),
                    'uriSegment' => 'intl',
                    'position' => 100
                ),
                'de_DE' => array(
                    'label' => 'Deutsch (Deutschland)',
                    'values' => array('de_DE', 'de_ZZ', 'mul_ZZ'),
                    'uriSegment' => 'deutsch',
                    'position' => 10
                )
            )
        ),
        'targetGroups' => array(
            'defaultPreset' => 'all',
            'label' => 'Target Groups',
            'icon' => 'icon-group',
            'position' => 20,
            'presets' => array(
                'all' => array(
                    'label' => 'All target groups',
                    'values' => array('all'),
                    'uriSegment' => 'all',
                    'position' => 100
                )
            )
        )
    );

    /**
     * @test
     */
    public function findPresetByDimensionValuesWithExistingValuesReturnsPreset()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->findPresetByDimensionValues('language', array('de_DE', 'de_ZZ', 'mul_ZZ'));
        $this->assertArrayHasKey('uriSegment', $preset);
        $this->assertEquals('deutsch', $preset['uriSegment']);
    }


    /**
     * @test
     */
    public function getAllPresetsReturnsDimensionsOrderedByPosition()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $presets = $source->getAllPresets();
        $this->assertEquals(array('targetGroups', 'language'), array_keys($presets));
    }

    /**
     * @test
     */
    public function getAllPresetsReturnsDimensionPresetsOrderedByPosition()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $presets = $source->getAllPresets();
        $this->assertArrayHasKey('language', $presets);
        $this->assertEquals(array('de_DE', 'all'), array_keys($presets['language']['presets']));
    }

    /**
     * @test
     */
    public function getDefaultPresetWithExistingDimensionReturnsDefaultPresetWithIdentifier()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->getDefaultPreset('language');
        $this->assertArrayHasKey('identifier', $preset);
        $this->assertEquals('all', $preset['identifier']);
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception
     * @expectedExceptionCode 1401093863
     */
    public function setConfigurationThrowsExceptionIfSpecifiedDefaultPresetDoesNotExist()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $configuration = $this->validConfiguration;
        $configuration['language']['defaultPreset'] = 'something';
        $source->setConfiguration($configuration);
    }
}
