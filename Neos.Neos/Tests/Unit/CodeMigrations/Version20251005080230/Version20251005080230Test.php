<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\CodeMigrations\Version20251005080230;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Core\Migrations\Manager;
use Neos\Flow\Core\Migrations\Version20251005080230;
use Neos\Neos\Tests\Unit\CodeMigrations\MigrationFixtureIterator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class Version20251005080230Test extends TestCase
{
    public static function fixtures(): iterable
    {
        yield from MigrationFixtureIterator::createForFilesInDirectory(__DIR__ . '/Fixture', 'fusion.inc');
    }

    #[DataProvider('fixtures')]
    #[Test]
    public function executeMigration(string $fusionInputFile, string $expectedFusionOutputFile): void
    {
        vfsStream::setup('fusion', null, [
            "Target.Package" => [
                'Resources' => [
                    'SomeFile.fusion' => $fusionInputFile
                ],
            ]
        ]);

        $fakeManager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock();

        if (!class_exists(Version20251005080230::class)) {
            // migrations are not PSR auto-loaded
            require_once __DIR__ . '/../../../../Migrations/Code/Version20251005080230.php';
        }

        $migration = new Version20251005080230(
            $fakeManager,
            'Neos.Neos'
        );

        $targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $migration->prepare($targetPackageData);
        $migration->up();
        $migration->execute();

        self::assertEquals(
            $expectedFusionOutputFile,
            file_get_contents('vfs://fusion/Target.Package/Resources/SomeFile.fusion')
        );
    }

    #[DataProvider('fixtures')]
    #[Test]
    public function reExecuteMigrationEmitsNoChanges(string $_, string $migratedFusionFile): void
    {
        vfsStream::setup('fusion', null, [
            "Target.Package" => [
                'Resources' => [
                    'SomeFile.fusion' => $migratedFusionFile
                ],
            ]
        ]);

        $fakeManager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->disableAutoReturnValueGeneration()->getMock();

        if (!class_exists(Version20251005080230::class)) {
            // migrations are not PSR auto-loaded
            require_once __DIR__ . '/../../../../Migrations/Code/Version20251005080230.php';
        }

        $migration = new Version20251005080230(
            $fakeManager,
            'Neos.Neos'
        );

        $targetPackageData = [
            'path' => 'vfs://fusion/Target.Package'
        ];

        $migration->prepare($targetPackageData);
        $migration->up();

        $migration->execute();

        self::assertEquals(
            $migratedFusionFile,
            file_get_contents('vfs://fusion/Target.Package/Resources/SomeFile.fusion')
        );

        if ($migration->hasWarnings()) {
            // warnings were logged but not written
            self::assertContains(
                'File ./Resources/SomeFile.fusion: No migration todo comments written as the migration was already run.',
                $migration->getWarnings()
            );
        }
    }
}
