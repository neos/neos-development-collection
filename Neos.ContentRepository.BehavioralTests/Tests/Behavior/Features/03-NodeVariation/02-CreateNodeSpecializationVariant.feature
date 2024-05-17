@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node specialization

  As a user of the CR I want to create a copy of a node within an aggregate to a more specialized dimension space point.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                 | Generalizations        |
      | example    | source, spec, leafSpec | leafSpec->spec->source |
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
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName            | parentNodeAggregateId  | succeedingSiblingNodeAggregateId | nodeTypeName                                | tetheredDescendantNodeAggregateIds                                                         |
    # We have to add another node since root node aggregates do not support variation, and while we're at it let's add two levels of tethered children to check recursion
      | nody-mc-nodeface       | document            | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:Document     | {"tethered-node": "nodewyn-tetherton", "tethered-node/tethered-leaf": "nodimer-tetherton"} |
    # Now let's add some siblings to check orderings. Also, everything gets better with siblings.
      | elder-mc-nodeface      | elder-document      | lady-eleonode-rootford | nody-mc-nodeface                 | Neos.ContentRepository.Testing:LeafDocument | {}                                                                                         |
      | eldest-mc-nodeface     | eldest-document     | lady-eleonode-rootford | elder-mc-nodeface                | Neos.ContentRepository.Testing:LeafDocument | {}                                                                                         |
      | younger-mc-nodeface    | younger-document    | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:LeafDocument | {}                                                                                         |
      | youngest-mc-nodeface   | youngest-document   | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:LeafDocument | {}                                                                                         |
      | invariable-mc-nodeface | invariable-document | nody-mc-nodeface       |                                  | Neos.ContentRepository.Testing:LeafDocument | {}                                                                                         |

  Scenario: check the tree state before the specialization
    When I am in workspace "live" and dimension space point {"example":"source"}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 3 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | eldest-mc-nodeface     |
      | 1     | elder-mc-nodeface      |
      | 1     | nody-mc-nodeface       |
      | 2     | nodewyn-tetherton      |
      | 3     | nodimer-tetherton      |
      | 2     | invariable-mc-nodeface |
      | 1     | younger-mc-nodeface    |
      | 1     | youngest-mc-nodeface   |

  Scenario: Create specialization of node to dimension space point with further specializations
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"spec"}   |
    Then I expect exactly 13 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                    |
      | contentStreamId        | "cs-identifier"                                                                                                                                                             |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                                          |
      | sourceOrigin           | {"example":"source"}                                                                                                                                                        |
      | specializationOrigin   | {"example":"spec"}                                                                                                                                                          |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"younger-mc-nodeface"},{"dimensionSpacePoint":{"example":"leafSpec"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 11 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                          |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                   |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                               |
      | sourceOrigin           | {"example":"source"}                                                                                                                                                              |
      | specializationOrigin   | {"example":"spec"}                                                                                                                                                                |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"leafSpec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
    And event at index 12 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                  |
      | contentStreamId        | "cs-identifier"                                                                                                                           |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                       |
      | sourceOrigin           | {"example":"source"}                                                                                                                      |
      | specializationOrigin   | {"example":"spec"}                                                                                                                        |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"leafSpec"},"nodeAggregateId":null}] |

    Then I expect the graph projection to consist of exactly 12 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"spec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"spec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"spec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "younger-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 3 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | eldest-mc-nodeface     |
      | 1     | elder-mc-nodeface      |
      | 1     | nody-mc-nodeface       |
      | 2     | nodewyn-tetherton      |
      | 3     | nodimer-tetherton      |
      | 2     | invariable-mc-nodeface |
      | 1     | younger-mc-nodeface    |
      | 1     | youngest-mc-nodeface   |
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                       |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"source"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"source"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-node       | cs-identifier;nodewyn-tetherton;{"example":"spec"}        |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-node" to lead to node cs-identifier;nodewyn-tetherton;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                  |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"spec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-node/tethered-leaf" to lead to node cs-identifier;nodimer-tetherton;{"example":"spec"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"source"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-mc-nodeface;{"example":"source"} |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}      |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"leafSpec"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                       |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"source"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"source"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-node       | cs-identifier;nodewyn-tetherton;{"example":"spec"}        |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-node" to lead to node cs-identifier;nodewyn-tetherton;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                  |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"spec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-node/tethered-leaf" to lead to node cs-identifier;nodimer-tetherton;{"example":"spec"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"source"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-mc-nodeface;{"example":"source"} |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}      |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Create specialization of node to dimension space point with specializations that are partially occupied
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "nody-mc-nodeface"     |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"leafSpec"} |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"spec"}   |
    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 13 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                             |
      | contentStreamId        | "cs-identifier"                                                                      |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                   |
      | sourceOrigin           | {"example":"source"}                                                                 |
      | specializationOrigin   | {"example":"spec"}                                                                   |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"younger-mc-nodeface"}] |
    And event at index 14 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                |
      | contentStreamId        | "cs-identifier"                                                                         |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                     |
      | sourceOrigin           | {"example":"source"}                                                                    |
      | specializationOrigin   | {"example":"spec"}                                                                      |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
    And event at index 15 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                            |
      | contentStreamId        | "cs-identifier"                                                     |
      | nodeAggregateId        | "nodimer-tetherton"                                                 |
      | sourceOrigin           | {"example":"source"}                                                |
      | specializationOrigin   | {"example":"spec"}                                                  |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |

    Then I expect the graph projection to consist of exactly 15 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"leafSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"leafSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"leafSpec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "younger-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                       |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"source"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"source"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-node       | cs-identifier;nodewyn-tetherton;{"example":"spec"}        |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-node" to lead to node cs-identifier;nodewyn-tetherton;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                  |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"spec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-node/tethered-leaf" to lead to node cs-identifier;nodimer-tetherton;{"example":"spec"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"source"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-mc-nodeface;{"example":"source"} |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}      |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"leafSpec"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                       |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"source"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"leafSpec"}   |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"source"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"leafSpec"}   |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"leafSpec"}   |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"leafSpec"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-node       | cs-identifier;nodewyn-tetherton;{"example":"leafSpec"}    |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-node" to lead to node cs-identifier;nodewyn-tetherton;{"example":"leafSpec"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                      |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"leafSpec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-node/tethered-leaf" to lead to node cs-identifier;nodimer-tetherton;{"example":"leafSpec"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"source"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nody-mc-nodeface;{"example":"leafSpec"} |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-mc-nodeface;{"example":"source"} |
      | cs-identifier;nody-mc-nodeface;{"example":"leafSpec"}  |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"}  |
    And I expect this node to have no succeeding siblings

    # @todo test based on NodeSpecializationVariantWasCreated ({"market":"CH", "language":"DE"})
    # and VirtualNodeVariantWasRemoved ({"market":"CH", "language":"gsw"})
    # to test that explicitly removed virtual variants are not implicitly created again
