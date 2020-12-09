# Neos.Fusion.Afx

> JSX inspired compact syntax for Neos.Fusion

__This repository is a **read-only subsplit** of a package that is part of the Neos project (learn more on `www.neos.io <https://www.neos.io/>`_).__

This package provides a fusion preprocessor that expands a compact xml-ish syntax to pure fusion code. This allows
to write compact components that do'nt need a seperate template file and enables unplanned extensibility for the defined 
prototypes because the generated fusion-code can be overwritten and controlled from the outside if needed.

## Installation

Neos.Fusion.AFX is available via packagist. Just add `"neos/fusion-afx" : "~1.0.0"`
to the require-section of the composer.json or run `composer require neos/fusion-afx`.

__We use semantic-versioning so every breaking change will increase the major-version number.__

## Usage

With this package the following fusion code

```
prototype(Vendor.Site:Example) < prototype(Neos.Fusion:Component) {

    title = 'title text'
    subtitle = 'subtitle line'
    imageUri = 'https://dummyimage.com/600x400/000/fff'
    
    #
    # The code afx`...` is converted to the fusion code below at parse time. 
    # Attention: Currently there is no way to escape closing-backticks inside the Expression. 
    #
    renderer = afx`
       <div>
         <h1 @key="headline" class="headline">{props.title}</h1>
         <h2 @key="subheadline" class="subheadline" @if.hasSubtitle={props.subtitle ? true : false}>{props.subtitle}</h2>
         <Vendor.Site:Image @key="image" uri={props.imageUri} />
       </div>
    `
}
```

Will be transpiled, parsed and then cached and evaluated as beeing equivalent to the following fusion-code

```
prototype(Vendor.Site:Example) < prototype(Neos.Fusion:Component) {

    title = 'title text'
    subtitle = 'subtitle line'
    imageUri = 'https://dummyimage.com/600x400/000/fff'
    
    renderer = Neos.Fusion:Tag {
        tagName = 'div'
        content = Neos.Fusion:Array {
            headline = Neos.Fusion:Tag {
                tagName = 'h1'
                content = ${props.title}
                attributes.class = 'headline'
            }
            subheadline = Neos.Fusion:Tag {
                tagName = 'h2'
                content = ${props.subtitle}
                attributes.subheadline = 'subheadline'
                @if.hasSubtitle = ${props.subtitle ? true : false}
            }
            image = Vendor.Site:Image {
                uri = ${props.imageUri}
            }
        }
    }
}
```

## AFX Language Rules

All whitepaces around the outer elements are ignored. Whitepaces that are connected to a newline are considered irrelevant and are ignored.

### HTML-Tags (Tags without Namespace)

HTML-Tags are converted to `Neos.Fusion:Tag` Objects. All attributes of the afx-tag are rendered as tag-attributes.
 
The following html: 
```
<h1 class="headline" @if.hasHeadline={props.headline ? true : false}>{props.headline}</h1>
```
Is transpiled to:
```
Neos.Fusion:Tag {
    tagName = 'h1'
    attributes.class = 'headline'
    content = ${props.headline}
    @if.hasHeadline = ${props.headline ? true : false}
}
``` 

If a tag is self-closing and has no content it will be rendered as self closing fusion-tag:.  
```
<br/>
```
Is transpiled to:
```
Neos.Fusion:Tag {
    tagName = 'br'
    selfClosingTag = true
}
``` 

### Fusion-Object-Tags (namespaced Tags)

All namespaced-tags are interpreted as prototype-names and all attributes are passed as top-level fusion-properties.

The following html: 
```
<Vendor.Site:Prototype type="headline" @if.hasHeadline={props.headline ? true : false}>{props.headline}</Vendor.Site:Prototype>
```
Is transpiled as:
```
Vendor.Site:Prototype {
    type = 'headline'
    content = ${props.headline}
    @if.hasHeadline= ${props.headline ? true : false}
}
```

### Spread Syntax

To apply multiple properties to a fusion prototype with a single expression
afx supports the spread syntax from ES6:

```
<Vendor.Site:Component {...expression} />
```
Is transpiled as:
```
Vendor.Site:Component {
    @apply.spread_1 = ${expression}
}
```

Spreads can be combined with props and the order of the definition is
of props and spreads is preserved, spreads will override previously
defined props but are overwritten again by later props.

The order preserving combination of spreads and properties works by
only rendering the properties before the first spread as classic
fusion properties. Spreads and the following props are transpiled to
fusion `@apply` statements and are thus able to override all props but
and are evaluated in the order of definition.

```
<Vendor.Site:Component title="example" {...data} description="description" {...moreData} />
```
Is transpiled as:
```
Vendor.Site:Component {
    title = 'example'
    @apply.spread_1 = ${data}
    @apply.spread_2 = Neos.Fusion:RawArray {
        description = 'description'
    }
    @apply.spread_3 = ${moreData}

}
```

**This feature is based on the `@apply`-syntax of fusion and thus will only work in Neos > 4.2.**

### Tag-Children

The handling of child-nodes below an afx-node is differs based on the number of childNodes that are found.

#### Single tag-children

If a AFX-tag contains exactly one child this child is rendered directly into the `content`-attribute.  
The child is then interpreted as string, eel-expression, html- or fusion-object-tag. 

The following AFX-Code: 
 
```
<h1>{props.title}</h1>
``` 
Is transpiled as:
```
Neos.Fusion:Tag {
    tagName = 'h1'
    content = {props.title}
}
```

#### Multiple tag-children

If an AFX-tag contains more than one child the content is are rendered as `Neos.Fusion:Array` into the 
`content`-attribute. The children are interpreted as string, eel-expression, html- or fusion-object-tag. 
 
The following AFX-Code:
 
```
<h1>{props.title}: {props.subtitle}</h1>
``` 
Is transpiled as:
```
Neos.Fusion:Tag {
    tagName = 'h1'
    content = Neos.Fusion:Array {
        item_1 = {props.title}
        item_2 = ': '
        item_3 = ${props.subtitle}
    }
}
```

The `@key`-property of tag-children inside alters the name of the fusion-attribute to recive render the array-child into. 
If no `@key`-property is given `index_x` is used starting by `x=1`.

```
<Vendor.Site:Prototype @children="text">
    <h2 @key="title">{props.title}</h1> 
    <p @key="description">{props.description}</p>
</Vendor.Site:Prototype>
``` 
Is transpiled as:
```
Vendor.Site:Prototype {
    text = Neos.Fusion:Array { 
        title = Neos.Fusion:Tag {
            tagName = 'h2'
            content  = ${props.title}
        }
        description = Neos.Fusion:Tag {
            tagName = 'p'
            content  = ${props.description}
        }
    }
}
```

The `@path`-property of tag-children can be used to render a specific afx-child into the given fusion path
instead of beeing included into the `content`. This allows to render AFX children into different props and
to assign Fusion-prototypes to props.

```
<Vendor.Site:Prototype>
    <h2 @path="title">{props.title}</h1> 
    <p @path="description">{props.description}</p>
</Vendor.Site:Prototype>
``` 
Is transpiled as:
```
Vendor.Site:Prototype {
    title = Neos.Fusion:Tag {
        tagName = 'h2'
        content  = ${props.title}
    }
    description = Neos.Fusion:Tag {
        tagName = 'p'
        content  = ${props.description}
    }
}
```

### Meta-Attributes

In general all meta-attributes start with an @-sign. 

The `@path`-attribute can be used to render a child node directly into the given path below the parent Fusion:Object
instead of beeing included into the `content` property.

The `@children`-attribute defined the property that is used to render the content/children of the current tag into. 
The default property name for the children is `content`.

The `@key`-attribute can be used to define the property name of an item among its siblings if an array is rendered. 
If no `@key` is defined `index_x` is used starting at `x=1. 

Attention: `@path`, `@children` and `@key` only support string-values and no expressions.

All other meta attributes are directly added to the generated prototype and can be used for @if or @process statements. 

### Whitespace and Newlines
 
AFX is not html and makes some simplifications to the code to optimize the generated fusion and allow a structured notation 
of the component hierarchy. 

The following rules are applied for that:

1. **Newlines and Whitespace-Characters that are connected to a newline are considered irrelevant and are ignored**

```
<h1>
	{'eelExpression 1'}
	{'eelExpression 2'}
</h1>
```
Is transpiled as: 
```
Neos.Fusion:Tag {
	tagName = 'h1'
	contents = Neos.Fusion:Array {
		item_1 = ${'eelExpression 1'}
		item_2 = ${'eelExpression 2'}
	}
}
```

2. **Spaces between Elements on a single line are considered meaningful and are preserved**
 
```
<h1>
	{'eelExpression 1'} {'eelExpression 2'}
</h1>
```
Is transpiled as: 
```
Neos.Fusion:Tag {
	tagName = 'h1'
	contents = Neos.Fusion:Array {
		item_1 = ${'eelExpression 1'}
		item_2 = ' '
		item_3 = ${'eelExpression 2'}   
	}
}
```
### HTML Comments

AFX accepts html comments but they are not transpiled to any fusion. However if you are converting html to afx it is allowed to have comments inside and you can use comments for disabeling parts of your afx during testing.

```
foo<!-- comment -->bar
```
Is transpiled as: 
```
Neos.Fusion:Array {
    item_1 = 'foo'
    item_2 = 'bar'
}
```

## Examples

### Rendering of Collections with `Neos.Fusion:Collection`

For rendering of lists or menus a presentational-component usually will recieve arrays of 
preprocessed data as prop. To iterate over such an array the `Neos.Fusion:Collection` 
can be used in afx.

```
prototype(Vendor.Site:IterationExample) < prototype(Neos.Fusion:Component) {
    
    # array {[href:'http://www.example_1.com', title:'Title 1'], [href:'http://example_2.com', title:'Title 2']}
    items = null
    
    renderer = afx`
        <ul @if.has={props.items ? true : false}>
        <Neos.Fusion:Collection collection={props.items} itemName="item">
            <li @path='itemRenderer'>
                <Vendor.Site:LinkExample {...item} />
            </li>
        </Neos.Fusion:Collection>
        </ul>
    `
}
```

### Augmentation of Child-Components with `Neos.Fusion:Augmenter`

The `Neos.Fusion:Augmenter` can be used to add additional attributes to rendered content. 
This allows some rendering flexibility without extending the api of the component. This is a 
useful pattern to avoid unneeded tag-wrapping in cases where only additional classes are needed.

```
prototype(PackageFactory.AtomicFusion.AFX:SliderExample) < prototype(Packagefactory.AtomicFusion:Component) {
  images = ${[]}
  renderer = afx`
     <div class="slider">
        <Neos.Fusion:Collection collection={props.images} itemName="image" iterationName="iteration" @children="itemRenderer">
            <Neos.Fusion:Augmenter class="slider__slide" data-index={iteration.index}>
                <Vendor.Site:ImageExample {...image} />
            </Neos.Fusion:Augmenter>
        </Neos.Fusion:Collection>  
     </div>
  `
}
```

The example iterates over a list of images and uses the `Vendor.Site:ImageExample` to render each one 
while the `Neos.Fusion:Augmenter` adds a class- and data-attribute from outside.

## License

see [LICENSE file](LICENSE)
