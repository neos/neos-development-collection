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

class SimpleTemplateRenderer
{
    /**
     * Renders simple templates by replacing {key} variables in the template, by the value of the key in the
     * contextVariables array
     *
     * @param string $templatePathAndFilename
     * @param array<string, string> $contextVariables
     * @return string
     */
    public function render(string $templatePathAndFilename, array $contextVariables) : string
    {
        $content = file_get_contents($templatePathAndFilename);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Could not read template file "%s".', $templatePathAndFilename));
        }
        foreach ($contextVariables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }
}
