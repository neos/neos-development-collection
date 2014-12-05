<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Render HTML markup for the full configuration tree in the Neos Administration -> Configuration Module.
 *
 * For performance reasons, this is done inside a ViewHelper instead of Fluid itself.
 */
class ConfigurationTreeViewHelper extends AbstractViewHelper {

	/**
	 * @var string
	 */
	protected $output = '';

	/**
	 * Render the given $configuration
	 *
	 * @param array $configuration
	 * @return string
	 * @throws \Exception
	 */
	public function render(array $configuration) {
		$this->output = '';
		$this->renderSingleLevel($configuration);
		return $this->output;
	}

	/**
	 * Recursive function rendering configuration and adding it to $this->output
	 *
	 * @param array $configuration
	 * @param string $relativePath the path up-to-now
	 * @return void
	 */
	protected function renderSingleLevel(array $configuration, $relativePath = NULL) {
		$this->output .= '<ul>';
		foreach ($configuration as $key => $value) {
			$path = ($relativePath ? $relativePath . '.' . $key : $key);
			$pathEscaped = htmlspecialchars($path);
			$keyEscaped = htmlspecialchars($key);

			$typeEscaped = htmlspecialchars(gettype($value));
			if ($typeEscaped === 'array') {
				$this->output .= sprintf('<li class="folder" title="%s">', $pathEscaped);
					$this->output .= sprintf('%s&nbsp;(%s)', $keyEscaped, count($value));
					$this->renderSingleLevel($value, $path);
				$this->output .= '</li>';
			} else {
				$this->output .= '<li>';
					$this->output .= sprintf('<div class="key" title="%s">%s:</div> ', $pathEscaped, $keyEscaped);
					$this->output .= sprintf('<div class="value" title="%s">', $typeEscaped);
						switch ($typeEscaped) {
							case 'boolean':
								$this->output .= ($value ? 'TRUE' : 'FALSE');
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