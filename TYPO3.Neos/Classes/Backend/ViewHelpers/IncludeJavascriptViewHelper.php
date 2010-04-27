<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Backend\ViewHelpers;

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
 * A View Helper to include javascript files inside Resources/Public/JavaScript of the package.
 *
 * @version $Id: BackendController.php 3943 2010-03-15 14:56:54Z k-fish $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class IncludeJavascriptViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {
	/**
	 * @inject
	 * @var \F3\FLOW3\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 *
	 * @param string $include Regular expression of files to include
	 * @param string $exclude Regular expression of files to exclude
	 */
	public function render($include, $exclude = NULL) {
		$baseDirectory = 'package://TYPO3/Public/Backend/JavaScript/';
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDirectory));

		$output = '';
		foreach ($iterator as $file) {
			$relativePath = substr($file->getPathname(), strlen($baseDirectory));

			if ($exclude !== NULL && preg_match('/^' . str_replace('/', '\/', $exclude) . '$/', $relativePath)) continue;

			if (preg_match('/^' . str_replace('/', '\/', $include) . '$/', $relativePath)) {
				$uri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/TYPO3/Backend/JavaScript/' . $relativePath;
				$output .= '<script type="text/javascript" src="' . $uri . '"></script>' . chr(10);
			}
		}
		return $output;
	}
}
?>