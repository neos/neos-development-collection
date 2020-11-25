<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Service\HtmlAugmenter;

/**
 * A Fusion Augmenter-Object
 *
 * The fusion object can be used to add html-attributes to the rendererd content
 *
 * @api
 */
class AugmenterImplementation extends JoinImplementation
{

    /**
     * @var HtmlAugmenter
     * @Flow\Inject
     */
    protected $htmlAugmenter;

    /**
     * Properties that are ignored
     *
     * @var array
     */
    protected $ignoreProperties = ['__meta', 'fallbackTagName', 'content'];

    /**
     * @return void|string
     */
    public function evaluate()
    {
        $content = $this->fusionValue('content');
        $fallbackTagName = $this->fusionValue('fallbackTagName');

        $sortedChildFusionKeys = $this->sortNestedFusionKeys();

        $attributes = [];
        foreach ($sortedChildFusionKeys as $key) {
            if ($fusionValue = $this->fusionValue($key)) {
                $attributes[$key] = $fusionValue;
            }
        }

        if ($attributes && is_array($attributes) && count($attributes) > 0) {
            return $this->htmlAugmenter->addAttributes($content, $attributes, $fallbackTagName);
        } else {
            return $content;
        }
    }
}
