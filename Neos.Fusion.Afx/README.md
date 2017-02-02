# PackageFactory.AtomicFusion.AFX

> JSX inspired compact syntax for Neos.Fusion

This package provides a fusion preprocessor that expands a compact xml-ish syntax to pure fusion code. This allows
to write compact components that do'nt need a seperate template file and enables unplanned extensibility for the defined 
prototypes because the genrated fusion code can be overwritten and controlled from the outside if needed. 

## WARNING

This is highly experimental and will very likely change in the future. 
Additionally this requires the following MR to actually work. 
https://github.com/neos/neos-development-collection/pull/1410

## Usage

With this package the following fusion code

```
prototype(PackageFactory.AtomicFusion.AFX:Example) < prototype(PackageFactory.AtomicFusion:Component) {

    title = 'title text'
    subtitle = 'subtitle line'
    imageUri = 'https://dummyimage.com/600x400/000/fff'
    
    #
    # All lines following the AFX:: are read as xml and 
    # converted to the fusion code below at parse time
    # 
    renderer = afx`
       <div>
         <h1 @key="headline" class="headline">${props.title}</h1>
         <h2 @key="subheadline" class="subheadline" @if.hasSubtitle="${props.subtitle ? true : false}">${props.subtitle}</h1>
         <PackageFactory.AtomicFusion.AFX:Image @key="image" uri="${props.imageUri}" />
       </div>`

}
```

Will be transpiled, parsed and then cached as the following fusion-code

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
                content = ${props.title}
                attributes.class = 'headline'
            }
            subheadline = Neos.Fusion:Tag {
                tagName = 'h2'
                content = ${props.subtitle}
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

## Rules


### HTML-Tags (Tags without Namespace)

HTML-Tags are converted to `Neos.Fusion:Tag` Objects. All attributes are rendered as attributes and the content/children 
are renderd as content.
 
The following html: 
```
<h1 class="headline" @if.hasHeadline="{props.headline ? true : false}">${props.headline}</h1>
```
Will be transformed into this fusion:
```
Neos.Fusion:Tag {
    tagName = 'h1'
    attributes.class = 'headline'
    content = ${props.headline}
    @if.hasHeadline="{props.headline ? true : false}
}
``` 

### Fusion-Object-Tags (namespaced Tags)

All namespaced-tags are interpreted as prototype-names and all attributes are passed as top-level fusion-properties.

The following html: 
```
<Vendor.Site:Prototype type="headline" @if.hasHeadline="{props.headline ? true : false}" >${props.headline}</Vendor.Site:Prototype>
```
Will be transformed into this fusion:
```
Vendor.Site:Prototype {
    type = 'headline'
    renderer = ${props.headline}
    @if.hasHeadline="{props.headline ? true : false}
}
```

### Meta-Attributes

In general all meta-attributes start with an @-sign. 

The `@kchildren`-attribute defined the property that is used to render the content/children of the current tag into. For
html-tags the default is `content` while for Fusion-Object-Tags the default is `renderer`.

The `@key`-attribute can be used to define the property name of an item among its siblings. If no key is defined the key `item_x` is used with x startting at 1.

All other meta attributes are directly added to the generated prototype and can be used for @if or @process statements. 

## License

see [LICENSE file](LICENSE)
