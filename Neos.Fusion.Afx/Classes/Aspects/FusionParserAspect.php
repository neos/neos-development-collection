<?php
namespace PackageFactory\AtomicFusion\AFX\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * Class FusionParserAspect
 *
 * @package PackageFactory\AtomicFusion\AFX\Aspects
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionParserAspect
{

    const SCAN_PATTERN_AFX = "/([ \\t]*)([a-z0-9.]+[\\s]*=[\\s]*)AFX::[\\s]*\\n(.*?)\\n[ \\t]*\\n/us";

    /**
     * @Flow\Around("method(Neos\Fusion\Core\Parser->parse())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function expandASXToFusion(JoinPointInterface $joinPoint)
    {
        $fusionCode = $joinPoint->getMethodArgument('sourceCode');

        if (preg_match(self::SCAN_PATTERN_AFX, $fusionCode)) {
            $fusionCodeProcessed = preg_replace(self::SCAN_PATTERN_AFX,  "$1$2 Neos.Fusion:Tag {\n$1  content = 'foo'  \n$1}\n", $fusionCode);
            $joinPoint->setMethodArgument('sourceCode', $fusionCodeProcessed);
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);;
    }

}