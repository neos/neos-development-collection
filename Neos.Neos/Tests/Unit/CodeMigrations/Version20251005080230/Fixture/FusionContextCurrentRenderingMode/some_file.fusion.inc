prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    nodeAttributes = ${node.context.currentRenderingMode.edit || node.context.currentRenderingMode.preview || node.context.currentRenderingMode.title || node.context.currentRenderingMode.name || node.context.currentRenderingMode.fusionPath || node.context.currentRenderingMode.options['foo']}
    siteAttributes = ${site.context.currentRenderingMode.edit || site.context.currentRenderingMode.preview || site.context.currentRenderingMode.title || site.context.currentRenderingMode.name || site.context.currentRenderingMode.fusionPath || site.context.currentRenderingMode.options['foo']}
    documentNodeAttributes = ${documentNode.context.currentRenderingMode.edit || documentNode.context.currentRenderingMode.preview || documentNode.context.currentRenderingMode.title || documentNode.context.currentRenderingMode.name || documentNode.context.currentRenderingMode.fusionPath || documentNode.context.currentRenderingMode.options['foo']}
    other = ${other.context.currentRenderingMode.edit || other.context.currentRenderingMode.preview || other.context.currentRenderingMode.title || other.context.currentRenderingMode.name || other.context.currentRenderingMode.fusionPath || other.context.currentRenderingMode.options['foo']}

    renderer = afx`
      <input
        type="checkbox"
        name={node.context.currentRenderingMode.name}
        value={node.context.currentRenderingMode.title}
        checked={node.context.currentRenderingMode.edit}
        {...node.context.currentRenderingMode.options}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 9: You very likely need to rewrite "VARIABLE.context.currentRenderingMode..." to "renderingMode...". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    nodeAttributes = ${renderingMode.isEdit || renderingMode.isPreview || renderingMode.title || renderingMode.name || renderingMode.fusionPath || renderingMode.options['foo']}
    siteAttributes = ${renderingMode.isEdit || renderingMode.isPreview || renderingMode.title || renderingMode.name || renderingMode.fusionPath || renderingMode.options['foo']}
    documentNodeAttributes = ${renderingMode.isEdit || renderingMode.isPreview || renderingMode.title || renderingMode.name || renderingMode.fusionPath || renderingMode.options['foo']}
    other = ${other.context.currentRenderingMode.edit || other.context.currentRenderingMode.preview || other.context.currentRenderingMode.title || other.context.currentRenderingMode.name || other.context.currentRenderingMode.fusionPath || other.context.currentRenderingMode.options['foo']}

    renderer = afx`
      <input
        type="checkbox"
        name={renderingMode.name}
        value={renderingMode.title}
        checked={renderingMode.isEdit}
        {...renderingMode.options}
      />
    `
  }
}
