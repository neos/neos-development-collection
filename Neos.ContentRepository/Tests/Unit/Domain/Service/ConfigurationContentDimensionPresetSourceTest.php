<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\ConfigurationContentDimensionPresetSource;

class ConfigurationContentDimensionPresetSourceTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $validConfiguration = [
        'language' => [
            'defaultPreset' => 'all',
            'label' => 'Language',
            'icon' => 'icon-language',
            'position' => 100,
            'presets' => [
                'all' => [
                    'label' => 'All languages',
                    'values' => ['mul_ZZ'],
                    'uriSegment' => 'intl',
                    'position' => 100
                ],
                'de_DE' => [
                    'label' => 'Deutsch (Deutschland)',
                    'values' => ['de_DE', 'de_ZZ', 'mul_ZZ'],
                    'uriSegment' => 'deutsch',
                    'position' => 10
                ]
            ]
        ],
        'targetGroups' => [
            'defaultPreset' => 'all',
            'label' => 'Target Groups',
            'icon' => 'icon-group',
            'position' => 20,
            'presets' => [
                'all' => [
                    'label' => 'All target groups',
                    'values' => ['all'],
                    'uriSegment' => 'all',
                    'position' => 100
                ]
            ]
        ]
    ];

    /**
     * @var array
     */
    protected $configurationWithThreeDimensionsAndManyValues = [
        'language' => [
            'defaultPreset' => 'en',
            'label' => 'Language',
            'icon' => 'icon-language',
            'presets' => []
        ],
        'country' => [
            'defaultPreset' => 'US',
            'label' => 'Country',
            'icon' => 'icon-globe',
            'presets' => []
        ],
        'persona' => [
            'defaultPreset' => 'happy',
            'label' => 'Persona',
            'icon' => 'icon-person',
            'presets' => []
        ]
    ];

    /**
     * @var array
     */
    protected $allLanguages = ['ab' => 'Abkhaz ', 'aa' => 'Afar', 'af' => 'Afrikaans', 'ak' => 'Akan', 'sq' => 'Albanian', 'am' => 'Amharic', 'ar' => 'Arabic', 'an' => 'Aragonese', 'hy' => 'Armenian', 'as' => 'Assamese', 'av' => 'Avaric', 'ae' => 'Avestan', 'ay' => 'Aymara', 'az' => 'Azerbaijani', 'bm' => 'Bambara', 'ba' => 'Bashkir', 'eu' => 'Basque', 'be' => 'Belarusian', 'bn' => 'Bengali, Bangla', 'bh' => 'Bihari', 'bi' => 'Bislama', 'bs' => 'Bosnian', 'br' => 'Breton', 'bg' => 'Bulgarian', 'my' => 'Burmese', 'ca' => 'Catalan', 'ch' => 'Chamorro', 'ce' => 'Chechen', 'ny' => 'Chichewa, Chewa, Nyanja', 'zh' => 'Chinese', 'cv' => 'Chuvash', 'kw' => 'Cornish', 'co' => 'Corsican', 'cr' => 'Cree', 'hr' => 'Croatian', 'cs' => 'Czech', 'da' => 'Danish', 'dv' => 'Divehi, Dhivehi, Maldivian', 'nl' => 'Dutch', 'dz' => 'Dzongkha', 'en' => 'English', 'eo' => 'Esperanto', 'et' => 'Estonian', 'ee' => 'Ewe', 'fo' => 'Faroese', 'fj' => 'Fijian', 'fi' => 'Finnish', 'fr' => 'French', 'ff' => 'Fula, Fulah, Pulaar, Pular', 'gl' => 'Galician', 'ka' => 'Georgian', 'de' => 'German', 'el' => 'Greek (modern)', 'gn' => 'Guaraní', 'gu' => 'Gujarati', 'ht' => 'Haitian, Haitian Creole', 'ha' => 'Hausa', 'he' => 'Hebrew (modern)', 'hz' => 'Herero', 'hi' => 'Hindi', 'ho' => 'Hiri Motu', 'hu' => 'Hungarian', 'ia' => 'Interlingua', 'id' => 'Indonesian', 'ie' => 'Interlingue', 'ga' => 'Irish', 'ig' => 'Igbo', 'ik' => 'Inupiaq', 'io' => 'Ido', 'is' => 'Icelandic', 'it' => 'Italian', 'iu' => 'Inuktitut', 'ja' => 'Japanese', 'jv' => 'Javanese', 'kl' => 'Kalaallisut, Greenlandic', 'kn' => 'Kannada', 'kr' => 'Kanuri', 'ks' => 'Kashmiri', 'kk' => 'Kazakh', 'km' => 'Khmer', 'ki' => 'Kikuyu, Gikuyu', 'rw' => 'Kinyarwanda', 'ky' => 'Kyrgyz', 'kv' => 'Komi', 'kg' => 'Kongo', 'ko' => 'Korean', 'ku' => 'Kurdish', 'kj' => 'Kwanyama, Kuanyama', 'la' => 'Latin', 'lb' => 'Luxembourgish, Letzeburgesch', 'lg' => 'Ganda', 'li' => 'Limburgish, Limburgan, Limburger', 'ln' => 'Lingala', 'lo' => 'Lao', 'lt' => 'Lithuanian', 'lu' => 'Luba-Katanga', 'lv' => 'Latvian', 'gv' => 'Manx', 'mk' => 'Macedonian', 'mg' => 'Malagasy', 'ms' => 'Malay', 'ml' => 'Malayalam', 'mt' => 'Maltese', 'mi' => 'Māori', 'mr' => 'Marathi (Marāṭhī)', 'mh' => 'Marshallese', 'mn' => 'Mongolian', 'na' => 'Nauru', 'nv' => 'Navajo, Navaho', 'nd' => 'Northern Ndebele', 'ne' => 'Nepali', 'ng' => 'Ndonga', 'nb' => 'Norwegian Bokmål', 'nn' => 'Norwegian Nynorsk', 'no' => 'Norwegian', 'ii' => 'Nuosu', 'nr' => 'Southern Ndebele', 'oc' => 'Occitan', 'oj' => 'Ojibwe, Ojibwa', 'cu' => 'Old Church Slavonic, Church Slavonic, Old Bulgarian', 'om' => 'Oromo', 'or' => 'Oriya', 'os' => 'Ossetian, Ossetic', 'pa' => 'Panjabi, Punjabi', 'pi' => 'Pāli', 'fa' => 'Persian (Farsi)', 'pl' => 'Polish', 'ps' => 'Pashto, Pushto', 'pt' => 'Portuguese', 'qu' => 'Quechua', 'rm' => 'Romansh', 'rn' => 'Kirundi', 'ro' => 'Romanian', 'ru' => 'Russian', 'sa' => 'Sanskrit (Saṁskṛta)', 'sc' => 'Sardinian', 'sd' => 'Sindhi', 'se' => 'Northern Sami', 'sm' => 'Samoan', 'sg' => 'Sango', 'sr' => 'Serbian', 'gd' => 'Scottish Gaelic, Gaelic', 'sn' => 'Shona', 'si' => 'Sinhala, Sinhalese', 'sk' => 'Slovak', 'sl' => 'Slovene', 'so' => 'Somali', 'st' => 'Southern Sotho', 'es' => 'Spanish', 'su' => 'Sundanese', 'sw' => 'Swahili', 'ss' => 'Swati', 'sv' => 'Swedish', 'ta' => 'Tamil', 'te' => 'Telugu', 'tg' => 'Tajik', 'th' => 'Thai', 'ti' => 'Tigrinya', 'bo' => 'Tibetan Standard, Tibetan, Central', 'tk' => 'Turkmen', 'tl' => 'Tagalog', 'tn' => 'Tswana', 'to' => 'Tonga (Tonga Islands)', 'tr' => 'Turkish', 'ts' => 'Tsonga', 'tt' => 'Tatar', 'tw' => 'Twi', 'ty' => 'Tahitian', 'ug' => 'Uyghur', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'uz' => 'Uzbek', 've' => 'Venda', 'vi' => 'Vietnamese', 'vo' => 'Volapük', 'wa' => 'Walloon', 'cy' => 'Welsh', 'wo' => 'Wolof', 'fy' => 'Western Frisian', 'xh' => 'Xhosa', 'yi' => 'Yiddish', 'yo' => 'Yoruba', 'za' => 'Zhuang, Chuang'];

    /**
     * @var array
     */
    protected $allCountries = ['AD' => 'Andorra', 'AE' => 'United Arab Emirates', 'AF' => 'Afghanistan', 'AG' => 'Antigua and Barbuda', 'AI' => 'Anguilla', 'AL' => 'Albania', 'AM' => 'Armenia', 'AO' => 'Angola', 'AQ' => 'Antarctica', 'AR' => 'Argentina', 'AS' => 'American Samoa', 'AT' => 'Austria', 'AU' => 'Australia', 'AW' => 'Aruba', 'AX' => 'Åland Islands', 'AZ' => 'Azerbaijan', 'BA' => 'Bosnia and Herzegovina', 'BB' => 'Barbados', 'BD' => 'Bangladesh', 'BE' => 'Belgium', 'BF' => 'Burkina Faso', 'BG' => 'Bulgaria', 'BH' => 'Bahrain', 'BI' => 'Burundi', 'BJ' => 'Benin', 'BL' => 'Saint Barthélemy', 'BM' => 'Bermuda', 'BN' => 'Brunei Darussalam', 'BO' => 'Bolivia, Plurinational State of', 'BQ' => 'Bonaire, Sint Eustatius and Saba', 'BR' => 'Brazil', 'BS' => 'Bahamas', 'BT' => 'Bhutan', 'BV' => 'Bouvet Island', 'BW' => 'Botswana', 'BY' => 'Belarus', 'BZ' => 'Belize', 'CA' => 'Canada', 'CC' => 'Cocos (Keeling) Islands', 'CD' => 'Congo, the Democratic Republic of the', 'CF' => 'Central African Republic', 'CG' => 'Congo', 'CH' => 'Switzerland', 'CI' => 'Côte d\'Ivoire', 'CK' => 'Cook Islands', 'CL' => 'Chile', 'CM' => 'Cameroon', 'CN' => 'China', 'CO' => 'Colombia', 'CR' => 'Costa Rica', 'CU' => 'Cuba', 'CV' => 'Cabo Verde', 'CW' => 'Curaçao', 'CX' => 'Christmas Island', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DE' => 'Germany', 'DJ' => 'Djibouti', 'DK' => 'Denmark', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'DZ' => 'Algeria', 'EC' => 'Ecuador', 'EE' => 'Estonia', 'EG' => 'Egypt', 'EH' => 'Western Sahara', 'ER' => 'Eritrea', 'ES' => 'Spain', 'ET' => 'Ethiopia', 'FI' => 'Finland', 'FJ' => 'Fiji', 'FK' => 'Falkland Islands (Malvinas)', 'FM' => 'Micronesia, Federated States of', 'FO' => 'Faroe Islands', 'FR' => 'France', 'GA' => 'Gabon', 'GB' => 'United Kingdom of Great Britain and Northern Ireland', 'GD' => 'Grenada', 'GE' => 'Georgia', 'GF' => 'French Guiana', 'GG' => 'Guernsey', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GL' => 'Greenland', 'GM' => 'Gambia', 'GN' => 'Guinea', 'GP' => 'Guadeloupe', 'GQ' => 'Equatorial Guinea', 'GR' => 'Greece', 'GS' => 'South Georgia and the South Sandwich Islands', 'GT' => 'Guatemala', 'GU' => 'Guam', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HK' => 'Hong Kong', 'HM' => 'Heard Island and McDonald Islands', 'HN' => 'Honduras', 'HR' => 'Croatia', 'HT' => 'Haiti', 'HU' => 'Hungary', 'ID' => 'Indonesia', 'IE' => 'Ireland', 'IL' => 'Israel', 'IM' => 'Isle of Man', 'IN' => 'India', 'IO' => 'British Indian Ocean Territory', 'IQ' => 'Iraq', 'IR' => 'Iran, Islamic Republic of', 'IS' => 'Iceland', 'IT' => 'Italy', 'JE' => 'Jersey', 'JM' => 'Jamaica', 'JO' => 'Jordan', 'JP' => 'Japan', 'KE' => 'Kenya', 'KG' => 'Kyrgyzstan', 'KH' => 'Cambodia', 'KI' => 'Kiribati', 'KM' => 'Comoros', 'KN' => 'Saint Kitts and Nevis', 'KP' => 'Korea, Democratic People\'s Republic of', 'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KY' => 'Cayman Islands', 'KZ' => 'Kazakhstan', 'LA' => 'Lao People\'s Democratic Republic', 'LB' => 'Lebanon', 'LC' => 'Saint Lucia', 'LI' => 'Liechtenstein', 'LK' => 'Sri Lanka', 'LR' => 'Liberia', 'LS' => 'Lesotho', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'LV' => 'Latvia', 'LY' => 'Libya', 'MA' => 'Morocco', 'MC' => 'Monaco', 'MD' => 'Moldova, Republic of', 'ME' => 'Montenegro', 'MF' => 'Saint Martin (French part)', 'MG' => 'Madagascar', 'MH' => 'Marshall Islands', 'MK' => 'Macedonia, the former Yugoslav Republic of', 'ML' => 'Mali', 'MM' => 'Myanmar', 'MN' => 'Mongolia', 'MO' => 'Macao', 'MP' => 'Northern Mariana Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MS' => 'Montserrat', 'MT' => 'Malta', 'MU' => 'Mauritius', 'MV' => 'Maldives', 'MW' => 'Malawi', 'MX' => 'Mexico', 'MY' => 'Malaysia', 'MZ' => 'Mozambique', 'NA' => 'Namibia', 'NC' => 'New Caledonia', 'NE' => 'Niger', 'NF' => 'Norfolk Island', 'NG' => 'Nigeria', 'NI' => 'Nicaragua', 'NL' => 'Netherlands', 'NO' => 'Norway', 'NP' => 'Nepal', 'NR' => 'Nauru', 'NU' => 'Niue', 'NZ' => 'New Zealand', 'OM' => 'Oman', 'PA' => 'Panama', 'PE' => 'Peru', 'PF' => 'French Polynesia', 'PG' => 'Papua New Guinea', 'PH' => 'Philippines', 'PK' => 'Pakistan', 'PL' => 'Poland', 'PM' => 'Saint Pierre and Miquelon', 'PN' => 'Pitcairn', 'PR' => 'Puerto Rico', 'PS' => 'Palestine, State of', 'PT' => 'Portugal', 'PW' => 'Palau', 'PY' => 'Paraguay', 'QA' => 'Qatar', 'RE' => 'Réunion', 'RO' => 'Romania', 'RS' => 'Serbia', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'SA' => 'Saudi Arabia', 'SB' => 'Solomon Islands', 'SC' => 'Seychelles', 'SD' => 'Sudan', 'SE' => 'Sweden', 'SG' => 'Singapore', 'SH' => 'Saint Helena, Ascension and Tristan da Cunha', 'SI' => 'Slovenia', 'SJ' => 'Svalbard and Jan Mayen', 'SK' => 'Slovakia', 'SL' => 'Sierra Leone', 'SM' => 'San Marino', 'SN' => 'Senegal', 'SO' => 'Somalia', 'SR' => 'Suriname', 'SS' => 'South Sudan', 'ST' => 'Sao Tome and Principe', 'SV' => 'El Salvador', 'SX' => 'Sint Maarten (Dutch part)', 'SY' => 'Syrian Arab Republic', 'SZ' => 'Swaziland', 'TC' => 'Turks and Caicos Islands', 'TD' => 'Chad', 'TF' => 'French Southern Territories', 'TG' => 'Togo', 'TH' => 'Thailand', 'TJ' => 'Tajikistan', 'TK' => 'Tokelau', 'TL' => 'Timor-Leste', 'TM' => 'Turkmenistan', 'TN' => 'Tunisia', 'TO' => 'Tonga', 'TR' => 'Turkey', 'TT' => 'Trinidad and Tobago', 'TV' => 'Tuvalu', 'TW' => 'Taiwan, Province of China', 'TZ' => 'Tanzania, United Republic of', 'UA' => 'Ukraine', 'UG' => 'Uganda', 'UM' => 'United States Minor Outlying Islands', 'US' => 'United States of America', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VA' => 'Holy See', 'VC' => 'Saint Vincent and the Grenadines', 'VE' => 'Venezuela, Bolivarian Republic of', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'VN' => 'Viet Nam', 'VU' => 'Vanuatu', 'WF' => 'Wallis and Futuna', 'WS' => 'Samoa', 'YE' => 'Yemen', 'YT' => 'Mayotte', 'ZA' => 'South Africa', 'ZM' => 'Zambia'];

    /**
     * @var array
     */
    protected $allPersonas = ['happy' => 'Happy Person', 'unhappy' => 'Unhappy Person', 'sleepy' => 'Sleepy Person', 'destructive' => 'Destructive Person'];

    /**
     * @return void
     */
    public function setUp(): void
    {
        foreach ($this->allLanguages as $languageCode => $languageName) {
            $this->configurationWithThreeDimensionsAndManyValues['language']['presets'][$languageCode] = [
                'label' => $languageName,
                'values' => [$languageCode],
                'uriSegment' => $languageCode
            ];
        }

        foreach ($this->allCountries as $countryCode => $countryName) {
            $this->configurationWithThreeDimensionsAndManyValues['country']['presets'][$countryCode] = [
                'label' => $countryName,
                'values' => [$countryCode],
                'uriSegment' => strtolower($countryCode)
            ];
        }

        foreach ($this->allPersonas as $personaCode => $personaName) {
            $this->configurationWithThreeDimensionsAndManyValues['persona']['presets'][$personaCode] = [
                'label' => $personaName,
                'values' => [$personaName],
                'uriSegment' => $personaCode
            ];
        }
    }

    /**
     * @test
     */
    public function findPresetByDimensionValuesWithExistingValuesReturnsPreset()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->findPresetByDimensionValues('language', ['de_DE', 'de_ZZ', 'mul_ZZ']);
        self::assertArrayHasKey('uriSegment', $preset);
        self::assertEquals('deutsch', $preset['uriSegment']);
    }


    /**
     * @test
     */
    public function getAllPresetsReturnsDimensionsOrderedByPosition()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $presets = $source->getAllPresets();
        self::assertEquals(['targetGroups', 'language'], array_keys($presets));
    }

    /**
     * @test
     */
    public function getAllPresetsReturnsDimensionPresetsOrderedByPosition()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $presets = $source->getAllPresets();
        self::assertArrayHasKey('language', $presets);
        self::assertEquals(['de_DE', 'all'], array_keys($presets['language']['presets']));
    }

    /**
     * @test
     */
    public function getDefaultPresetWithExistingDimensionReturnsDefaultPresetWithIdentifier()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->validConfiguration);
        $preset = $source->getDefaultPreset('language');
        self::assertArrayHasKey('identifier', $preset);
        self::assertEquals('all', $preset['identifier']);
    }

    /**
     * @test
     */
    public function setConfigurationThrowsExceptionIfSpecifiedDefaultPresetDoesNotExist()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1401093863);
        $source = new ConfigurationContentDimensionPresetSource();
        $configuration = $this->validConfiguration;
        $configuration['language']['defaultPreset'] = 'something';
        $source->setConfiguration($configuration);
    }

    /**
     * @test
     */
    public function isPresetCombinationAllowedByConstraintsReturnsTrueIfNoConstraintsHaveBeenDefined()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->configurationWithThreeDimensionsAndManyValues);

        self::assertTrue($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'DE']));
    }

    /**
     * @test
     */
    public function isPresetCombinationAllowedByConstraintsReturnsFalseIfAnyOfThePresetsDoesNotExist()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $source->setConfiguration($this->configurationWithThreeDimensionsAndManyValues);

        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['language' => 'xy', 'country' => 'DE']));
        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['languageXX' => 'de', 'country' => 'DE']));
        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'DEXX']));
    }

    /**
     * @test
     */
    public function isPresetCombinationAllowedByConstraintsReturnsFalseIfConstraintExplicitlyDoesNotAllowTheCombination()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $configuration = $this->configurationWithThreeDimensionsAndManyValues;
        $configuration['country']['presets']['US']['constraints']['language']['de'] = false;
        $source->setConfiguration($configuration);

        self::assertTrue($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'DE']));
        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'US']));
    }

    /**
     * @test
     */
    public function isPresetCombinationAllowedByConstraintsReturnsFalseIfWildCardConstraintIsSetToFalse()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $configuration = $this->configurationWithThreeDimensionsAndManyValues;
        $configuration['country']['presets']['US']['constraints']['language']['*'] = false;
        $source->setConfiguration($configuration);

        self::assertTrue($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'DE']));
        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'US']));
    }

    /**
     * @test
     */
    public function isPresetCombinationAllowedByConstraintsCorrectlyEvaluatesCombinationsOfWildcardAndExplicitConstraints()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $configuration = $this->configurationWithThreeDimensionsAndManyValues;
        $configuration['country']['presets']['US']['constraints']['language']['*'] = false;
        $configuration['country']['presets']['US']['constraints']['language']['de'] = true;
        $configuration['country']['presets']['US']['constraints']['language']['en'] = true;
        $source->setConfiguration($configuration);

        # Not affected by wildcard:
        self::assertTrue($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'DE']));

        # Affected by wildcard but explicitly allowed:
        self::assertTrue($source->isPresetCombinationAllowedByConstraints(['language' => 'de', 'country' => 'US']));
        self::assertTrue($source->isPresetCombinationAllowedByConstraints(['language' => 'en', 'country' => 'US']));

        # Affected by wildcard and thus not allowed:
        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['language' => 'it', 'country' => 'US']));
        self::assertFalse($source->isPresetCombinationAllowedByConstraints(['language' => 'fr', 'country' => 'US']));
    }

    /**
     * @test
     */
    public function getAllowedDimensionPresetsAccordingToPreselectionReturnsAllowedPresetsOfSecondDimensionIfPresetOfFirstDimensionIsGiven()
    {
        $source = new ConfigurationContentDimensionPresetSource();
        $configuration = $this->configurationWithThreeDimensionsAndManyValues;

        $configuration['country']['presets']['US']['constraints']['language']['*'] = false;
        $configuration['country']['presets']['US']['constraints']['language']['de'] = true;
        $configuration['country']['presets']['US']['constraints']['language']['en'] = true;

        $configuration['language']['presets']['it']['constraints']['country']['*'] = false;
        $configuration['language']['presets']['it']['constraints']['country']['IT'] = true;
        $source->setConfiguration($configuration);

        $dimensionAndPresets = $source->getAllowedDimensionPresetsAccordingToPreselection('language', ['country' => 'US']);

        # Only presets of the specified dimension should be returned:
        self::assertTrue(isset($dimensionAndPresets['language']));
        self::assertFalse(isset($dimensionAndPresets['country']));
        self::assertFalse(isset($dimensionAndPresets['persona']));

        $dimensionAndPresets = $source->getAllowedDimensionPresetsAccordingToPreselection('language', ['country' => 'US']);

        # "de" and "en" are explicitly allowed for country "US":
        self::assertArrayHasKey('de', $dimensionAndPresets['language']['presets']);
        self::assertArrayHasKey('en', $dimensionAndPresets['language']['presets']);
        self::assertCount(2, $dimensionAndPresets['language']['presets']);

        $dimensionAndPresets = $source->getAllowedDimensionPresetsAccordingToPreselection('country', ['language' => 'it']);

        # only "IT" is allowed as a country when "it" is selected as a language:
        self::assertArrayHasKey('IT', $dimensionAndPresets['country']['presets']);
        self::assertCount(1, $dimensionAndPresets['country']['presets']);
    }
}
