<?php
declare(strict_types=1);

namespace Neos\SiteKickstarter\Service;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

class FusionRecursiveDirectoryRenderer
{
    /**
     * @Flow\Inject
     * @var SimpleTemplateRenderer
     */
    protected $simpleTemplateRenderer;

    /**
     * Renders whole directory recursivly
     *
     * @param string $srcDirectory
     * @param string $targetDirectory
     * @param array $variables
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function renderDirectory(string $srcDirectory, string $targetDirectory, array $variables)
    {
        $files = scandir($srcDirectory);

        foreach ($files as $key => $value) {
            $path = realpath($srcDirectory . DIRECTORY_SEPARATOR . $value);

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $value;

            if (!is_dir($path)) {
                $compiledFile = $this->simpleTemplateRenderer->render($path, $variables);
                if (!is_dir(dirname($targetPath))) {
                    \Neos\Utility\Files::createDirectoryRecursively(dirname($targetPath));
                }
                file_put_contents($targetPath, $compiledFile);
            } elseif ($value != "." && $value != "..") {
                $this->renderDirectory($path, $targetPath, $variables);
            }
        }
    }
}
