<?php
namespace Neos\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Context\Workspace\WorkspaceName;
use Ramsey\Uuid\Uuid;

/**
 * Testcase for the "Domain" domain model
 *
 */
class WorkspaceNameTest extends UnitTestCase
{
    /**
     * @param string $accountIdentifier
     * @param string $expectedWorkspaceName
     * @test
     * @dataProvider accountIdentifierProvider
     */
    public function fromAccountIdentifierCreatesCorrectWorkspaceName(string $accountIdentifier, string $expectedWorkspaceName)
    {
        $this->assertSame(
            $expectedWorkspaceName,
            (string) WorkspaceName::fromAccountIdentifier($accountIdentifier)
        );
    }

    /**
     * @return array
     */
    public function accountIdentifierProvider(): array
    {
        $randomUuid = (string) Uuid::uuid4();
        return [
            [
                'me@domain.com',
                'user-me-domain-com'
            ],
            [
                'whatever+-something/someone',
                'user-whatever--something-someone'
            ],
            [
                'whatever+-something/someone',
                'user-whatever--something-someone'
            ],
            [
                $randomUuid,
                'user-' . $randomUuid
            ],
            [
                'me',
                'user-me'
            ]
        ];
    }

    /**
     * @param array $takenWorkspaceNames
     * @param string $expectedWorkspaceName
     * @test
     * @dataProvider takenWorkspaceNamesProvider
     */
    public function incrementAddsLowestPossibleNumberIfAny(array $takenWorkspaceNames, string $expectedWorkspaceName)
    {
        $workspaceName = new WorkspaceName('user-me');
        $this->assertSame(
            $expectedWorkspaceName,
            (string) $workspaceName->increment($takenWorkspaceNames)
        );
    }

    /**
     * @return array
     */
    public function takenWorkspaceNamesProvider(): array
    {
        return [
            [
                [],
                'user-me'
            ],
            [
                [
                    'user-someone-else' => true
                ],
                'user-me'
            ],
            [
                [
                    'user-me-but-not-exactly' => true
                ],
                'user-me'
            ],
            [
                [
                    'user-me' => true
                ],
                'user-me_1'
            ],
            [
                [
                    'user-me' => true,
                    'user-me_1' => true,
                ],
                'user-me_2'
            ],
            [
                [
                    'user-me' => true,
                    'user-me_2' => true,
                ],
                'user-me_1'
            ]
        ];
    }
}
