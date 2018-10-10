<?php
namespace Neos\Fusion\Afx\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Neos\Fusion\Afx\Service\AfxService;
use Neos\Fusion\Afx\Exception;

class AfxServiceTest extends TestCase
{

    /**
     * @test
     */
    public function emptyCodeConvertedToEmptyFusion()
    {
        $afxCode = '';
        $expectedFusion = <<<'EOF'
''
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function whitepaceCodeIsConvertedToEmptyFusion()
    {
        $afxCode = '   ';
        $expectedFusion = <<<'EOF'
''
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function htmlTagsAreConvertedToFusionTags()
    {
        $afxCode = '<h1></h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function htmlTagsWithSpaceContentAreConvertedToFusionTags()
    {
        $afxCode = '<h1>   </h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = '   '
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function htmlTagsWithIgnoredContentAreConvertedToFusionTags()
    {
        $afxCode = '<h1>
   
</h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = ''
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function multipleHtmlTagsAreConvertedToFusionArray()
    {
        $afxCode = '<h1></h1><p></p><p></p>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Array {
    item_1 = Neos.Fusion:Tag {
        tagName = 'h1'
    }
    item_2 = Neos.Fusion:Tag {
        tagName = 'p'
    }
    item_3 = Neos.Fusion:Tag {
        tagName = 'p'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function multipleHtmlTagsAndTextsAreConvertedToFusionArray()
    {
        $afxCode = 'Foo<h1></h1>Bar<p></p>Baz';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Array {
    item_1 = 'Foo'
    item_2 = Neos.Fusion:Tag {
        tagName = 'h1'
    }
    item_3 = 'Bar'
    item_4 = Neos.Fusion:Tag {
        tagName = 'p'
    }
    item_5 = 'Baz'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function whitepaceAroundAfxIsIgnored()
    {
        $afxCode = '  <h1></h1><p></p>  ';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Array {
    item_1 = Neos.Fusion:Tag {
        tagName = 'h1'
    }
    item_2 = Neos.Fusion:Tag {
        tagName = 'p'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }
    /**
     * @test
     */
    public function multipleHtmlTagsAreConvertedToFusionTags()
    {
        $afxCode = '<h1></h1><p></p><p></p>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Array {
    item_1 = Neos.Fusion:Tag {
        tagName = 'h1'
    }
    item_2 = Neos.Fusion:Tag {
        tagName = 'p'
    }
    item_3 = Neos.Fusion:Tag {
        tagName = 'p'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function whitespacesAndNewlinesAroundAfxCodeAreIgnored()
    {
        $afxCode = '   
              <h1></h1>
        ';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function htmlTagsAreConvertedToSelfClosingFusionTags()
    {
        $afxCode = '<h1/>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    selfClosingTag = true
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function attributesInHtmlTagsAreConvertedToTagAttributes()
    {
        $afxCode = '<h1 content="bar" class="fooo" />';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    selfClosingTag = true
    attributes.content = 'bar'
    attributes.class = 'fooo'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function sindgleQuotesAreEscapedInAttributesAndChildren()
    {
        $afxCode = '<h1 class="foo\'bar" >foo\'bar</h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    attributes.class = 'foo\'bar'
    content = 'foo\'bar'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function fusionTagsAreConvertedToFusionObjects()
    {
        $afxCode = '<Vendor.Site:Prototype/>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function attributesInFusionTagsAreConvertedToFusionPropertiesOrEelExpressions()
    {
        $afxCode = '<Vendor.Site:Prototype foo="bar" baz="bam" />';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    foo = 'bar'
    baz = 'bam'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function metaAttributesOfFusionObjectTagsAreConvertedToFusionProperties()
    {
        $afxCode = '<Vendor.Site:Prototype @position="start" @if.hasTitle={title} />';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    @position = 'start'
    @if.hasTitle = ${title}
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function metaAttributesOfHtmlTagsAreConvertedToFusionProperties()
    {
        $afxCode = '<div @position="start" @if.hasTitle={title} ></div>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'div'
    @position = 'start'
    @if.hasTitle = ${title}
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function contentOfHtmlTagsIsRenderedAsFusionContent()
    {
        $afxCode = '<h1>Fooo</h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = 'Fooo'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function contentOfFusionTagsIsRenderedAsFusionRenderer()
    {
        $afxCode = '<Vendor.Site:Prototype>Fooo</Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    content = 'Fooo'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function textContentOfHtmlTagsIsRenderedAsConfiguredChildrenProperty()
    {
        $afxCode = '<Vendor.Site:Prototype @children="children">Fooo</Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    children = 'Fooo'
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function eelContentOfHtmlTagsIsRenderedAsConfiguredChildrenProperty()
    {
        $afxCode = '<Vendor.Site:Prototype @children="children">{eelExpression()}</Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    children = ${eelExpression()}
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function complexChildrenAreRenderedAsArray()
    {
        $afxCode = '<h1><strong>foo</strong><i>bar</i></h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = Neos.Fusion:Tag {
            tagName = 'strong'
            content = 'foo'
        }
        item_2 = Neos.Fusion:Tag {
            tagName = 'i'
            content = 'bar'
        }
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function complexChildrenAreRenderedAsArrayIgnoringWhitespaceInBetween()
    {
        $afxCode = <<<'EOF'
<h1>
    
    <strong>foo</strong>
    
    <i>bar</i>
        
</h1>
EOF;

        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = Neos.Fusion:Tag {
            tagName = 'strong'
            content = 'foo'
        }
        item_2 = Neos.Fusion:Tag {
            tagName = 'i'
            content = 'bar'
        }
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function complexChildrenAreRenderedAsArrayWithOptionalKeys()
    {
        $afxCode = '<h1><strong @key="key_one">foo</strong><i @key="key_two">bar</i></h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        key_one = Neos.Fusion:Tag {
            tagName = 'strong'
            content = 'foo'
        }
        key_two = Neos.Fusion:Tag {
            tagName = 'i'
            content = 'bar'
        }
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function complexChildrenAreCanContainTagsAnsValues()
    {
        $afxCode = '<h1>a string<strong>a tag</strong>{eelExpression()}</h1>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = 'a string'
        item_2 = Neos.Fusion:Tag {
            tagName = 'strong'
            content = 'a tag'
        }
        item_3 = ${eelExpression()}
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function childrenWithPathesAreRendered()
    {
        $afxCode = '<Vendor.Site:Prototype><strong @path="namedProp">foo</strong></Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    namedProp = Neos.Fusion:Tag {
        tagName = 'strong'
        content = 'foo'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function multipleChildrenWithPathesAreRendered()
    {
        $afxCode = '<Vendor.Site:Prototype><strong @path="propOne">foo</strong><Vendor.Site:Prototype @path="propTwo">bar</Vendor.Site:Prototype></Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    propOne = Neos.Fusion:Tag {
        tagName = 'strong'
        content = 'foo'
    }
    propTwo = Vendor.Site:Prototype {
        content = 'bar'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function childrenWithPathesAreCompatibleWithContentChildren()
    {
        $afxCode = '<Vendor.Site:Prototype><strong @path="propOne">foo</strong><Vendor.Site:Prototype @path="propTwo">bar</Vendor.Site:Prototype><div>a tag</div><div>another tag</div></Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    propOne = Neos.Fusion:Tag {
        tagName = 'strong'
        content = 'foo'
    }
    propTwo = Vendor.Site:Prototype {
        content = 'bar'
    }
    content = Neos.Fusion:Array {
        item_1 = Neos.Fusion:Tag {
            tagName = 'div'
            content = 'a tag'
        }
        item_2 = Neos.Fusion:Tag {
            tagName = 'div'
            content = 'another tag'
        }
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function childrenWithDeepPathesAreSupported()
    {
        $afxCode = '<Vendor.Site:Prototype><strong @path="a.fusion.path">foo</strong><Vendor.Site:Prototype @path="another.fusion.path">bar</Vendor.Site:Prototype></Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    a.fusion.path = Neos.Fusion:Tag {
        tagName = 'strong'
        content = 'foo'
    }
    another.fusion.path = Vendor.Site:Prototype {
        content = 'bar'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function spacesNewLinesAndSpacesAroundAreIgnored()
    {
        $afxCode = '<h1>
            {eelExpression1}
            {eelExpression2}
            {eelExpression3}
        </h1>';

        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = ${eelExpression1}
        item_2 = ${eelExpression2}
        item_3 = ${eelExpression3}
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function spacesInsideALineArePreserved()
    {
        $afxCode = '<h1>
            {eelExpression1} {eelExpression2}
        </h1>';

        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = ${eelExpression1}
        item_2 = ' '
        item_3 = ${eelExpression2}
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function spacesInsideALineArePreservedAlsoForStrings()
    {
        $afxCode = '<h1>
            String {eelExpression} String
        </h1>';

        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = 'String '
        item_2 = ${eelExpression}
        item_3 = ' String'
    }
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function spreadsAreEvaluetedForFusionObjectTags()
    {
        $afxCode = '<Vendor.Site:Prototype {...contextValue} />';

        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    @spread.spread_1 = ${contextValue}
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }

    /**
     * @test
     */
    public function spreadsAreEvaluetedForHtmlTags()
    {
        $afxCode = '<h1 {...contextValue} />';

        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    selfClosingTag = true
    attributes.@spread.spread_1 = ${contextValue}
}
EOF;
        $this->assertEquals($expectedFusion, AfxService::convertAfxToFusion($afxCode));
    }


    /**
     * @test
     * @expectedException \PackageFactory\Afx\Exception
     */
    public function unclosedTagsRaisesException()
    {
        $afxCode = '<h1>';
        AfxService::convertAfxToFusion($afxCode);
    }

    /**
     * @test
     * @expectedException \PackageFactory\Afx\Exception
     */
    public function unclosedAttributeRaisesException()
    {
        $afxCode = '<h1 foo="bar />';
        AfxService::convertAfxToFusion($afxCode);
    }

    /**
     * @test
     * @expectedException \PackageFactory\Afx\Exception
     */
    public function unclosedExpressionRaisesException()
    {
        $afxCode = '<h1 foo={"123" />';
        AfxService::convertAfxToFusion($afxCode);
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Exception\AfxException
     */
    public function childPathAnnotationWithExpressionRaisesException()
    {
        $afxCode = '<div><span @path={expression} /></div>';
        AfxService::convertAfxToFusion($afxCode);
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Exception\AfxException
     */
    public function keyAnnotationWithExpressionRaisesException()
    {
        $afxCode = '<div><span @key={expression} /><span/></div>';
        AfxService::convertAfxToFusion($afxCode);
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Afx\Exception\AfxException
     */
    public function childrenAnnotationWithExpressionRaisesException()
    {
        $afxCode = '<div @children={expression} ><span/></div>';
        AfxService::convertAfxToFusion($afxCode);
    }


}
