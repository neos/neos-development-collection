<?php
namespace TYPO3\TYPO3\ViewHelpers\Aloha;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Renders a wrapper around the inner contents of the tag to enable frontend editing.
 * The wrapper contains the property name which should be made editable, and is either a "span" or a "div" tag (depending on the context)
 *
 * @Flow\Scope("prototype")
 * @deprecated since sprint 10, use ContentElement/NotEditableViewHelper instead
 */
class NotEditableViewHelper extends \TYPO3\TYPO3\ViewHelpers\ContentElement\NotEditableViewHelper {

}
?>