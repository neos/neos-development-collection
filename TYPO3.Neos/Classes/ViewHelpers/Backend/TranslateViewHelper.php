<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\I18n\EelHelper\TranslationHelper;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Exception;
use TYPO3\Fluid\ViewHelpers\TranslateViewHelper as FluidTranslateViewHelper;
use TYPO3\Fluid\Core\ViewHelper;

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
class TranslateViewHelper extends FluidTranslateViewHelper
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\UserService
     */
    protected $userService;

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
    public function render($id = null, $value = null, array $arguments = array(), $source = 'Main', $package = null, $quantity = null, $languageIdentifier = null)
    {
        if (preg_match(TranslationHelper::I18N_LABEL_ID_PATTERN, $id) === 1) {
            // In the longer run, this "extended ID" format should directly be resolved in the localization service
            list($package, $source, $id) = explode(':', $id, 3);
            $source = str_replace('.', '/', $source);
        }

        if ($languageIdentifier === null) {
            $languageIdentifier = $this->userService->getInterfaceLanguage();
        }

        // Catch exception in case the translation file doesn't exist, should be fixed in Flow 3.1
        try {
            $translation = parent::render($id, $value, $arguments, $source, $package, $quantity, $languageIdentifier);
            // Fallback to english label if label was not available in specific language
            if ($translation === $id && $languageIdentifier !== 'en') {
                $translation = parent::render($id, $value, $arguments, $source, $package, $quantity, 'en');
            }
            return $translation;
        } catch (Exception $exception) {
            return $value ?: $id;
        }
    }
}
