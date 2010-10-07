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
	 * @param string $directory The directory inside the current subpackage. By default, the "JavaScript" directory will be used.
	 * @return string
	 * @author Christopher Hlubek
	 */
	public function render($include, $exclude = NULL, $package = NULL, $subpackage = NULL, $directory = 'JavaScript') {
		$packageKey = $package === NULL ? $this->controllerContext->getRequest()->getControllerPackageKey() : $package;
		$subpackageKey = $subpackage === NULL ? $this->controllerContext->getRequest()->getControllerSubpackageKey() : $subpackage;

		$baseDirectory = 'resource://' . $packageKey . '/Public/' . ($subpackageKey !== NULL ? $subpackageKey . '/' : '') . $directory . '/';
		$staticJavaScriptWebBaseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $packageKey . '/' . ($subpackageKey !== NULL ? $subpackageKey . '/' : '') . $directory . '/';

		$iterator = $this->iterateDirectoryRecursively($baseDirectory);
		if ($iterator === NULL) {
			return '<!-- Warning: Cannot include JavaScript because directory "' . $baseDirectory . '" does not exist. -->';
		}

		$uris = array();
		foreach ($iterator as $file) {
			$relativePath = substr($file->getPathname(), strlen($baseDirectory));
			$relativePath = \F3\FLOW3\Utility\Files::getUnixStylePath($relativePath);

			if (!$this->patternMatchesPath($exclude, $relativePath) &&
				$this->patternMatchesPath($include, $relativePath)) {
				$uris[] = $staticJavaScriptWebBaseUri . $relativePath;
			}
		}

			// Sadly, the aloha editor needs a predefined inclusion order, which right now matches
			// the sorted URI list. that's why we sort here...
		asort($uris);

		$output = '';
		foreach ($uris as $uri) {
			$output .= '<script src="' . $uri . '"></script>' . chr(10);
		}
		return $output;
	}

	/**
	 * Iterate over a directory with all subdirectories
	 *
	 * (Exists primarily to ease unit testing)
	 *
	 * @param string $directory The directory to iterate over
	 * @return \RecursiveIteratorIterator
	 * @author Christopher Hlubek
	 */
	protected function iterateDirectoryRecursively($directory) {
		if (!is_dir($directory)) {
			return NULL;
		} else {
			return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
		}
	}

	/**
	 * Test if the (partial) pattern matches the path. Slashes in the pattern
	 * will be escaped.
	 *
	 * @param string $pattern The partial regular expression pattern
	 * @param string $path The path to test
	 * @return boolean True if the pattern matches the path
	 * @author Christopher Hlubek
	 */
	protected function patternMatchesPath($pattern, $path) {
		return $pattern !== NULL && preg_match('/^' . str_replace('/', '\/', $pattern) . '$/', $path);
	}
}
?>