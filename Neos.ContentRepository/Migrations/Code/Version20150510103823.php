<?php
namespace Neos\Flow\Core\Migrations;

/**
 * Migrates to new super type syntax in node type configuration
 */
class Version20150510103823 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'TYPO3.TYPO3CR-20150510103823';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(
            'NodeTypes',
            function (&$configuration) {
                foreach ($configuration as $nodeTypeName => &$nodeType) {
                    if (isset($nodeType['superTypes'])) {
                        $superTypeConfiguration = [];
                        foreach ($nodeType['superTypes'] as $key => $superType) {
                            // Handle values that are already adjusted
                            if (is_string($key) && (is_bool($superType) || $superType === null)) {
                                $superTypeConfiguration[$key] = $superType === null ? false : $superType;
                                continue;
                            }
                            if (is_string($key) && (strtolower($superType) === 'true' || $superType === '1' || $superType === 1)) {
                                $superTypeConfiguration[$key] = true;
                                continue;
                            }
                            if (is_string($key) && (strtolower($superType) === 'false' || $superType === '0' || $superType === 0)) {
                                $superTypeConfiguration[$key] = false;
                                continue;
                            }
                            if (!is_string($superType)) {
                                $this->showWarning(sprintf('The super type configuration for "%s" with the key "%s" could not be converted to the new super type configuration format', $nodeTypeName, $key));
                                continue;
                            }
                            $superTypeConfiguration[$superType] = true;
                        }
                        $nodeType['superTypes'] = $superTypeConfiguration;
                    }
                }
            },
            true
        );
    }
}
