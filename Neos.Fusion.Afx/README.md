# PackageFactory.AtomicFusion.AFX

> JSX inspired compact syntax for Neos.Fusion

This package provides a fusion preprocessor that expands a compact xml-ish syntax to pure fusion code. This allows
to write compact components that do'nt need a seperate template file and enables unplanned extensibility for the defined 
prototypes because the genrated fusion code can be overwritten and controlled from the outside if needed. 

HINT: To use this package you have to install https://github.com/PackageFactory/afx aswell until the package becomes available
via packagist.

## WARNING

This is highly experimental and will very likely change in the future. 

## Usage

With this package the following fusion code

```
prototype(PackageFactory.AtomicFusion.AFX:Example) < prototype(PackageFactory.AtomicFusion:Component) {

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
         <PackageFactory.AtomicFusion.AFX:Image @key="image" uri={props.imageUri} />
       </div>
    `
}
```

Will be transpiled, parsed and then cached and evaluated as the following fusion-code

```
prototype(PackageFactory.AtomicFusion.AFX:Example) < prototype(PackageFactory.AtomicFusion:Component) {

    title = 'title text'
    subtitle = 'subtitle line'
    imageUri = 'https://dummyimage.com/600x400/000/fff'
    
    renderer = Neos.Fusion:Tag {
        tagName = 'div'
        content = Neos.Fusion:Array {
            headline = Neos.Fusion:Tag {
                tagName = 'h1'
                content = Neos.Fusion:Array {
                    1 = ${props.title}
                }
                attributes.class = 'headline'
            }
            subheadline = Neos.Fusion:Tag {
                tagName = 'h2'
                content = Neos.Fusion:Array {
                    1 = ${props.subtitle}
                }
                attributes.subheadline = 'subheadline'
                @if.hasSubtitle = ${props.subtitle ? true : false}
            }
            image = PackageFactory.AtomicFusion.AFX:Image {
                uri = ${props.imageUri}
            }
        }
    }
}
```

## CLI - Usage

The package contains the following cli-commands.

1. `./flow afx:show` - Show the afx detection and expansion to pure fusion, this is useful for learning and understanding.
1. `./flow afx:eject` - Expand afx in fusion code to pure fusion, this is usefull before removing the afx package. 

## AFX Language Rules


### HTML-Tags (Tags without Namespace)

HTML-Tags are converted to `Neos.Fusion:Tag` Objects. All attributes except `content` are rendered as attributes and the content/children 
are directly rendered as property names. All other attributes are rendered as tag-attributes.
 
The following html: 
```
<h1 class="headline" @if.hasHeadline={props.headline ? true : false}>{props.headline}</h1>
```
Will be transformed into this fusion:
```
Neos.Fusion:Tag {
    tagName = 'h1'
    attributes.class = 'headline'
    content = Neos.Fusion:Array {
        1 = ${props.headline}
    }
    @if.hasHeadline="{props.headline ? true : false}
}
``` 

The following example defines the tag-content as a single expression instead of an fusion array: 
```
<h1 class="headline" content={props.headline} />
```
Will be transformed into this fusion:
```
Neos.Fusion:Tag {
    tagName = 'h1'
    attributes.class = 'headline'
    content = ${props.headline}
}
``` 

If a tag is self closing and has no content it will be rendered as self closing fusion-tag.  
```
<br/>
```
Will be transformed into this fusion:
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
<Vendor.Site:Prototype type="headline" @if.hasHeadline={props.headline ? true : false} >{props.headline}</Vendor.Site:Prototype>
```
Will be transformed into this fusion:
```
Vendor.Site:Prototype {
    type = 'headline'
    renderer = Neos.Fusion:Array {
        1 = ${props.headline}
    }
    @if.hasHeadline="{props.headline ? true : false}
}
```

### Meta-Attributes

In general all meta-attributes start with an @-sign. 

The `@children`-attribute defined the property that is used to render the content/children of the current tag into. 
The default property name for the children is `content`.

The `@key`-attribute can be used to define the property name of an item among its siblings. If no key is defined the index is used starting at 1.

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
is parsed as: 
```
Neos.Fusion:Tag {
	tagName = 'h1'
	contents = Neos.Fusion:Array {
		1 = ${'eelExpression 1'}
		2 = ${'eelExpression 2'}   
	}
}
```

2. **Spaces between Elements on a single line are considered meaningful and are preserved**
 
```
<h1>
	{'eelExpression 1'} {'eelExpression 2'}
</h1>
```
is parsed as: 
```
Neos.Fusion:Tag {
	tagName = 'h1'
	contents = Neos.Fusion:Array {
		1 = ${'eelExpression 1'}
		2 = ' '
		2 = ${'eelExpression 2'}   
	}
}
```
  

## License

see [LICENSE file](LICENSE)
