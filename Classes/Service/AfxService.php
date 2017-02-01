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
        // replace namespaces to make the xml parsable
        $afxCode = preg_replace_callback(
            "/(<\/?)([A-Za-z\\\\.]+:[A-Za-z\\\\.]+)/us",
            function($matches) {
                return $matches[1] . AfxService::fusionNameToTagName($matches[2]);
            },
            $afxCode
        );

        // replace meta attributes with a prefix to make the xml parsable
        $afxCode = preg_replace("/(?<= )@([a-zA-Z0-9\\.]+)(?=\\=\")/u", "AFX__$1", $afxCode);

        $xml = new \DOMDocument();
        $xml->loadXML($afxCode);
        $fusion = AfxService::xmlNodeToFusion($xml->documentElement, $indentation);

        return $fusion;
    }

    /**
     * @param \DOMNode $xml
     * @param string $indentation
     * @return string
     */
    protected static function xmlNodeToFusion (\DOMNode $xml, $indentation = '')
    {
        if ($xml->nodeType === XML_TEXT_NODE) {
            return AfxService::valueToFusionAssignment($xml->nodeValue) . PHP_EOL;
        }

        $tagName = $xml->nodeName;


        if (substr($tagName, 0, strlen(AfxService::PREFIX)) == AfxService::PREFIX){
            // fusion object
            $fusion = AfxService::tagNameToFusionName($tagName) . ' {' . PHP_EOL;
            $childrenPropertyName = 'renderer';
            $attributePrefix = '';
        } else {
            // tag
            $fusion = 'Neos.Fusion:Tag {' . PHP_EOL;
            $fusion .= $indentation . AfxService::INDENTATION .'tagName = \'' .  $tagName . '\'' . PHP_EOL;
            $childrenPropertyName = 'content';
            $attributePrefix = 'attributes.';
        }

        // attributes
        if ($xml->hasAttributes()) {
            foreach ($xml->attributes as $attribute) {
                if ($attribute->name == AfxService::PREFIX . 'key') {
                    // this is handled later
                } elseif ($attribute->name == AfxService::PREFIX . 'children') {
                    $childrenPropertyName = $attribute->value;
                } elseif (substr($attribute->name, 0, strlen(AfxService::PREFIX)) == AfxService::PREFIX) {
                    $fusion .= $indentation . AfxService::INDENTATION . '@' . substr($attribute->name, strlen(AfxService::PREFIX)) . ' = ' . AfxService::valueToFusionAssignment($attribute->value) . PHP_EOL;
                } else {
                    $fusion .= $indentation . AfxService::INDENTATION . $attributePrefix . $attribute->name . ' = ' . AfxService::valueToFusionAssignment($attribute->value) . PHP_EOL;
                }
            }
        }

        // children
        if ($xml->hasChildNodes()) {
            $fusion .= $indentation . AfxService::INDENTATION . $childrenPropertyName . ' = ' . AfxService::xmlNodeListToFusion($xml->childNodes, $indentation);
        }

        $fusion .= $indentation . '}' . PHP_EOL;

        return $fusion;
    }

    /**
     * @param \DOMNodeList $xml
     * @param string $indentation
     * @return string
     */
    protected static function xmlNodeListToFusion (\DOMNodeList $xmlList, $indentation = '')
    {
        $fusion = '';

        // filter the items that can be converted to code
        $renderableItems = [];
        for ($i = 0; $i < $xmlList->length; $i++) {
            $xmlListItem = $xmlList->item($i);
            switch ($xmlListItem->nodeType) {
                case XML_ELEMENT_NODE:
                case XML_TEXT_NODE:
                    $renderableItems[] = $xmlListItem;
                    break;
            }
        }


        if (count($renderableItems) == 1 && $renderableItems[0]->nodeType === XML_TEXT_NODE) {
            $fusion .=  AfxService::valueToFusionAssignment($xmlList[0]->nodeValue) . PHP_EOL;
        } else {
            $fusion .= 'Neos.Fusion:Array {' . PHP_EOL;
            $index = 1;
            for ($i = 0; $i < $xmlList->length; $i++) {
                $child = $xmlList->item($i);

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
                $fusion .= $indentation . AfxService::INDENTATION . AfxService::INDENTATION . $fusionName . ' = ' . AfxService::xmlNodeToFusion($child, $indentation . AfxService::INDENTATION . AfxService::INDENTATION);
                $index ++;
            }
            $fusion .= $indentation . AfxService::INDENTATION . '}' . PHP_EOL;
        }
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