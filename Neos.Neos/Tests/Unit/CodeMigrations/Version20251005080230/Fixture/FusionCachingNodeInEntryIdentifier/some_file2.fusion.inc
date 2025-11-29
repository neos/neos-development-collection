prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {
    @cache {
      entryIdentifier {
        foo = ${Neos.Caching.entryIdentifierForNode(node)}
      }
    }
    @cache.entryIdentifier.foo2 = ${Neos.Caching.entryIdentifierForNode(documentNode)}
    @cache {
      entryIdentifier.foo3 = ${Neos.Caching.entryIdentifierForNode(site)}
      entryIdentifier.foo4 = ${someOtherObject}
    }
  }
}
-----
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {
    @cache {
      entryIdentifier {
        foo = ${Neos.Caching.entryIdentifierForNode(node)}
      }
    }
    @cache.entryIdentifier.foo2 = ${Neos.Caching.entryIdentifierForNode(documentNode)}
    @cache {
      entryIdentifier.foo3 = ${Neos.Caching.entryIdentifierForNode(site)}
      entryIdentifier.foo4 = ${someOtherObject}
    }
  }
}
