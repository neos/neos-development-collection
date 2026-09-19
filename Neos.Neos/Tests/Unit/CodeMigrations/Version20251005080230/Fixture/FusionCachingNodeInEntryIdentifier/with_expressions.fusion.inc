prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {
  assignPlainNode = ${node}

  renderer = Neos.Fusion:Component {
    @cache {
      entryIdentifier {
        foo = ${node.workspaceName}
      }
    }
    @cache.entryIdentifier.foo2 = ${documentNode == site}
    @cache {
      entryIdentifier.foo3 = ${Some.Eel(site)}
      entryIdentifier.foo4 = ${someOtherObject}
    }
  }
}
-----
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {
  assignPlainNode = ${node}

  renderer = Neos.Fusion:Component {
    @cache {
      entryIdentifier {
        foo = ${node.workspaceName}
      }
    }
    @cache.entryIdentifier.foo2 = ${documentNode == site}
    @cache {
      entryIdentifier.foo3 = ${Some.Eel(site)}
      entryIdentifier.foo4 = ${someOtherObject}
    }
  }
}
