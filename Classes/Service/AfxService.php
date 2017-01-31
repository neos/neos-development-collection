<?php
namespace PackageFactory\AtomicFusion\AFX\Service;

use Neos\Flow\Annotations as Flow;

/**
 * Class AfxService
 * @package PackageFactory\AtomicFusion\AFX\Service
 * @Flow\Scope("singleton")
 */
class AfxService
{

    const INDENTATION = '    ';
    
    const PREFIX = 'AFX__';

    /**
     * @var string $afxCode the AFX code that is converted
     * @var string $indentation Indentation to start with
     * @return string
     */
    public static function convertAfxToFusion($afxCode, $indentation = '')
    {
        // replace namespaces to make the xml-parsable
        $afxCode = preg_replace_callback(
            "/(<\/?)([A-Za-z\\\\.]+:[A-Za-z\\\\.]+)/us",
            function($matches) {
                return $matches[1] . AfxService::fusionNameToTagName($matches[2]);
            },
            $afxCode
        );
        // replace @key withj PREFIX__key
        $afxCode = str_replace('@key', AfxService::PREFIX . 'key', $afxCode);

        // replace @children withj PREFIX__children
        $afxCode = str_replace('@children', AfxService::PREFIX . 'children', $afxCode);
//\Neos\Flow\var_dump($afxCode);
        // $xml = new \SimpleXMLElement($afxCode);
        $xml = new \DOMDocument();
        $xml->loadXML($afxCode);
        $fusion = AfxService::xmlToFusion($xml->documentElement, $indentation);
//\Neos\Flow\var_dump($fusion);
// die();
        return $fusion;
    }

    /**
     * @param \DOMNode $xml
     * @param string $indentation
     * @return string
     */
    protected static function xmlToFusion (\DOMNode $xml, $indentation = '')
    {
        if ($xml->nodeType === XML_TEXT_NODE) {
            return AfxService::valueToFusionAssignment($xml->nodeValue) . PHP_EOL;
        }

        $tagName = $xml->nodeName;


        if (substr($tagName, 0, strlen(AfxService::PREFIX)) == AfxService::PREFIX){
            // fusion object
            $fusion = AfxService::tagNameToFusionName($tagName) . ' {' . PHP_EOL;
        } else {
            // tag
            $fusion = 'Neos.Fusion:Tag {' . PHP_EOL;
            $fusion .= $indentation . AfxService::INDENTATION .'tagName = \'' .  $tagName . '\'' . PHP_EOL;
        }

        $childrenProperty = 'content';

        // attributes
        if ($xml->hasAttributes()) {
            foreach ($xml->attributes as $attribute) {
                switch ($attribute->name) {
                    case AfxService::PREFIX . 'key':
                        break;
                    case AfxService::PREFIX . 'chsildren':
                        $childrenProperty = $attribute->value;
                        break;
                    default:
                        $fusion .= $indentation . AfxService::INDENTATION . $attribute->name . ' = ' . AfxService::valueToFusionAssignment($attribute->value) . PHP_EOL;
                        break;
                }
            }
        }

        // children
        if ($xml->hasChildNodes()) {
            if ($xml->childNodes->length == 1 && $xml->childNodes[0]->nodeType === XML_TEXT_NODE && (trim($xml->childNodes[0]->nodeValue) == false)) {
                $fusion .= $indentation . AfxService::INDENTATION . $childrenProperty . ' = ' . AfxService::valueToFusionAssignment($xml->childNodes[0]->nodeValue) . PHP_EOL;
            } else {
                $fusion .= $indentation . AfxService::INDENTATION . $childrenProperty . ' = Neos.Fusion:Array {' . PHP_EOL;
                $index = 1;
                for ($i = 0; $i < $xml->childNodes->length; $i++) {
                    $child = $xml->childNodes->item($i);

                    // ignore empty text children
                    if ($child->nodeType === XML_TEXT_NODE && (trim($child->nodeValue) == false) ) {
                        continue;
                    }

                    if ($child->hasAttributes() && $child->hasAttribute(AfxService::PREFIX . 'key')) {
                        $fusionName = $child->getAttribute(AfxService::PREFIX . 'key');
                    } else {
                        $fusionName = 'item_' . $index;
                    }

                   // $fusionName = 'item_' . $index;
                    $fusion .= $indentation . AfxService::INDENTATION . AfxService::INDENTATION . $fusionName . ' = ' . AfxService::xmlToFusion($child, $indentation . AfxService::INDENTATION . AfxService::INDENTATION);
                    $index ++;
                }
                $fusion .= $indentation . AfxService::INDENTATION . '}' . PHP_EOL;
            }
        }

        $fusion .= $indentation . '}' . PHP_EOL;

        return $fusion;
    }

    /**
     * Create a fusion value assignment
     * 
     * @param string $value
     * @return string
     */
    protected static function valueToFusionAssignment($value) {
        if (substr($value, 0, 2) == '${') {
            return $value;
        } else {
            return '\'' . $value . '\'';
        }
    }

    /**
     * Convert a Fusion Prototype Name into a valid XML Tag Name with a prefix
     *
     * @param string $value
     * @return string
     */
    protected static function fusionNameToTagName($name)
    {
        return AfxService::PREFIX . str_replace(['.',':'],['_','__'], $name);
    }

    /**
     * Convert a XML Tag Name with a prefix back to fusion prototype name
     *
     * @param string $value
     * @return string
     */
    protected static function tagNameToFusionName($name)
    {
        return str_replace(['__','_'], [':','.'], substr($name, strlen(AfxService::PREFIX)));
    }


}