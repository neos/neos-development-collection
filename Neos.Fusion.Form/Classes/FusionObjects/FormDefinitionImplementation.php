<?php
declare(strict_types=1);

namespace Neos\Fusion\Form\FusionObjects;

/*
 * This file is part of the Neos.Fusion.Form package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Fusion\Form\Domain\Model\FormDefinition;
use Neos\Error\Messages\Result;

/**
 * Class FormImplementation
 * @package Neos\Fusion\Form\Model
 */
class FormDefinitionImplementation extends AbstractFusionObject
{
    public function evaluate()
    {
        $request = $this->fusionValue('request');

        return new FormDefinition(
            $this->fusionValue('name'),
            $this->fusionValue('object'),
            $this->fusionValue('fieldNamePrefix') ?: $request->getArgumentNamespace(),
            $request ? $request->getInternalArgument('__submittedArguments') : [],
            $request ? $request->getInternalArgument('__submittedArgumentValidationResults') : new Result()
        );
    }
}
