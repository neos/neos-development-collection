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

use TYPO3\Flow\I18n\EelHelper\TranslationHelper;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\ViewHelpers\TranslateViewHelper as FluidTranslateViewHelper;
use TYPO3\Fluid\Core\ViewHelper;
use TYPO3\Neos\Domain\Model\User;

/**
 * Returns translated message using source message or key ID.
 * uses the selected backend language
 * * Also replaces all placeholders with formatted versions of provided values.
 *
 * = Examples =
 *
 * {namespace neos=TYPO3\Neos\ViewHelpers}
 * <code title="Translation by id">
 * <neos:backend.translate id="user.unregistered">Unregistered User</neos:backend.translate>
 * </code>
 * <output>
 * translation of label with the id "user.unregistered" and a fallback to "Unregistered User"
 * </output>
 *
 * <code title="Inline notation">
 * {neos:backend.translate(id: 'some.label.id', value: 'fallback result')}
 * </code>
 * <output>
 * translation of label with the id "some.label.id" and a fallback to "fallback result"
 * </output>
 *
 * <code title="Custom source and locale">
 * <neos:backend.translate id="some.label.id" source="SomeLabelsCatalog" locale="de_DE"/>
 * </code>
 * <output>
 * translation from custom source "SomeLabelsCatalog" for locale "de_DE"
 * </output>
 *
 * <code title="Custom source from other package">
 * <neos:backend.translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>
 * </code>
 * <output>
 * translation from custom source "LabelsCatalog" in "OtherPackage"
 * </output>
 *
 * <code title="Arguments">
 * <neos:backend.translate arguments="{0: 'foo', 1: '99.9'}"><![CDATA[Untranslated {0} and {1,number}]]></neos:backend.translate>
 * </code>
 * <output>
 * translation of the label "Untranslated foo and 99.9"
 * </output>
 *
 * <code title="Translation by label">
 * <neos:backend.translate>Untranslated label</neos:backend.translate>
 * </code>
 * <output>
 * translation of the label "Untranslated label"
 * </output>
 */
class TranslateViewHelper extends FluidTranslateViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\InjectConfiguration("userInterface.defaultLanguage")
	 * @var string
	 */
	protected $defaultLanguageIdentifier;

	/**
	 * Renders the translated label.
	 *
	 * Replaces all placeholders with corresponding values if they exist in the
	 * translated label.
	 *
	 * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
	 * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
	 * @param array $arguments Numerically indexed array of values to be inserted into placeholders
	 * @param string $source Name of file with translations
	 * @param string $package Target package key. If not set, the current package key will be used
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param string $languageIdentifier An identifier of a language to use (NULL for using the default language)
	 * @return string Translated label or source label / ID key
	 * @throws ViewHelper\Exception
	 */
	public function render($id = NULL, $value = NULL, array $arguments = array(), $source = 'Main', $package = NULL, $quantity = NULL, $languageIdentifier = NULL) {
		if (preg_match(TranslationHelper::I18N_LABEL_ID_PATTERN, $id) === 1) {
			// In the longer run, this "extended ID" format should directly be resolved in the localization service
			list($package, $source, $id) = explode(':', $id, 3);
			$source = str_replace('.', '/', $source);
		}

		if ($languageIdentifier === NULL && $this->securityContext->canBeInitialized()) {
			if ($this->securityContext->getAccount()) {
				/** @var User $user */
				$user = $this->securityContext->getAccount()->getParty();
				$languageIdentifier = $user->getPreferences()->get('interfaceLanguage') ?: $this->defaultLanguageIdentifier;
			}
		}

		// Catch exception in case the translation file doesn't exist, should be fixed in Flow 3.1
		try {
			$translation = parent::render($id, $value, $arguments, $source, $package, $quantity, $languageIdentifier);
			// Fallback to english label if label was not available in specific language
			if ($translation === $id && $languageIdentifier !== 'en') {
				$translation = parent::render($id, $value, $arguments, $source, $package, $quantity, 'en');
			}
			return $translation;
		} catch(\TYPO3\Flow\I18n\Exception $exception) {
			return $value ?: $id;
		}
	}

}
