<?php

namespace Neos\SiteKickstarter\Service;

class FusionRecursiveDirectoryRenderer
{
    /**
     * Renders whole directory recursivly instead of one file
     *
     * @param $srcDirectory
     * @param $targetDirectory
     * @param $variables
     */
    public function renderDirectory($srcDirectory, $targetDirectory, $variables)
    {
        $files = scandir($srcDirectory);

        foreach ($files as $key => $value) {
            $path = realpath($srcDirectory . DIRECTORY_SEPARATOR . $value);

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $value;

            if (!is_dir($path)) {
                $compiledFile = $this->renderSimpleTemplate($path, $variables);
                if (!is_dir(dirname($targetPath))) {
                    \Neos\Utility\Files::createDirectoryRecursively(dirname($targetPath));
                }
                file_put_contents($targetPath, $compiledFile);
            } else if ($value != "." && $value != "..") {
                $this->renderDirectory($path, $targetPath, $variables);
            }
        }
    }

    /**
     * Simplified template rendering
     *
     * @param string $templatePathAndFilename
     * @param array $contextVariables
     * @return string
     */
    protected function renderSimpleTemplate($templatePathAndFilename, array $contextVariables)
    {
        $content = file_get_contents($templatePathAndFilename);
        foreach ($contextVariables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

}
