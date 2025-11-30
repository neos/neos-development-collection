@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node specialization
  As a user of the CR I want to create a variant of a node within an aggregate
  to a more specialized dimension space point
  and assign that variant to a different parent
  * before the first of its new siblings
  * before the first of its new siblings - which does not exist in all variants
  * before one of its new siblings, wich is partially the first
  * before one of its new siblings, which is not the first
  * before one of its new siblings, which is not the first and does not exist in all variants
  * before one of its new siblings, which is the last and does not exist in all variants
  * after the last of its new siblings
  * after the last of its new siblings, which does not exist in all variants
  * after one of its new siblings, which is partially the last
  * after one of its new siblings, which is not the last

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-node:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered':
      childNodes:
        tethered-leaf:
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    'Neos.ContentRepository.Testing:TetheredLeaf': []
    'Neos.ContentRepository.Testing:LeafDocument': []
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
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName          | originDimensionSpacePoint | nodeTypeName                                | parentNodeAggregateId      | tetheredDescendantNodeAggregateIds                                                         |
      | sir-david-nodenborough     | parent-document   | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | lady-eleonode-rootford     | {}                                                                                         |
      | eldest-mc-nodeface         | eldest-document   | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | sir-david-nodenborough     | {}                                                                                         |
      | elder-mc-nodeface          | elder-document    | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | sir-david-nodenborough     | {}                                                                                         |
      | younger-mc-nodeface        | younger-document  | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | sir-david-nodenborough     | {}                                                                                         |
      | youngest-mc-nodeface       | youngest-document | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | sir-david-nodenborough     | {}                                                                                         |
      | sir-nodeward-nodington-iii | esquire           | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | lady-eleonode-rootford     | {}                                                                                         |
      | nody-mc-nodeface           | document          | {"example": "general"}    | Neos.ContentRepository.Testing:Document     | sir-nodeward-nodington-iii | {"tethered-node": "nodewyn-tetherton", "tethered-node/tethered-leaf": "nodimer-tetherton"} |
      | invariable-mc-nodeface     | invariable        | {"example": "general"}    | Neos.ContentRepository.Testing:LeafDocument | nody-mc-nodeface           | {}                                                                                         |

  Scenario: Create specialization variant to a new parent before the first of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example":"spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                              | Value                    |
      | nodeAggregateId                  | "nody-mc-nodeface"       |
      | sourceOrigin                     | {"example":"general"}    |
      | targetOrigin                     | {"example":"source"}     |
      | parentNodeAggregateId            | "sir-david-nodenborough" |
      | succeedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                               |
      | contentStreamId        | "cs-identifier"                                                                                                                                                        |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                  |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                   |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"eldest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"elder-mc-nodeface"}] |
      | parentNodeAggregateId  | "sir-david-nodenborough"                                                                                                                                               |
    And event at index 14 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                        |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                 |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                             |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                           |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                            |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
      | parentNodeAggregateId  | null                                                                                                                                                                            |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | null                                                                                                                                    |

    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"general"} to exist in the content graph

    When I am in workspace "live"

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 10 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to no node
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Create specialization variant to a new parent before one of its new siblings, wich is partially the first
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example":"spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                              | Value                    |
      | nodeAggregateId                  | "nody-mc-nodeface"       |
      | sourceOrigin                     | {"example":"general"}    |
      | targetOrigin                     | {"example":"source"}     |
      | parentNodeAggregateId            | "sir-david-nodenborough" |
      | succeedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                              |
      | contentStreamId        | "cs-identifier"                                                                                                                                                       |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                                    |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                 |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                  |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"elder-mc-nodeface"}] |
      | parentNodeAggregateId  | "sir-david-nodenborough"                                                                                                                                              |
    And event at index 14 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                        |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                 |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                             |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                           |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                            |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
      | parentNodeAggregateId  | null                                                                                                                                                                            |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | null                                                                                                                                    |

    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"general"} to exist in the content graph

    When I am in workspace "live"

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 10 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to no node
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Create specialization variant to a new parent before one of its new siblings, which is not the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "elder-mc-nodeface"  |
      | coveredDimensionSpacePoint   | {"example":"spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    When the command CreateNodeVariant is executed with payload:
      | Key                              | Value                    |
      | nodeAggregateId                  | "nody-mc-nodeface"       |
      | sourceOrigin                     | {"example":"general"}    |
      | targetOrigin                     | {"example":"source"}     |
      | parentNodeAggregateId            | "sir-david-nodenborough" |
      | succeedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                                      |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"younger-mc-nodeface"}] |
      | parentNodeAggregateId  | "sir-david-nodenborough"                                                                                                                                                |
    And event at index 14 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                        |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                 |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                             |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                           |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                            |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
      | parentNodeAggregateId  | null                                                                                                                                                                            |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | null                                                                                                                                    |

    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"general"} to exist in the content graph

    When I am in workspace "live"
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 10 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Create specialization variant to a new parent before one of its new siblings, which is the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example":"spec"}     |
      | nodeVariantSelectionStrategy | "allSpecializations"   |

    When the command CreateNodeVariant is executed with payload:
      | Key                              | Value                    |
      | nodeAggregateId                  | "nody-mc-nodeface"       |
      | sourceOrigin                     | {"example":"general"}    |
      | targetOrigin                     | {"example":"source"}     |
      | parentNodeAggregateId            | "sir-david-nodenborough" |
      | succeedingSiblingNodeAggregateId | "youngest-mc-nodeface"   |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                                           |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                        |
      | sourceOrigin           | {"example":"general"}                                                                                                                                     |
      | specializationOrigin   | {"example":"source"}                                                                                                                                      |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | "sir-david-nodenborough"                                                                                                                                  |
    And event at index 14 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                        |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                 |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                             |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                           |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                            |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
      | parentNodeAggregateId  | null                                                                                                                                                                            |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | null                                                                                                                                    |

    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"general"} to exist in the content graph

    When I am in workspace "live"
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 10 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | elder-document   | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"source"}     |

    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |

    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"} |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}

    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to no node

    When I am in workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Create specialization variant to a new parent after one of its new siblings, which is partially the last
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example":"spec"}     |
      | nodeVariantSelectionStrategy | "allSpecializations"   |

    When the command CreateNodeVariant is executed with payload:
      | Key                             | Value                    |
      | nodeAggregateId                 | "nody-mc-nodeface"       |
      | sourceOrigin                    | {"example":"general"}    |
      | targetOrigin                    | {"example":"source"}     |
      | parentNodeAggregateId           | "sir-david-nodenborough" |
      | precedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                                           |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                        |
      | sourceOrigin           | {"example":"general"}                                                                                                                                     |
      | specializationOrigin   | {"example":"source"}                                                                                                                                      |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | "sir-david-nodenborough"                                                                                                                                  |
    And event at index 14 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                        |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                 |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                             |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                           |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                            |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
      | parentNodeAggregateId  | null                                                                                                                                                                            |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | null                                                                                                                                    |

    Then I expect the graph projection to consist of exactly 14 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"general"} to exist in the content graph

    When I am in workspace "live"

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"},{"example":"peer"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}

    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |

    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"general"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 10 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
      | 1     | sir-nodeward-nodington-iii |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name             | NodeDiscriminator                                       |
      | eldest-document  | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
      | elder-document   | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | younger-document | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | document         | cs-identifier;nody-mc-nodeface;{"example":"source"}     |

    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |

    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}

    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;nody-mc-nodeface;{"example":"source"} |

    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to no node

    When I am in workspace "live" and dimension space point {"example":"peer"}
    Then I expect the subgraph projection to consist of exactly 11 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | nodeAggregateId            |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | eldest-mc-nodeface         |
      | 2     | elder-mc-nodeface          |
      | 2     | younger-mc-nodeface        |
      | 2     | youngest-mc-nodeface       |
      | 1     | sir-nodeward-nodington-iii |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimer-tetherton          |
      | 3     | invariable-mc-nodeface     |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                          |
      | tethered-node | cs-identifier;nodewyn-tetherton;{"example":"general"}      |
      | invariable    | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nodewyn-tetherton" to lead to node cs-identifier;nodewyn-tetherton;{"example":"general"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                     |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"general"} |
    And I expect node aggregate identifier "nodimer-tetherton" to lead to node cs-identifier;nodimer-tetherton;{"example":"general"}
    And I expect node aggregate identifier "invariable-mc-nodeface" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings
