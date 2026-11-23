Feature: Set node references with different scopes

  As a user of the CR I want to modify node references with different scopes.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        unscopedReference:
          type: reference
        unscopedReferences:
          type: references
        nodeScopedReference:
          type: reference
          scope: node
        nodeScopedReferences:
          type: references
          scope: node
        nodeAggregateScopedReference:
          type: reference
          scope: nodeAggregate
        nodeAggregateScopedReferences:
          type: references
          scope: nodeAggregate
        specializationsScopedReference:
          type: reference
          scope: specializations
        specializationsScopedReferences:
          type: references
          scope: specializations
      references:
        nodeScopedTypedReference:
          constraints:
            referenceTypes:
              '*': false
              'Neos.ContentRepository:ScopingExample': true
          scope: node
        nodeAggregateScopedTypedReference:
          constraints:
            referenceTypes:
              '*': false
              'Neos.ContentRepository:ScopingExample': true
          scope: nodeAggregate
        specializationsScopedTypedReference:
          constraints:
            referenceTypes:
              '*': false
              'Neos.ContentRepository:ScopingExample': true
          scope: specializations
    """
    And using the following reference types:
    """yaml
    'Neos.ContentRepository:Featureless': []
    'Neos.ContentRepository:ScopingExample':
      properties:
        nodeScopedProperty:
          type: string
          defaultValue: 'node scoped default'
        nodeAggregateScopedProperty:
          type: string
          defaultValue: 'node aggregate scoped default'
        specializationsScopedProperty:
          type: string
          defaultValue: 'specializations scoped default'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | parentNodeAggregateId  | nodeTypeName                                      |
      | source-nodandaise | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
      | anthony-destinode | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithReferences |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "source-nodandaise"   |
      | sourceOrigin    | {"example":"general"} |
      | targetOrigin    | {"example":"source"}  |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "source-nodandaise"   |
      | sourceOrigin    | {"example":"general"} |
      | targetOrigin    | {"example":"spec"}    |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "source-nodandaise"   |
      | sourceOrigin    | {"example":"general"} |
      | targetOrigin    | {"example":"peer"}    |

  Scenario: Set node references in separate commands
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                     |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                       |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                     |
      | references                      | [{"referenceName": "unscopedReference", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                      |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                        |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                      |
      | references                      | [{"referenceName": "unscopedReferences", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                       |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                         |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                       |
      | references                      | [{"referenceName": "nodeScopedReference", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                        |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                          |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                        |
      | references                      | [{"referenceName": "nodeScopedReferences", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                  |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                |
      | references                      | [{"referenceName": "nodeAggregateScopedReference", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                 |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                   |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                 |
      | references                      | [{"referenceName": "nodeAggregateScopedReferences", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                  |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                    |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                  |
      | references                      | [{"referenceName": "specializationsScopedReference", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                   |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                     |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                   |
      | references                      | [{"referenceName": "specializationsScopedReferences", "references": [{"target": "anthony-destinode"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                          |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                            |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                          |
      | references                      | [{"referenceName": "nodeScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                                   |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                                     |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                                   |
      | references                      | [{"referenceName": "nodeAggregateScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                                     |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                                       |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                                     |
      | references                      | [{"referenceName": "specializationsScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample"}]}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "general"}
    And I expect this node to have the following references:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "source"}
    And I expect this node to have the following references:
      | Name                                | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | nodeScopedReference                 | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedReferences                | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedTypedReference            | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | unscopedReference                   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | unscopedReferences                  | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                                | Node                                                  | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | nodeScopedReference                 | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedReferences                | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedTypedReference            | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | unscopedReference                   | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | unscopedReferences                  | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "spec"}
    And I expect this node to have the following references:
      | Name                                | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                                | Node                                                | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "peer"}
    And I expect this node to have the following references:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                              | Node                                                | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    # Scoped modification
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                          |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                            |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                          |
      | references                      | [{"referenceName": "nodeScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample", "properties": {"nodeScopedProperty": "node scoped override", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped override"}}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                                   |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                                     |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                                   |
      | references                      | [{"referenceName": "nodeAggregateScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample", "properties": {"nodeScopedProperty": "node scoped override", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped override"}}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                                     |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                                       |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                                     |
      | references                      | [{"referenceName": "specializationsScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample", "properties": {"nodeScopedProperty": "node scoped override", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped override"}}]}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "general"}
    And I expect this node to have the following references:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped default"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped default"} |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "source"}
    And I expect this node to have the following references:
      | Name                                | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped override", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped override"} |
      | nodeScopedReference                 | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedReferences                | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedTypedReference            | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped override", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped override"} |
      | specializationsScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped override", "nodeAggregateScopedProperty": "node aggregate scoped override", "specializationsScopedProperty": "specializations scoped override"} |
      | unscopedReference                   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | unscopedReferences                  | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                                | Node                                                  | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | nodeScopedReference                 | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedReferences                | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedTypedReference            | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | unscopedReference                   | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | unscopedReferences                  | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "spec"}
    And I expect this node to have the following references:
      | Name                                | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                                | Node                                                | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "peer"}
    And I expect this node to have the following references:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                              | Node                                                | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

  Scenario: Set node references in single command
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
      | sourceNodeAggregateId           | "source-nodandaise"                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
      | references                      | [{"referenceName": "unscopedReference", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "unscopedReferences", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "nodeScopedReference", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "nodeScopedReferences", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "nodeAggregateScopedReference", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "nodeAggregateScopedReferences", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "specializationsScopedReference", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "specializationsScopedReferences", "references": [{"target": "anthony-destinode"}]}, {"referenceName": "nodeScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample"}]}, {"referenceName": "nodeAggregateScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample"}]}, {"referenceName": "specializationsScopedTypedReference", "references": [{"target": "anthony-destinode", "referenceTypeName": "Neos.ContentRepository:ScopingExample"}]}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "general"}
    And I expect this node to have the following references:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;source-nodandaise;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "source"}
    And I expect this node to have the following references:
      | Name                                | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | nodeScopedReference                 | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedReferences                | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedTypedReference            | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | unscopedReference                   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | unscopedReferences                  | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                                | Node                                                  | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | nodeScopedReference                 | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedReferences                | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeScopedTypedReference            | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | unscopedReference                   | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | unscopedReferences                  | cs-identifier;source-nodandaise;{"example": "source"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "spec"}
    And I expect this node to have the following references:
      | Name                                | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                                | Node                                                | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference        | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences       | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference   | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
      | specializationsScopedReference      | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedReferences     | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | specializationsScopedTypedReference | cs-identifier;source-nodandaise;{"example": "spec"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"example": "peer"}
    And I expect this node to have the following references:
      | Name                              | Node                                                   | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;anthony-destinode;{"example": "general"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |

    And I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"example": "general"}
    And I expect this node to be referenced by:
      | Name                              | Node                                                | Type                                  | Properties                                                                                                                                                                       |
      | nodeAggregateScopedReference      | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedReferences     | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:Featureless    | null                                                                                                                                                                             |
      | nodeAggregateScopedTypedReference | cs-identifier;source-nodandaise;{"example": "peer"} | Neos.ContentRepository:ScopingExample | {"nodeScopedProperty": "node scoped default", "nodeAggregateScopedProperty": "node aggregate scoped default", "specializationsScopedProperty": "specializations scoped default"} |
