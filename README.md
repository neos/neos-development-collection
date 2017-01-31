# PackageFactory.AtomicFusion.ASX

> JSX inspired syntax for Neos.Fusion

## WARNING

This is highly experimental and will very likely change in the future. 
Additionally this requires the following MR to actually work. 
https://github.com/neos/neos-development-collection/pull/1410

## Usage

With this package the following fusion code

```
prototype(PackageFactory.AtomicFusion.AFX:Example) < prototype(PackageFactory.AtomicFusion:Component) {
    title = 'foo'
    imageUri = 'https://dummyimage.com/600x400/000/fff'
    
    renderer = AFX::
       <div>
         <h1 @key="headline" >${props.title}</h1>
         <PackageFactory.AtomicFusion.AFX:Image @key="image" uri="${props.imageUri}" />
       </div>

}
```

will be interpreted as equivalent to the following fusion code

```
prototype(PackageFactory.AtomicFusion.AFX:Example) < prototype(PackageFactory.AtomicFusion:Component) {
    title = 'foo'
    imageUri = 'https://dummyimage.com/600x400/000/fff'
    
    renderer = Neos.Fusion:Tag {
        tagName = 'div'
        content = Neos.Fusion:Array {
            headline = Neos.Fusion:Tag {
                tagName = 'h1'
                content = ${props.title}
            }
            image = PackageFactory.AtomicFusion.AFX:Image {
                uri = ${props.imageUri}
            }
        }
    }
}
```

## License

see [LICENSE file](LICENSE)
