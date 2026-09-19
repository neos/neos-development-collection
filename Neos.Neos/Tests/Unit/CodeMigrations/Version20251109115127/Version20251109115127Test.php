<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\CodeMigrations\Version20251109115127;

use Neos\Flow\Core\Migrations\Manager;
use Neos\Flow\Core\Migrations\Version20251109115127;
use Neos\Neos\Tests\Unit\CodeMigrations\MigrationFixtureIterator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class Version20251109115127Test extends TestCase
{
    public static function settingsFixtures(): iterable
    {
        yield from MigrationFixtureIterator::createForFilesInDirectory(__DIR__ . '/Fixture/Settings', 'yaml.inc');
    }

    public static function routesFixtures(): iterable
    {
        yield from MigrationFixtureIterator::createForFilesInDirectory(__DIR__ . '/Fixture/Routes', 'yaml.inc');
    }

    #[DataProvider('settingsFixtures')]
    #[Test]
    public function executeSettingsMigration(string $yamlInputFile, string $expectedYamlOutputFile, string $expectedWarnings = ''): void
    {
        vfsStream::setup('yaml', null, [
            "Target.Package" => [
                'Configuration' => [
                    'Settings.SomeFile.yaml' => $yamlInputFile
                ],
            ]
        ]);

        $fakeManager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock();

        if (!class_exists(Version20251109115127::class)) {
            // migrations are not PSR auto-loaded
            require_once __DIR__ . '/../../../../Migrations/Code/Version20251109115127.php';
        }

        $migration = new Version20251109115127(
            $fakeManager,
            'Neos.Neos'
        );

        $targetPackageData = [
            'path' => 'vfs://yaml/Target.Package'
        ];

        $migration->prepare($targetPackageData);
        $migration->up();

        self::assertEquals(
            $expectedYamlOutputFile,
            rtrim(file_get_contents('vfs://yaml/Target.Package/Configuration/Settings.SomeFile.yaml'))
        );
    }

    #[DataProvider('routesFixtures')]
    #[Test]
    public function executeRoutesMigration(string $yamlInputFile, string $expectedYamlOutputFile): void
    {
        vfsStream::setup('yaml', null, [
            "Target.Package" => [
                'Configuration' => [
                    'Routes.yaml' => $yamlInputFile
                ],
            ]
        ]);

        $fakeManager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock();

        if (!class_exists(Version20251109115127::class)) {
            // migrations are not PSR auto-loaded
            require_once __DIR__ . '/../../../../Migrations/Code/Version20251109115127.php';
        }

        $migration = new Version20251109115127(
            $fakeManager,
            'Neos.Neos'
        );

        $targetPackageData = [
            'path' => 'vfs://yaml/Target.Package'
        ];

        $migration->prepare($targetPackageData);
        $migration->up();

        self::assertEquals(
            $expectedYamlOutputFile,
            rtrim(file_get_contents('vfs://yaml/Target.Package/Configuration/Routes.yaml'))
        );

        self::assertEmpty($migration->getWarnings());
    }

    #[DataProvider('settingsFixtures')]
    #[DataProvider('routesFixtures')]
    #[Test]
    public function reExecuteMigrationEmitsNoChanges(string $_, string $migratedYamlFile): void
    {
        vfsStream::setup('yaml', null, [
            "Target.Package" => [
                'Configuration' => [
                    'Settings.SomeFile.yaml' => $migratedYamlFile,
                    'Routes.yaml' => $migratedYamlFile,
                ],
            ]
        ]);

        $fakeManager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock();

        if (!class_exists(Version20251109115127::class)) {
            // migrations are not PSR auto-loaded
            require_once __DIR__ . '/../../../../Migrations/Code/Version20251109115127.php';
        }

        $migration = new Version20251109115127(
            $fakeManager,
            'Neos.Neos'
        );

        $targetPackageData = [
            'path' => 'vfs://yaml/Target.Package'
        ];

        $migration->prepare($targetPackageData);
        $migration->up();

        self::assertEquals(
            $migratedYamlFile,
            file_get_contents('vfs://yaml/Target.Package/Configuration/Settings.SomeFile.yaml')
        );

        self::assertEquals(
            $migratedYamlFile,
            file_get_contents('vfs://yaml/Target.Package/Configuration/Routes.yaml')
        );

        self::assertEmpty($migration->getWarnings());
    }
}
