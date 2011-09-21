<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "ContenTypeManager"
 *
 */
class ContentTypeManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	protected $settingsFixture = array(
		'contentTypes' => array(
			'TYPO3.TYPO3:ContentObject' => array(
				'label' => 'Abstract content object',
				'properties' => array(
					'_hidden' => array(
						'type' => 'boolean',
						'label' => 'Hidden',
						'category' => 'visibility',
						'priority' => 1
					),
				),
				'propertyGroups' => array(
					'visibility' => array(
						'label' => 'Visibility',
						'priority' => 1
					)
				)
			),
			'TYPO3.TYPO3:Text' => array(
				'superTypes' => array('TYPO3.TYPO3:ContentObject'),
				'label' => 'Text',
				'properties' => array(
					'headline' => array(
						'type' => 'string',
						'placeholder' => 'Enter headline here'
					),
					'text' => array(
						'type' => 'string',
						'placeholder' => '<p>Enter text here</p>'
					)
				),
				'inlineEditableProperties' => array('headline', 'text')
			),
			'TYPO3.TYPO3:TextWithImage' => array(
				'superTypes' => array('TYPO3.TYPO3:Text'),
				'label' => 'Text with image',
				'properties' => array(
					'image' => array(
						'type' => 'TYPO3\TYPO3\Domain\Model\Media\Image',
						'label' => 'Image'
					)
				)
			)
		)
	);

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function contentTypeConfigurationIsMergedTogether() {
		$contentTypeManager = new \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager();
		$contentTypeManager->injectSettings($this->settingsFixture);

		$contentType = $contentTypeManager->getContentType('TYPO3.TYPO3:Text');
		$this->assertSame('Text', $contentType->getLabel());

		$expectedProperties = array(
			'_hidden' => array(
				'type' => 'boolean',
				'label' => 'Hidden',
				'category' => 'visibility',
				'priority' => 1
			),
			'headline' => array(
				'type' => 'string',
				'placeholder' => 'Enter headline here'
			),
			'text' => array(
				'type' => 'string',
				'placeholder' => '<p>Enter text here</p>'
			)
		);
		$this->assertSame($expectedProperties, $contentType->getProperties());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\ContentTypeNotFoundException
	 */
	public function getContentTypeThrowsExceptionForUnknownContentType() {
		$contentTypeManager = new \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager();
		$contentTypeManager->injectSettings($this->settingsFixture);

		$contentTypeManager->getContentType('TYPO3.TYPO3:TextFooBarNotHere');
	}
}
?>