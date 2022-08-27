<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\WorkspaceRebase;

final class WorkspaceRebaseStatistics
{
    protected int $totalNumberOfAppliedCommands = 0;

    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $errorCommands = [];

    public function commandRebaseError(string $message, \Exception $cause): void
    {
        $this->totalNumberOfAppliedCommands++;
        $this->errorCommands[] = [
            'commandIndex' => $this->totalNumberOfAppliedCommands,
            'message' => $message,
            'cause' => [
                'class' => get_class($cause),
                'message' => $cause->getMessage()
            ]
        ];
    }

    public function commandRebaseSuccess(): void
    {
        $this->totalNumberOfAppliedCommands++;
    }

    public function hasErrors(): bool
    {
        return count($this->errorCommands) > 0;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getErrors(): array
    {
        return $this->errorCommands;
    }
}
