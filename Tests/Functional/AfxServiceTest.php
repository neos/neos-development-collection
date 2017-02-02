<?php
namespace PackageFactory\AtomicFusion\AFX\Tests\Functional;

use Neos\Flow\Tests\FunctionalTestCase;
use PackageFactory\AtomicFusion\AFX\Service\AfxService;

class AfxServiceTest extends FunctionalTestCase
{

    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    /**
     * @test
     */
    public function htmlTagsAreConvertedToFusionTags()
    {
        $afxCode = '<h1/>';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
}

EOF;
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

    /**
     * @test
     */
    public function attributesInHtmlTagsAreConvertedToTagAttributes()
    {
        $afxCode = '<h1 class="fooo" />';
        $expectedFusion = <<<'EOF'
Neos.Fusion:Tag {
    tagName = 'h1'
    attributes.class = 'fooo'
}

EOF;
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

    /**
     * @test
     */
    public function metaAttributesAreConvertedToFusionProperties()
    {
        $afxCode = '<Vendor.Site:Prototype @position="start" @if.hasTitle="${title}" />';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    @position = 'start'
    @if.hasTitle = ${title}
}

EOF;
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

    /**
     * @test
     */
    public function contentOfFusionTagsIsRenderedAsFusionRenderer()
    {
        $afxCode = '<Vendor.Site:Prototype>Fooo</Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    renderer = 'Fooo'
}

EOF;
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

    /**
     * @test
     */
    public function eelContentOfHtmlTagsIsRenderedAsConfiguredChildrenProperty()
    {
        $afxCode = '<Vendor.Site:Prototype @children="children">${eelExpression()}</Vendor.Site:Prototype>';
        $expectedFusion = <<<'EOF'
Vendor.Site:Prototype {
    children = ${eelExpression()}
}

EOF;
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

    /**
     * @test
     */
    public function complexChildrenAreRenderedAsArrayIgnoringCommentsBetween()
    {
        $afxCode = <<<'EOF'
<h1>
    <!-- comments -->
    <strong>foo</strong>
    <!-- are -->
    <i>bar</i>
    <!-- ignored -->    
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

    /**
     * @test
     */
    public function complexChildrenAreCanContainTagsAnsValues()
    {
        $afxCode = '<h1>a string<strong>a tag</strong>${eelExpression()}</h1>';
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
        $this->assertEquals(AfxService::convertAfxToFusion($afxCode), $expectedFusion);
    }

}
