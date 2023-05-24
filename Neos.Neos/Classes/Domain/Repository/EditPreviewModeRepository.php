<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Repository;

use Neos\Neos\Controller\Exception\InvalidEditPreviewModeException;
use Neos\Neos\Domain\Model\EditPreviewMode;
use Neos\Flow\Annotations as Flow;

class EditPreviewModeRepository
{
    #[Flow\InjectConfiguration(path:"userInterface.defaultEditPreviewMode")]
    protected string $defaultEditPreviewMode;

    /**
     * @var array<string, array{'title'?:string, 'fusionRenderingPath'?:string, 'isEditingMode'?:bool, 'isPreviewMode'?:bool}>
     */
    #[Flow\InjectConfiguration(path:"userInterface.editPreviewModes")]
    protected array $editPreviewModeConfigurations;

    public function findDefault(): EditPreviewMode
    {
        return EditPreviewMode::fromNameAndConfiguration($this->defaultEditPreviewMode, $this->editPreviewModeConfigurations[$this->defaultEditPreviewMode]);
    }

    public function findByName(string $name): EditPreviewMode
    {
        if (array_key_exists($name, $this->editPreviewModeConfigurations)) {
            return EditPreviewMode::fromNameAndConfiguration($name, $this->editPreviewModeConfigurations[$name]);
        }
        throw new InvalidEditPreviewModeException(sprintf('"%s" is not a valid editPreviewMode', $name), 1683790077);
    }
}
