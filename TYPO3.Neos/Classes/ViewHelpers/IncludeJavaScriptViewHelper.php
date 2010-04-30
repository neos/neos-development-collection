<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\ViewHelpers;

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
 * A View Helper to include JavaScript files inside Resources/Public/JavaScript of the package.
 *
 * @version $Id: BackendController.php 3943 2010-03-15 14:56:54Z k-fish $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class IncludeJavaScriptViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {
	/**
	 * @inject
	 * @var \F3\FLOW3\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * Include all JavaScript files matching the include regular expression
	 * and not matching the exclude regular expression.
	 *
	 * @param string $include Regular expression of files to include
	 * @param string $exclude Regular expression of files to exclude
	 * @param string $package The package key of the resources to include or current controller package if NULL
	 * @param string $subpackage The subpackage key of the resources to include or current controller subpackage if NULL
	 */
	public function render($include, $exclude = NULL, $package = NULL, $subpackage = NULL) {
		$packageKey = $package === NULL ? $this->controllerContext->getRequest()->getControllerPackageKey() : $package;
		
		$subpackageKey = $subpackage === NULL ? $this->controllerContext->getRequest()->getControllerSubpackageKey() : $subpackage;
		$baseDirectory = 'package://' . $packageKey . '/Public/' . ($subpackageKey !== '' ? $subpackageKey . '/' : '') . 'JavaScript/';

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDirectory));

		$output = '';
		foreach ($iterator as $file) {
			$relativePath = substr($file->getPathname(), strlen($baseDirectory));

			if ($exclude !== NULL && preg_match('/^' . str_replace('/', '\/', $exclude) . '$/', $relativePath)) continue;

			if (preg_match('/^' . str_replace('/', '\/', $include) . '$/', $relativePath)) {
				$uri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $packageKey . '/' . ($subpackageKey !== '' ? $subpackageKey . '/' : '') . 'JavaScript/' . $relativePath;
				$output .= '<script type="text/javascript" src="' . $uri . '"></script>' . chr(10);
			}
		}
		return $output;
	}
}
?>