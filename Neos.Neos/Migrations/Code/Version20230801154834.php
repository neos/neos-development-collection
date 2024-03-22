<?php

namespace Neos\Flow\Core\Migrations;


/**
 * Replace defaultUriSuffix configuration with uriPathSuffix in default site preset configuration
 */
class Version20230801154834 extends AbstractMigration
{

    public function getIdentifier(): string
    {
        return 'Neos.Neos-20230801154834';
    }

    public function up(): void
    {
        $this->moveSettingsPaths(['Neos', 'Flow', 'mvc', 'routes', 'Neos.Neos', 'variables', 'defaultUriSuffix'], ['Neos', 'Neos', 'sitePresets', 'default', 'uriPathSuffix']);
    }
}
