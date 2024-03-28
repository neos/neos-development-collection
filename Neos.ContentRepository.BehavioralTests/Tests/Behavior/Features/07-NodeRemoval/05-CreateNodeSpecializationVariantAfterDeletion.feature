@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node specialization

  As a user of the CR I want to create a copy of a node within an aggregate to a more specialized dimension space point.
  Now that we are able to delete, the order of siblings comes into play as no longer fall naturally into place due to the fallback mechanism.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                 | Generalizations            |
      | example    | source, spec, leafSpec | leafSpec -> spec -> source |
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
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

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

  Scenario: Delete the node in a virtual specialization and then create the node in that specialization, forcing the edges to be recreated
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example":"spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | contentStreamId | "cs-identifier"      |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"spec"}   |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 12 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"spec"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"source"} to exist in the content graph

    When I am in the active content stream of workspace "live"
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
    And I expect this node aggregate to cover dimension space points [{"example":"source"}]

    And I expect the node aggregate "younger-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    And I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"source"},{"example":"spec"},{"example":"leafSpec"}]

    When I am in dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 8 nodes
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
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"spec"}    |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-document   | cs-identifier;nodewyn-tetherton;{"example":"spec"}        |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                  |
      | tethered-document | cs-identifier;nodimer-tetherton;{"example":"spec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"spec"}
    And I expect this node to have no succeeding siblings
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

    When I am in dimension space point {"example":"leafSpec"}
    Then I expect the subgraph projection to consist of exactly 8 nodes
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
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;younger-mc-nodeface;{"example":"spec"}    |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"source"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;nody-mc-nodeface;{"example":"spec"}       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"spec"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                         |
      | tethered-document   | cs-identifier;nodewyn-tetherton;{"example":"spec"}        |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-mc-nodeface;{"example":"source"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"source"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"source"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"source"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-document" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                  |
      | tethered-document | cs-identifier;nodimer-tetherton;{"example":"spec"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimer-tetherton;{"example":"spec"}
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
