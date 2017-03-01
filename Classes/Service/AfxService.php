<?php
namespace PackageFactory\AtomicFusion\AFX\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use PackageFactory\Afx\Parser as AfxParser;
use PackageFactory\AtomicFusion\AFX\Exception\Exception;

/**
 * Class AfxService
 * @package PackageFactory\AtomicFusion\AFX\Service
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
        $parser = new AfxParser($afxCode);
        $ast = $parser->parse();
        $fusion = AfxService::astNodeToFusion($ast, $indentation);
        return $fusion;
    }

    /**
     * @param array $astNode
     * @param string $indentation
     * @return string
     */
    protected static function astNodeToFusion ($astNode, $indentation = '')
    {
        $tagName = $astNode['identifier'];

        // Tag
        if (strpos($tagName, ':') !== false) {
            // Named fusion-object
            $fusion = $indentation . $tagName . ' {' . PHP_EOL;
            $childrenPropertyName = 'renderer';
            $attributePrefix = '';
        } else {
            // Neos.Fusion:Tag
            $fusion = 'Neos.Fusion:Tag {' . PHP_EOL;
            $fusion .= $indentation . AfxService::INDENTATION .'tagName = \'' .  $tagName . '\'' . PHP_EOL;
            $childrenPropertyName = 'content';
            $attributePrefix = 'attributes.';
        }

        // Attributes
        if ($astNode['props'] && count($astNode['props']) > 0) {
            foreach ($astNode['props'] as $propName => $prop) {
                if ($propName == '@key') {
                    continue;
                }
                if ($propName == '@children') {
                    if ($prop['type'] == 'string') {
                        $childrenPropertyName = $prop['payload'];
                    } else {
                        throw new Exception(sprintf('@children only supports string payloads %s found', $prop['type']));
                    }
                } else {
                    switch ($prop['type']) {
                        case 'expression':
                            $fusion .= $indentation . AfxService::INDENTATION . $attributePrefix . $propName . ' = ${' . $prop['payload'] . '}' . PHP_EOL;
                            break;
                        case 'string':
                            $fusion .= $indentation . AfxService::INDENTATION . $attributePrefix . $propName . ' = \'' . $prop['payload'] . '\'' . PHP_EOL;
                            break;
                        case 'boolean':
                            $fusion .= $indentation . AfxService::INDENTATION . $attributePrefix . $propName . ' = true ' . PHP_EOL;
                            break;
                    }
                }
            }
        }

        // Children
        if ($astNode['children'] && count($astNode['children']) > 0) {
            $fusion .= $indentation . AfxService::INDENTATION . $childrenPropertyName . ' = ' . AfxService::astNodeListToFusion($astNode['children'], $indentation);
        }

        $fusion .= $indentation . '}' . PHP_EOL;

        return $fusion;
    }


    /**
     * @param array $astNodeList
     * @param string $indentation
     * @return string
     */
    protected static function astNodeListToFusion ($astNodeList, $indentation = '')
    {
        $fusion = 'Neos.Fusion:Array {' . PHP_EOL;
        $index = 1;
        foreach ($astNodeList as $astNode) {
            $nodeFusion = false;
            switch ($astNode['type']) {
                case 'expression':
                    $nodeFusion = $index . ' = ${' . $astNode['payload'] . '}'  . PHP_EOL;;
                    break;
                case 'text':
                    if (trim($astNode['payload']) !== '') {
                        $nodeFusion = $index . ' = \'' . $astNode['payload'] . '\'' . PHP_EOL;;
                    }
                    break;
                case 'node':
                    $fusionName = $index;
                    if ($keyProperty = Arrays::getValueByPath($astNode, 'payload.props.@key')) {
                        if ($keyProperty['type'] == 'string') {
                            $fusionName = $keyProperty['payload'];
                        } else {
                            throw new Exception(sprintf('@key only supports string payloads %s was given', $astNode['props']['@key']['type']));
                        }
                    }
                    $nodeFusion = $fusionName . ' = ' . AfxService::astNodeToFusion($astNode['payload'], $indentation . AfxService::INDENTATION . AfxService::INDENTATION);
                    break;
            }
            if ($nodeFusion) {
                $index ++;
                $fusion .= $indentation . AfxService::INDENTATION . AfxService::INDENTATION . $nodeFusion;
            }
        }

        $fusion .= $indentation . AfxService::INDENTATION . '}' . PHP_EOL;

        return $fusion;
    }
}
