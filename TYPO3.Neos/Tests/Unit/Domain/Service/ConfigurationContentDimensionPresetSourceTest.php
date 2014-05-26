<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;

class ConfigurationContentDimensionPresetSourceTest extends UnitTestCase {

	/**
	 * @var array
	 */
	protected $validConfiguration = array(
		'languages' => array(
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
	public function getAllPresetsReturnsDimensionsOrderedByPosition() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$presets = $source->getAllPresets();
		$this->assertEquals(array('targetGroups', 'languages'), array_keys($presets));
	}

	/**
	 * @test
	 */
	public function getAllPresetsReturnsDimensionPresetsOrderedByPosition() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$presets = $source->getAllPresets();
		$this->assertArrayHasKey('languages', $presets);
		$this->assertEquals(array('de_DE', 'all'), array_keys($presets['languages']['presets']));
	}

	/**
	 * @test
	 */
	public function getDefaultPresetWithExistingDimensionReturnsDefaultPresetWithIdentifier() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$preset = $source->getDefaultPreset('languages');
		$this->assertArrayHasKey('identifier', $preset);
		$this->assertEquals('all', $preset['identifier']);
	}

	/**
	 * @test
	 */
	public function findPresetByUriSegmentWithExistingUriSegmentReturnsPreset() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$preset = $source->findPresetByUriSegment('languages', 'deutsch');
		$this->assertArrayHasKey('values', $preset);
		$this->assertEquals(array('de_DE', 'de_ZZ', 'mul_ZZ'), $preset['values']);
	}

	/**
	 * @test
	 */
	public function findPresetByUriSegmentWithoutExistingUriSegmentReturnsNull() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$preset = $source->findPresetByUriSegment('languages', 'english');
		$this->assertNull($preset);
	}

	/**
	 * @test
	 */
	public function findPresetByDimensionValuesWithExistingValuesReturnsPreset() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$preset = $source->findPresetByDimensionValues('languages', array('de_DE', 'de_ZZ', 'mul_ZZ'));
		$this->assertArrayHasKey('uriSegment', $preset);
		$this->assertEquals('deutsch', $preset['uriSegment']);
	}

	/**
	 * @test
	 */
	public function findPresetByDimensionValuesWithoutExistingUriSegmentReturnsNull() {
		$source = new ConfigurationContentDimensionPresetSource();
		$source->setConfiguration($this->validConfiguration);
		$preset = $source->findPresetByDimensionValues('languages', array('ja_JP', 'mul_ZZ'));
		$this->assertNull($preset);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Domain\Exception
	 * @expectedExceptionCode 1401093863
	 */
	public function setConfigurationThrowsExceptionIfSpecifiedDefaultPresetDoesNotExist() {
		$source = new ConfigurationContentDimensionPresetSource();
		$configuration = $this->validConfiguration;
		$configuration['languages']['defaultPreset'] = 'something';
		$source->setConfiguration($configuration);
	}

}
