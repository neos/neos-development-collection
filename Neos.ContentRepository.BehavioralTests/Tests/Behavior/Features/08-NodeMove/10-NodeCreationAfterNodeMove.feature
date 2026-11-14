Feature: Create a node aggregate underneath or next to a (partially) moved node

  As a user of the CR I want to create a node
  - underneath a partially moved node
  - before a partially moved node

  These are the test cases for moving nodes with content dimensions being involved, which is a lot more fun!

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName          |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | parent-document   |
      | eldest-mc-nodeface     | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | eldest-document   |
      | elder-mc-nodeface      | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | elder-document    |
      | younger-mc-nodeface    | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | younger-document  |
      | youngest-mc-nodeface   | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | youngest-document |
      | bustling-mc-nodeface   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | bustling-document |

  Scenario: Move a node aggregate partially to a new parent and create a new node underneath
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "bustling-mc-nodeface"   |
      | dimensionSpacePoint          | {"example": "general"}   |
      | newParentNodeAggregateId     | "sir-david-nodenborough" |
      | relationDistributionStrategy | "scatter"                |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "elder-mc-nodeface"    |
      | dimensionSpacePoint          | {"example": "general"} |
      | newParentNodeAggregateId     | "bustling-mc-nodeface" |
      | relationDistributionStrategy | "scatter"              |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "younger-mc-nodeface"  |
      | dimensionSpacePoint          | {"example": "general"} |
      | newParentNodeAggregateId     | "bustling-mc-nodeface" |
      | relationDistributionStrategy | "scatter"              |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | dimensionSpacePoint          | {"example": "general"} |
      | newParentNodeAggregateId     | "bustling-mc-nodeface" |
      | relationDistributionStrategy | "scatter"              |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "bustling-mc-nodeface"   |
      | dimensionSpacePoint          | {"example": "peer"}      |
      | newParentNodeAggregateId     | "sir-david-nodenborough" |
      | relationDistributionStrategy | "scatter"                |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | dimensionSpacePoint          | {"example": "peer"}    |
      | newParentNodeAggregateId     | "bustling-mc-nodeface" |
      | relationDistributionStrategy | "scatter"              |
    # Preceding sibling case: we provide the same preceding sibling as in the origin DSP, but with an additional, unrelated succeeding sibling
    # In this case, the created node should appear between the preceding and additional sibling.
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "elder-mc-nodeface"    |
      | dimensionSpacePoint          | {"example": "source"}  |
      | newParentNodeAggregateId     | "bustling-mc-nodeface" |
      | relationDistributionStrategy | "scatter"              |
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "additional-mc-nodeface"                  |
      | originDimensionSpacePoint | {"example": "source"}                     |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId     | "bustling-mc-nodeface"                    |

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                                     |
      | nodeAggregateId                  | "nody-mc-nodeface"                        |
      | originDimensionSpacePoint        | {"example": "general"}                    |
      | nodeTypeName                     | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId            | "bustling-mc-nodeface"                    |
      | succeedingSiblingNodeAggregateId | "younger-mc-nodeface"                     |

    Then I expect exactly 17 events to be published on stream "ContentStream:cs-identifier"
    And event at index 16 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                                 |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                          |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                                                                                                                                                                                                                                |
      | originDimensionSpacePoint     | {"example": "general"}                                                                                                                                                                                                                                                                                                                                   |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "younger-mc-nodeface"}, {"dimensionSpacePoint": {"example": "source"}, "nodeAggregateId": "additional-mc-nodeface"}, {"dimensionSpacePoint": {"example": "peer"}, "nodeAggregateId": "youngest-mc-nodeface"}, {"dimensionSpacePoint": {"example": "spec"}, "nodeAggregateId": null}] |
      | parentNodeAggregateId         | "bustling-mc-nodeface"                                                                                                                                                                                                                                                                                                                                   |
      | nodeAggregateClassification   | "regular"                                                                                                                                                                                                                                                                                                                                                |

  Scenario: Move a node aggregate partially to a new parent and create a new node next to it
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "bustling-mc-nodeface"   |
      | dimensionSpacePoint                 | {"example": "general"}   |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy        | "scatter"                |
    # the regular case: the succeeding sibling is a sibling and can be used as such
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "bustling-mc-nodeface"   |
      | dimensionSpacePoint          | {"example": "peer"}      |
      | newParentNodeAggregateId     | "sir-david-nodenborough" |
      | relationDistributionStrategy | "scatter"                |
    # the further succeeding sibling case (nothing to do): the succeeding sibling is no sibling in {"example": "source"},
    # but its further succeeding sibling is and can be used as such

    # the preceding sibling case: we replace the further succeeding siblings with a different one, so the closest preceding sibling is evaluated
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "younger-mc-nodeface"    |
      | dimensionSpacePoint          | {"example": "spec"}      |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
      | relationDistributionStrategy | "scatter"                |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "youngest-mc-nodeface"   |
      | dimensionSpacePoint          | {"example": "spec"}      |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
      | relationDistributionStrategy | "scatter"                |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                                     |
      | nodeAggregateId                  | "another-mc-nodeface"                     |
      | originDimensionSpacePoint        | {"example": "spec"}                       |
      | nodeTypeName                     | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId            | "sir-david-nodenborough"                  |
      | succeedingSiblingNodeAggregateId | null                                      |

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                                     |
      | nodeAggregateId                  | "nody-mc-nodeface"                        |
      | originDimensionSpacePoint        | {"example": "general"}                    |
      | nodeTypeName                     | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId            | "sir-david-nodenborough"                  |
      | succeedingSiblingNodeAggregateId | "bustling-mc-nodeface"                    |

    Then I expect exactly 14 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                                                |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                                         |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                                                                                                                                                                                                                                                                                                               |
      | originDimensionSpacePoint     | {"example": "general"}                                                                                                                                                                                                                                                                                                                                                  |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "bustling-mc-nodeface"}, {"dimensionSpacePoint": {"example": "source"}, "nodeAggregateId": "younger-mc-nodeface"}, {"dimensionSpacePoint": {"example": "peer"}, "nodeAggregateId": "bustling-mc-nodeface"}, {"dimensionSpacePoint": {"example": "spec"}, "nodeAggregateId": "another-mc-nodeface"}] |
      | parentNodeAggregateId         | "sir-david-nodenborough"                                                                                                                                                                                                                                                                                                                                                |
      | nodeAggregateClassification   | "regular"                                                                                                                                                                                                                                                                                                                                                               |
