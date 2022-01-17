<?php

namespace Neos\Fusion\Tests\Functional\FusionObjects\FusionTestsFusion;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\View\FusionView;

class FusionViewAllowedContext extends FusionView implements ProtectedContextAwareInterface
{
    /**
     * so that ${ view.assign('foo', 123) works in eel }
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
