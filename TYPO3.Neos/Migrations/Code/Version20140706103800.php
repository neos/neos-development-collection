<?php
namespace TYPO3\Flow\Core\Migrations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Adjust to updated date format for inspector date editor
 */
class Version20140706103800 extends AbstractMigration
{
    /**
     * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
     * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
     * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.Neos-201407061038';
    }

    /**
     * @return void
     */
    public function up()
    {
        $dateDataTypes = array();
        $this->processConfiguration(
            \TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            function (&$configuration) use (&$dateDataTypes) {
                if (isset($configuration['TYPO3']['Neos']['userInterface']['inspector']['dataTypes'])) {
                    foreach ($configuration['TYPO3']['Neos']['userInterface']['inspector']['dataTypes'] as $dataType => &$dataTypeConfiguration) {
                        if ($dataTypeConfiguration['editor'] === 'TYPO3.Neos/Inspector/Editors/DateTimeEditor') {
                            $dateDataTypes[] = $dataType;

                            if (isset($dataTypeConfiguration['editorOptions']['format'])) {
                                $dataTypeConfiguration['editorOptions']['format'] = $this->transformFormat($dataTypeConfiguration['editorOptions']['format']);
                            }
                        }
                    }
                }
            },
            true
        );

        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) use ($dateDataTypes) {
                foreach ($configuration as &$nodeType) {
                    if (isset($nodeType['properties'])) {
                        foreach ($nodeType['properties'] as &$propertyConfiguration) {
                            if ((isset($propertyConfiguration['type']) && in_array($propertyConfiguration['type'], $dateDataTypes))
                                || (isset($propertyConfiguration['ui']['inspector']['editor']) && $propertyConfiguration['ui']['inspector']['editor'] === 'TYPO3.Neos/Inspector/Editors/DateTimeEditor')) {
                                if (isset($propertyConfiguration['ui']['inspector']['editorOptions']['format'])) {
                                    $propertyConfiguration['ui']['inspector']['editorOptions']['format'] = $this->transformFormat($propertyConfiguration['ui']['inspector']['editorOptions']['format']);
                                }
                            }
                        }
                    }
                }
            },
            true
        );
    }

    /**
     * @param string $format
     * @return string
     */
    protected function transformFormat($format)
    {
        return strtr($format, array(
            'yyyy' => 'Y',
            'yy' => 'y',
            'mm' => 'm',
            'm' => 'n',
            'MM' => 'F',
            'M' => 'M',
            'dd' => 'd',
            'd' => 'j',
            'p' => 'a',
            'P' => 'A',
            'hh' => 'H',
            'h' => 'G',
            'HH' => 'h',
            'H' => 'g',
            'ii' => 'i',
            'ss' => 's'
        ));
    }
}
