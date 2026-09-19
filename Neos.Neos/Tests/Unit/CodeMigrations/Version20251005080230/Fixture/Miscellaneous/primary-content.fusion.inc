prototype(Neos.Neos:PrimaryContent) {
  myArticle {
    condition = ${q(node).is('[instanceof My.Site:Article]')}
    renderer = My.Site:ArticleRenderer
  }
}

content = Neos.Neos:PrimaryContent {
  nodePath = 'main'
}
-----
// TODO Neos.Neos-20251005080230 "Neos.Neos:PrimaryContent" has been removed without a complete replacement. We replaced all usages with "Neos.Neos:ContentCollection" but not the prototype definition. Please check the replacements and if you have overridden the "Neos.Neos:PrimaryContent" prototype and rewrite it for your needs.
prototype(Neos.Neos:PrimaryContent) {
  myArticle {
    condition = ${q(node).is('[instanceof My.Site:Article]')}
    renderer = My.Site:ArticleRenderer
  }
}

content = Neos.Neos:ContentCollection {
  nodePath = 'main'
}
