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

use TYPO3\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;

class ConfigurationContentDimensionPresetSourceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var ConfigurationContentDimensionPresetSource
	 */
	protected $source;

	public function setUp() {
		$this->source = new ConfigurationContentDimensionPresetSource();
		$this->source->setConfiguration(array(
			'locales' => array(
				'defaultPreset' => 'all',
				'label' => 'Locale',
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
				)
			)
		));
	}

	/**
	 * @test
	 */
	public function getAllPresetsReturnsDimensionsOrderedByPosition() {
		$presets = $this->source->getAllPresets();
		$this->assertEquals(array('targetGroups', 'locales'), array_keys($presets));
	}

	/**
	 * @test
	 */
	public function getAllPresetsReturnsDimensionPresetsOrderedByPosition() {
		$presets = $this->source->getAllPresets();
		$this->assertArrayHasKey('locales', $presets);
		$this->assertEquals(array('de_DE', 'all'), array_keys($presets['locales']['presets']));
	}

	/**
	 * @test
	 */
	public function getDefaultPresetWithExistingDimensionReturnsDefaultPresetWithIdentifier() {
		$preset = $this->source->getDefaultPreset('locales');
		$this->assertArrayHasKey('identifier', $preset);
		$this->assertEquals('all', $preset['identifier']);
	}

	/**
	 * @test
	 */
	public function findPresetByUriSegmentWithExistingUriSegmentReturnsPreset() {
		$preset = $this->source->findPresetByUriSegment('locales', 'deutsch');
		$this->assertArrayHasKey('values', $preset);
		$this->assertEquals(array('de_DE', 'de_ZZ', 'mul_ZZ'), $preset['values']);
	}

	/**
	 * @test
	 */
	public function findPresetByUriSegmentWithoutExistingUriSegmentReturnsNull() {
		$preset = $this->source->findPresetByUriSegment('locales', 'english');
		$this->assertNull($preset);
	}

	/**
	 * @test
	 */
	public function findPresetByDimensionValuesWithExistingValuesReturnsPreset() {
		$preset = $this->source->findPresetByDimensionValues('locales', array('de_DE', 'de_ZZ', 'mul_ZZ'));
		$this->assertArrayHasKey('uriSegment', $preset);
		$this->assertEquals('deutsch', $preset['uriSegment']);
	}

	/**
	 * @test
	 */
	public function findPresetByDimensionValuesWithoutExistingUriSegmentReturnsNull() {
		$preset = $this->source->findPresetByDimensionValues('locales', array('ja_JP', 'mul_ZZ'));
		$this->assertNull($preset);
	}
}
