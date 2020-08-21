<?php

namespace Neos\SiteKickstarter\Annotation;


/**
 * Class SitePackageGenerator
 * @package Neos\SiteKickstarter\Annotation
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class SitePackageGenerator
{
    /**
     * The name of the generator. (Can be given as anonymous argument.)
     * @var string
     */
    public $generatorName;

    /**
     * @param array $values
     */
    public function __construct(array $values) {
        if (!isset($values['value']) && !isset($values['generatorName'])) {
            throw new \InvalidArgumentException('A SitePackageGenerator annotation must specify a generatorName.', 1234567890);
        }
        $this->generatorName = isset($values['generatorName']) ? $values['generatorName'] : $values['value'];
    }
}
