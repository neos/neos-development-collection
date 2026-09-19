prototype(Neos.Neos:Test) < prototype(Neos.Fusion:Component) {
  node = ${node}

  # The name of the property which should be accessed
  property = null

  renderer = Neos.Fusion:Case {
    editable {
      condition = ${props.node.context.inBackend && props.node.context.currentRenderingMode.edit}
      renderer = Neos.Fusion:Tag {
        content = ${q(props.node).property(props.property)}
      }
    }

    notEditable {
      condition = true
      renderer = ${q(props.node).property(props.property)}
      @process.convertUris = Neos.Neos:ConvertUris
    }
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 11: You very likely need to rewrite "VARIABLE.context.inBackend" to "renderingMode.isEdit". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 11: You very likely need to rewrite "VARIABLE.context.currentRenderingMode..." to "renderingMode...". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Neos:Test) < prototype(Neos.Fusion:Component) {
  node = ${node}

  # The name of the property which should be accessed
  property = null

  renderer = Neos.Fusion:Case {
    editable {
      condition = ${props.node.context.inBackend && props.node.context.currentRenderingMode.edit}
      renderer = Neos.Fusion:Tag {
        content = ${q(props.node).property(props.property)}
      }
    }

    notEditable {
      condition = true
      renderer = ${q(props.node).property(props.property)}
      @process.convertUris = Neos.Neos:ConvertUris
    }
  }
}
