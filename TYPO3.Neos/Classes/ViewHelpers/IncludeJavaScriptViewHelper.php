<?php
namespace TYPO3\Neos\ViewHelpers;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A View Helper to include JavaScript files inside Resources/Public/JavaScript of the package.
 *
 * @Flow\Scope("prototype")
 * @deprecated This ViewHelper is deprecated with no replacement as of version 1.3 and will be removed in 3 versions from now.
 */
class IncludeJavaScriptViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
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
     */
    public function render($include, $exclude = null, $package = null, $subpackage = null, $directory = 'JavaScript')
    {
        $packageKey = $package === null ? $this->controllerContext->getRequest()->getControllerPackageKey() : $package;
        $subpackageKey = $subpackage === null ? $this->controllerContext->getRequest()->getControllerSubpackageKey() : $subpackage;

        $baseDirectory = 'resource://' . $packageKey . '/Public/' . ($subpackageKey !== null ? $subpackageKey . '/' : '') . $directory . '/';
        $staticJavaScriptWebBaseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $packageKey . '/' . ($subpackageKey !== null ? $subpackageKey . '/' : '') . $directory . '/';

        $iterator = $this->iterateDirectoryRecursively($baseDirectory);
        if ($iterator === null) {
            return '<!-- Warning: Cannot include JavaScript because directory "' . $baseDirectory . '" does not exist. -->';
        }

        $uris = array();
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($baseDirectory));
            $relativePath = \TYPO3\Flow\Utility\Files::getUnixStylePath($relativePath);

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
     */
    protected function iterateDirectoryRecursively($directory)
    {
        if (!is_dir($directory)) {
            return null;
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
     */
    protected function patternMatchesPath($pattern, $path)
    {
        return $pattern !== null && preg_match('/^' . str_replace('/', '\/', $pattern) . '$/', $path);
    }
}
