<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\ViewHelpers\Aloha;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
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
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class AlohaConfigurationViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \F3\TYPO3CR\Domain\Repository\ContentTypeRepository
	 * @inject
	 */
	protected $contentTypeRepository;

	/**
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		$this->configuration = array();
		$this->buildListOfContentTypes();

		return json_encode($this->configuration);
	}

	/**
	 * @return void
	 */
	protected function buildListOfContentTypes() {
		$contentTypes = $this->contentTypeRepository->findAll();

		$contentTypesNotBeingOfTypeFolder = array();
		foreach ($contentTypes as $contentType) {
			if (!$contentType->isOfType('TYPO3CR:Folder')) {
				$contentTypesNotBeingOfTypeFolder[] = $contentType;
			}
		}

		$contentTypeConfiguration = array();
		foreach ($contentTypesNotBeingOfTypeFolder as $contentType) {
			$contentTypeConfiguration[] = array(
				'name' => $contentType->getName(),
				'labelKey' => strtr($contentType->getName(), ':', '_')
			);
		}
		$this->configuration['contentTypes'] = $contentTypeConfiguration;
	}

}
?>