<?php
namespace Neos\Fusion\Afx\Service;

/*
 * This file is part of the Neos.Fusion.Afx package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Afx\Parser\Parser as AfxParser;
use Neos\Fusion\Afx\Exception\AfxException;

/**
 * Class AfxService
 *
 * @Flow\Scope("singleton")
 */
class AfxService
{
    const INDENTATION = '    ';

    /**
     * @var string $afxCode the AFX code that is converted
     * @var string $indentation Indentation to start with
     * @return string
     */
    public static function convertAfxToFusion($afxCode, $indentation = '')
    {
        $parser = new AfxParser(trim($afxCode));
        $ast = $parser->parse();
        $fusion = self::astNodeListToFusion($ast, $indentation);
        return $fusion;
    }

    /**
     * @param array $astNode
     * @param string $indentation
     * @return string
     */
    protected static function astToFusion($ast, $indentation = '')
    {
        switch ($ast['type']) {
            case 'expression':
                return self::astExpressionToFusion($ast['payload'], $indentation);
                break;
            case 'string':
                return self::astStringToFusion($ast['payload'], $indentation);
                break;
            case 'text':
                return self::astTextToFusion($ast['payload'], $indentation);
                break;
            case 'boolean':
                return self::astBooleanToFusion($ast['payload'], $indentation);
                break;
            case 'node':
                return self::astNodeToFusion($ast['payload'], $indentation);
                break;
            default:
                throw new AfxException(sprintf('ast type %s is unkonwn', $ast['type']));
        }
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astBooleanToFusion($payload, $indentation = '')
    {
        return 'true';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astExpressionToFusion($payload, $indentation = '')
    {
        return '${' . $payload . '}';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astStringToFusion($payload, $indentation = '')
    {
        return '\'' . addslashes($payload) . '\'';
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astTextToFusion($payload, $indentation = '')
    {
        return '\'' . addslashes($payload) . '\'';
    }

    /**
     * @param array $payload
     * @param array $attributePrefix
     * @param string $indentation
     * @return string
     */
    protected static function propListToFusion($payload, $attributePrefix, $indentation = '')
    {
        $fusion = '';
        foreach ($payload as $attribute) {
            if ($attribute['type'] === 'prop') {
                $prop = $attribute['payload'];
                $propName = $prop['identifier'];
                $propFusion = self::astToFusion($prop, $indentation . self::INDENTATION);
                if ($propFusion !== null) {
                    $fusion .= $indentation . self::INDENTATION . $attributePrefix . $propName . ' = ' . $propFusion . PHP_EOL;
                }
            }
        }
        return $fusion;
    }


    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astNodeToFusion($payload, $indentation = '')
    {
        $tagName = $payload['identifier'];
        $childrenPropertyName = 'content';
        $attributes = $payload['attributes'];

        // filter attributes and remove @key, @path and @children attributes
        $attributes = array_filter($attributes, function ($attribute) use (&$childrenPropertyName) {
            if ($attribute['type'] === 'prop') {
                if ($attribute['payload']['identifier'] === '@key' || $attribute['payload']['identifier'] === '@path') {
                    return false;
                } elseif ($attribute['payload']['identifier'] === '@children') {
                    if ($attribute['payload']['type'] === 'string') {
                        $childrenPropertyName = $attribute['payload']['payload'];
                    } else {
                        throw new AfxException(
                            sprintf('@children only supports string payloads %s found', $attribute['payload']['type'])
                        );
                    }
                    return false;
                }
            }
            return true;
        });

        // filter children into path and content children
        $pathChildren = [];
        $contentChildren = [];
        if ($payload['children'] && count($payload['children']) > 0) {
            foreach ($payload['children'] as $child) {
                if ($child['type'] === 'node') {
                    $path = null;
                    foreach ($child['payload']['attributes'] as $attribute) {
                        if ($attribute['type'] === 'prop' && $attribute['payload']['identifier'] === '@path') {
                            $pathAttribute = $attribute['payload'];
                            if ($pathAttribute['type'] === 'string') {
                                $path = $pathAttribute['payload'];
                            } else {
                                throw new AfxException(
                                    sprintf('@path only supports string payloads %s found', $pathAttribute['type'])
                                );
                            }
                        }
                    };

                    if ($path) {
                        $pathChildren[$path] = $child;
                        continue;
                    }
                }
                $contentChildren[] = $child;
            }
        }

        // Tag
        if (strpos($tagName, ':') !== false) {
            // Named fusion-object
            $fusion = $tagName . ' {' . PHP_EOL;
            // Attributes are not prefixed
            $attributePrefix = '';
        } else {
            // Neos.Fusion:Tag
            $fusion = 'Neos.Fusion:Tag {' . PHP_EOL;
            $fusion .= $indentation . self::INDENTATION .'tagName = \'' .  $tagName . '\'' . PHP_EOL;
            // Attributes are rendered as tag-attributes
            $attributePrefix = 'attributes.';
            // Self closing Tags stay self closing
            if ($payload['selfClosing'] === true) {
                $fusion .= $indentation . self::INDENTATION .'selfClosingTag = true' . PHP_EOL;
            }
        }

        // Attributes
        if ($attributes !== []) {
            $spreadIsPresent = false;
            $metaAttributes = [];
            $fusionAttributes = [];
            $spreadsOrAttributeLists = [];

            // seperate between attributes (before the first spread), meta attributes
            // spreads and attributes lists between and after spreads
            foreach ($attributes as $attribute) {
                if ($attribute['type'] === 'prop' && $attribute['payload']['identifier'][0] === '@') {
                    $metaAttributes[] = $attribute;
                } elseif ($attribute['type'] === 'prop' && $spreadIsPresent === false) {
                    $fusionAttributes[] = $attribute;
                } elseif ($attribute['type'] === 'spread') {
                    $spreadsOrAttributeLists[] = $attribute;
                    $spreadIsPresent = true;
                } elseif ($attribute['type'] === 'prop') {
                    $last = end($spreadsOrAttributeLists);
                    $lastPos = key($spreadsOrAttributeLists);
                    if ($last && $last['type'] === 'propList') {
                        $last['payload'][] = $attribute;
                        $spreadsOrAttributeLists[$lastPos] = $last;
                    } else {
                        $spreadsOrAttributeLists[] = [
                            'type' => 'propList',
                            'payload' => [$attribute]
                        ];
                    }
                }
            }

            // attributes before the first spread render as fusion keys
            if ($fusionAttributes !== []) {
                $fusion .=  self::propListToFusion($fusionAttributes, $attributePrefix, $indentation);
            }

            // starting with the first spread we render spreads as @apply expressions
            // and attributes as @apply of the respective propList
            $spreadIndex = 1;
            foreach ($spreadsOrAttributeLists as $attribute) {
                if ($attribute['type'] === 'spread') {
                    if ($attribute['payload']['type'] === 'expression') {
                        $spreadFusion = self::astToFusion($attribute['payload'], $indentation . self::INDENTATION);
                        if ($spreadFusion !== null) {
                            $fusion .= $indentation . self::INDENTATION . $attributePrefix . '@apply.spread_' . $spreadIndex . ' = ' . $spreadFusion . PHP_EOL;
                        }
                    } else {
                        throw new AfxException(
                            sprintf('Spreads only support expression payloads %s found', $attribute['payload']['type'])
                        );
                    }
                } elseif ($attribute['type'] === 'propList') {
                    $fusion .= $indentation . self::INDENTATION . $attributePrefix . '@apply.spread_' . $spreadIndex . ' = Neos.Fusion:RawArray {' . PHP_EOL;
                    $fusion .=  self::propListToFusion($attribute['payload'], '', $indentation . self::INDENTATION);
                    $fusion .= $indentation . self::INDENTATION . '}' . PHP_EOL;
                }
                $spreadIndex ++;
            }

            // meta attributes are rendered last
            if ($metaAttributes !== []) {
                $fusion .=  self::propListToFusion($metaAttributes, '', $indentation);
            }
        }

        // Path Children
        if ($pathChildren !== []) {
            foreach ($pathChildren as $path => $child) {
                $fusion .= $indentation . self::INDENTATION . $path . ' = ' . self::astToFusion($child, $indentation . self::INDENTATION) . PHP_EOL;
            }
        }

        // Content Children
        if ($contentChildren !== []) {
            $childFusion = self::astNodeListToFusion($contentChildren, $indentation . self::INDENTATION);
            if ($childFusion) {
                $fusion .= $indentation . self::INDENTATION . $childrenPropertyName . ' = ' . $childFusion . PHP_EOL;
            }
        }

        $fusion .= $indentation . '}';

        return $fusion;
    }

    /**
     * @param array $payload
     * @param string $indentation
     * @return string
     */
    protected static function astNodeListToFusion($payload, $indentation = '')
    {
        $index = 1;

        // ignore blank text if it is connected to a newline
        $payload = array_map(function ($astNode) {
            if ($astNode['type'] === 'text') {
                $astNode['payload'] = preg_replace('/[\\s]*\\n[\\s]*/u', '', $astNode['payload']);
            }
            return $astNode;
        }, $payload);

        // filter empty text nodes and comments
        $payload = array_filter($payload, function ($astNode) {
            if ($astNode['type'] === 'text' && $astNode['payload'] == '') {
                return false;
            }
            if ($astNode['type'] === 'comment') {
                return false;
            }
            return true;
        });

        if (count($payload) === 0) {
            return '\'\'';
        } elseif (count($payload) === 1) {
            return self::astToFusion(array_shift($payload), $indentation);
        } else {
            $fusion = 'Neos.Fusion:Array {' . PHP_EOL;
            foreach ($payload as $astNode) {
                // detect key
                $fusionName = 'item_' . $index;
                if ($astNode['type'] === 'node' && $astNode['payload']['attributes'] !== []) {
                    foreach ($astNode['payload']['attributes'] as $attribute) {
                        if ($attribute['type'] === 'prop' && $attribute['payload']['identifier'] === '@key') {
                            if ($attribute['payload']['type'] === 'string') {
                                $fusionName = $attribute['payload']['payload'];
                            } else {
                                throw new AfxException(
                                    sprintf(
                                        '@key only supports string payloads %s was given',
                                        $attribute['payload']['type']
                                    )
                                );
                            }
                        }
                    }
                }

                // convert node
                $nodeFusion = self::astToFusion($astNode, $indentation . self::INDENTATION);
                if ($nodeFusion !== null) {
                    $fusion .= $indentation . self::INDENTATION . $fusionName . ' = ' . $nodeFusion . PHP_EOL;
                    $index++;
                }
            }
            $fusion .= $indentation . '}';
            return $fusion;
        }
    }
}
