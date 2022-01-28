<?php

namespace Neos\Fusion\Testing\View;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\View\FusionView;

class AllowedContextFusionView extends FusionView implements ProtectedContextAwareInterface
{
    /**
     * so that ${ view.assign('foo', 123) works in eel }
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
