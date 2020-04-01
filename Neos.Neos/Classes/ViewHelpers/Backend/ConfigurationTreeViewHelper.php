<?php
namespace Neos\Neos\ViewHelpers\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Render HTML markup for the full configuration tree in the Neos Administration -> Configuration Module.
 *
 * For performance reasons, this is done inside a ViewHelper instead of Fluid itself.
 */
class ConfigurationTreeViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var string
     */
    protected $output = '';

    /**
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('configuration', 'array', 'Configuration to show', true);
    }

    /**
     * Render the $configuration
     *
     * @return string
     * @throws \Exception
     */
    public function render(): string
    {
        $this->output = '';
        $this->renderSingleLevel($this->arguments['configuration']);
        return $this->output;
    }

    /**
     * Recursive function rendering configuration and adding it to $this->output
     *
     * @param array $configuration
     * @param string $relativePath the path up-to-now
     * @return void
     */
    protected function renderSingleLevel(array $configuration, ?string $relativePath = null): void
    {
        $this->output .= '<ul>';
        foreach ($configuration as $key => $value) {
            $path = ($relativePath ? $relativePath . '.' . $key : $key);
            $pathEscaped = htmlspecialchars($path);
            $keyEscaped = htmlspecialchars($key);

            $typeEscaped = htmlspecialchars(gettype($value));
            if ($typeEscaped === 'array') {
                $this->output .= sprintf('<li class="neos-tree-folder neos-tree-node" title="%s">', $pathEscaped);
                $this->output .= sprintf('%s&nbsp;(%s)', $keyEscaped, count($value));
                $this->renderSingleLevel($value, $path);
                $this->output .= '</li>';
            } else {
                $this->output .= '<li class="neos-tree-node">';
                $this->output .= sprintf('<div class="key" title="%s">%s:</div> ', $pathEscaped, $keyEscaped);
                $this->output .= sprintf('<div class="value" title="%s">', $typeEscaped);
                switch ($typeEscaped) {
                            case 'boolean':
                                $this->output .= ($value ? 'true' : 'false');
                                break;
                            case 'NULL':
                                $this->output .= 'NULL';
                                break;
                            default:
                                $this->output .= htmlspecialchars($value);
                        }
                $this->output .= '</div>';
                $this->output .= '</li>';
            }
        }
        $this->output .= '</ul>';
    }
}
