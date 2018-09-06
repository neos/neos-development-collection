<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Arrays;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

/**
 * Remove namespace aliases and replace them with the package keys
 */
class Version20180211184832 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Fusion-20180211184832';
    }

    /**
     * @return void
     * @throws FilesException
     */
    public function up()
    {
        $namespaces = $this->findDeclaredFusionNamespaces();
        foreach ($namespaces as $alias => $packageKey) {
            // remove namespace declarations
            $this->searchAndReplaceRegex('/^[\\s]*namespace:[\\s]*' . preg_quote($alias) . '[\\s]*\\=[\\s]*' . preg_quote($packageKey) . '[\\s]*$/um', '', ['ts2', 'fusion']);
            // expand alias in fusion object assignments
            $this->searchAndReplaceRegex('/(?<=\\=)([\\s]*)(' . preg_quote($alias) . ')(?=\\:[\\w\\.]+[$\\s\\{])/u', '$1' . $packageKey, ['ts2', 'fusion']);
            // expand alias in prototype declarations
            $this->searchAndReplaceRegex('/(?<=prototype\\()(' . preg_quote($alias) . ')(?=\\:[\\w\\.]+\\))/um', $packageKey, ['ts2', 'fusion']);
        }
    }


    /**
     * Find all declared fusion namespaces for the currently migrated package
     *
     * @return array an array with namespace alias as key and packageKey as value
     * @throws FilesException
     */
    protected function findDeclaredFusionNamespaces()
    {
        $namespaces = [];

        $targetDirectory = $this->targetPackageData['path'] . '/Resources/Private';
        if(!is_dir($targetDirectory)) {
            return $namespaces;
        }

        foreach (Files::getRecursiveDirectoryGenerator($targetDirectory, null, true) as $pathAndFilename) {
            $pathInfo = pathinfo($pathAndFilename);
            if (!isset($pathInfo['filename'])) {
                continue;
            }
            if (strpos($pathAndFilename, 'Migrations/Code') !== false) {
                continue;
            }

            if (array_key_exists('extension', $pathInfo) && ($pathInfo['extension'] == 'ts2' || $pathInfo['extension'] == 'fusion')) {
                $namespaceDeclarationMatches = [];
                $count = preg_match_all(
                    '/^namespace:[\\s]*(?P<alias>[\\w\\.]+)[\\s]*\\=[\\s]*(?P<packageKey>[\\w\\.]+)[\\s]*$/um',
                    file_get_contents($pathAndFilename),
                    $namespaceDeclarationMatches,
                    PREG_SET_ORDER
                );
                if ($count > 0) {
                    foreach ($namespaceDeclarationMatches as $match) {
                        if (!array_key_exists($match['alias'], $namespaces)) {
                            $namespaces[$match['alias']] = $match['packageKey'];
                        } else {
                            if ($namespaces[$match['alias']] !== $match['packageKey']) {
                                $this->showWarning(
                                    sprintf(
                                        'Namespace-alias "%s" was declared multiple times for different aliases %s is used',
                                        $match['alias'],
                                        $namespaces[$match['alias']]
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
        return $namespaces;
    }
}
