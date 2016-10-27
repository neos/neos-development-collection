<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Transliterator\Transliterator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Service as LocalizationService;

/**
 * @Flow\Scope("singleton")
 */
class TransliterationService
{
    /**
     * @Flow\Inject
     * @var LocalizationService
     */
    protected $localizationService;

    /**
     * @Flow\InjectConfiguration("transliterationRules")
     * @var boolean
     */
    protected $transliterationRules;

    /**
     * Translaterates UTF-8 string to ASCII. (北京 to 'Bei Jing')
     *
     * Accepts language parameter that maps to a configurable array of special transliteration rules if present.
     *
     * @param string $text Text to transliterate
     * @param string $language Optional language for specific rules (falls back to current locale if not provided)
     * @return string
     */
    public function transliterate($text, $language = null)
    {
        $language = $language ?: $this->localizationService->getConfiguration()->getCurrentLocale()->getLanguage();

        if (isset($this->transliterationRules[$language])) {
            // Apply special transliteration (not supported in library)
            $text = strtr($text, $this->transliterationRules[$language]);
        }

        // Transliterate (transform 北京 to 'Bei Jing')
        if (preg_match('/[\x80-\xff]/', $text) && Transliterator::validUtf8($text)) {
            $text = Transliterator::utf8ToAscii($text);
        }

        return $text;
    }
}
