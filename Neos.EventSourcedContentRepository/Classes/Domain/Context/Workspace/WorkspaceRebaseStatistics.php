<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 */
final class WorkspaceRebaseStatistics
{

    protected $totalNumberOfAppliedCommands = 0;

    /**
     * @var array
     */
    protected $errorCommands = [];


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

    public function getErrors(): array
    {
        return $this->errorCommands;
    }
}
