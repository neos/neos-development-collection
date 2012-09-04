<?php
namespace TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Interface for TypoScript Processors which are aware of the TypoScript runtime.
 *
 * Such a processor should be only applied to complete TypoScript objects; and
 * not to single properties. The processor is called before AND after the TypoScript
 * object is invoked; making it possible to manipulate the context variables.
 *
 * Before the TypoScript object is evaluated, the "beforeInvocation()" method is called.
 * Then, after evaluating the TS object, the "process" method is called. At the end,
 * "afterInvocation()" is called.
 *
 * NOTE: It currently might be possible that *multiple processor objects* are created
 * for rendering a single TypoScript function; so you cannot store any properties inside
 * the Processor so far.
 */
interface RuntimeAwareProcessorInterface extends ProcessorInterface {

	/**
	 * Called before the $typoScriptObject is evaluated.
	 *
	 * @param \TYPO3\TypoScript\Core\Runtime $runtime
	 * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTsObject $typoScriptObject
	 * @param string $typoScriptPath
	 */
	public function beforeInvocation(Core\Runtime $runtime, TypoScriptObjects\AbstractTsObject $typoScriptObject, $typoScriptPath);

	/**
	 * Called after the $typoScriptObject is evaluated and process has been called.
	 *
	 * @param \TYPO3\TypoScript\Core\Runtime $runtime
	 * @param \TYPO3\TypoScript\TypoScriptObjects\AbstractTsObject $typoScriptObject
	 * @param string $typoScriptPath
	 */
	public function afterInvocation(Core\Runtime $runtime, TypoScriptObjects\AbstractTsObject $typoScriptObject, $typoScriptPath);

}
?>