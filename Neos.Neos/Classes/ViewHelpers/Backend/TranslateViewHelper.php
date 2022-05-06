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

use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\ViewHelpers\TranslateViewHelper as FluidTranslateViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper;

/**
 * Returns translated message using source message or key ID.
 * uses the selected backend language
 * * Also replaces all placeholders with formatted versions of provided values.
 *
 * = Examples =
 *
 * {namespace neos=Neos\Neos\ViewHelpers}
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
 * <neos:backend.translate arguments="{0: 'foo', 1: '99.9'}">
 *      <![CDATA[Untranslated {0} and {1,number}]]>
 * </neos:backend.translate>
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
     * @var \Neos\Neos\Service\UserService
     */
    protected $userService;

    /**
     * Renders the translated label.
     *
     * Replaces all placeholders with corresponding values if they exist in the
     * translated label.
     *
     * ViewHelper arguments: @return string Translated label or source label / ID key
     * @throws ViewHelper\Exception
     * @see FluidTranslateViewHelper::initializeArguments
     */
    public function render()
    {
        $id = $this->arguments['id'];
        $value = $this->arguments['value'];
        $locale = $this->arguments['locale'];

        if (preg_match(TranslationHelper::I18N_LABEL_ID_PATTERN, $id) === 1) {
            // In the longer run, this "extended ID" format should directly be resolved in the localization service
            list($package, $source, $id) = explode(':', $id, 3);
            $this->arguments['id'] = $id;
            $this->arguments['package'] = $package;
            $this->arguments['source'] = str_replace('.', '/', $source);
        }

        if ($locale === null) {
            $this->arguments['locale'] = $this->userService->getInterfaceLanguage();
        }

        $translation = parent::render();
        // Fallback to english label if label was not available in specific language
        if ($translation === $id && $locale !== 'en') {
            $this->arguments['locale'] = 'en';
            $translation = parent::render();
        }
        return $translation;
    }
}
