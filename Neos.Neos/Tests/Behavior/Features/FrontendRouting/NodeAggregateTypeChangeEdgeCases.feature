@flowEntities @contentrepository
Feature: Test cases for node aggregate type change edge cases

  Scenario: Create a (non-document) node with uri path segment, change the node aggregate's type to be a document and expect it to now have a uri path segment
  # Note: this is not some esoteric fantasy case, it happens when you rename a node type, change a node's type to the new one and replay the documentUriPathProjection
    Given using the following content dimensions:
      | Identifier | Values                       | Generalizations                         |
      | example    | general,source,peer,peerSpec | peerSpec->peer->general,source->general |
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example":"general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | originDimensionSpacePoint | nodeName | parentNodeAggregateId  | nodeTypeName       | initialPropertyValues         |
      | shernode-homes    | {"example":"general"}     | site     | lady-eleonode-rootford | Neos.Neos:Site     | {}                            |
      | anthony-destinode | {"example":"general"}     | anthony  | shernode-homes         | Neos.Neos:Document | {"uriPathSegment": "anthony"} |
      | berta-destinode   | {"example":"general"}     | berta    | shernode-homes         | Neos.Neos:Document | {"uriPathSegment": "berta"}   |
    # Set up our test subject document
    # We do this via event since it must be of an unknown node type to simulate the replay
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                                  |
      | workspaceName               | "live"                                                                                 |
      | contentStreamId             | "cs-identifier"                                                                        |
      | nodeAggregateId             | "nody-mc-nodeface"                                                                     |
      | nodeTypeName                | "Neos.Neos:SomethingThatOnceWasADocument"                                              |
      | originDimensionSpacePoint   | {"example":"general"}                                                                  |
      | coveredDimensionSpacePoints | [{"example":"general"},{"example":"source"},{"example":"peer"},{"example":"peerSpec"}] |
      | parentNodeAggregateId       | "anthony-destinode"                                                                    |
      | nodeName                    | "document"                                                                             |
      | nodeAggregateClassification | "regular"                                                                              |
      | initialPropertyValues       | {"uriPathSegment": {"type": "string", "value": "nody"}}                                |
    And the event NodeAggregateWasMoved was published with payload:
    # Let's move the node partially to add a little brainfuck
    # We do this via event to bypass constraint checks on the still non-existent node type
      | Key                           | Value                                                                                                                                         |
      | workspaceName                 | "live"                                                                                                                                        |
      | contentStreamId               | "cs-identifier"                                                                                                                               |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                            |
      | newParentNodeAggregateId      | "berta-destinode"                                                                                                                             |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example": "peer"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example": "peerSpec"},"nodeAggregateId": null}] |
    And the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | newNodeTypeName | "Neos.Neos:Document" |
      | strategy        | "happypath"          |

    Then I expect the documenturipath table to contain exactly:
      # general: 033e5de7b423f45bb4f5a09f73af839e
      # source: 65901ded4f068dac14ad0dce4f459b29
      # peer: fbe53ddc3305685fbb4dbf529f283a0e
      # peerSpec: 2ca4fae2f65267c94c85602df0cbb728
      | dimensionspacepointhash            | uripath        | nodeaggregateidpath                                                        | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | "033e5de7b423f45bb4f5a09f73af839e" | ""             | "lady-eleonode-rootford"                                                   | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "2ca4fae2f65267c94c85602df0cbb728" | ""             | "lady-eleonode-rootford"                                                   | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""             | "lady-eleonode-rootford"                                                   | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | ""             | "lady-eleonode-rootford"                                                   | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "033e5de7b423f45bb4f5a09f73af839e" | ""             | "lady-eleonode-rootford/shernode-homes"                                    | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "2ca4fae2f65267c94c85602df0cbb728" | ""             | "lady-eleonode-rootford/shernode-homes"                                    | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "65901ded4f068dac14ad0dce4f459b29" | ""             | "lady-eleonode-rootford/shernode-homes"                                    | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | ""             | "lady-eleonode-rootford/shernode-homes"                                    | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "033e5de7b423f45bb4f5a09f73af839e" | "anthony"      | "lady-eleonode-rootford/shernode-homes/anthony-destinode"                  | "anthony-destinode"      | "shernode-homes"         | null                     | "berta-destinode"         | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "anthony"      | "lady-eleonode-rootford/shernode-homes/anthony-destinode"                  | "anthony-destinode"      | "shernode-homes"         | null                     | "berta-destinode"         | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "anthony"      | "lady-eleonode-rootford/shernode-homes/anthony-destinode"                  | "anthony-destinode"      | "shernode-homes"         | null                     | "berta-destinode"         | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "anthony"      | "lady-eleonode-rootford/shernode-homes/anthony-destinode"                  | "anthony-destinode"      | "shernode-homes"         | null                     | "berta-destinode"         | "Neos.Neos:Document" |
      | "033e5de7b423f45bb4f5a09f73af839e" | "anthony/nody" | "lady-eleonode-rootford/shernode-homes/anthony-destinode/nody-mc-nodeface" | "nody-mc-nodeface"       | "anthony-destinode"      | null                     | null                      | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "anthony/nody" | "lady-eleonode-rootford/shernode-homes/anthony-destinode/nody-mc-nodeface" | "nody-mc-nodeface"       | "anthony-destinode"      | null                     | null                      | "Neos.Neos:Document" |
      | "033e5de7b423f45bb4f5a09f73af839e" | "berta"        | "lady-eleonode-rootford/shernode-homes/berta-destinode"                    | "berta-destinode"        | "shernode-homes"         | "anthony-destinode"      | null                      | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "berta"        | "lady-eleonode-rootford/shernode-homes/berta-destinode"                    | "berta-destinode"        | "shernode-homes"         | "anthony-destinode"      | null                      | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "berta"        | "lady-eleonode-rootford/shernode-homes/berta-destinode"                    | "berta-destinode"        | "shernode-homes"         | "anthony-destinode"      | null                      | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "berta"        | "lady-eleonode-rootford/shernode-homes/berta-destinode"                    | "berta-destinode"        | "shernode-homes"         | "anthony-destinode"      | null                      | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "berta/nody"   | "lady-eleonode-rootford/shernode-homes/berta-destinode/nody-mc-nodeface"   | "nody-mc-nodeface"       | "berta-destinode"        | null                     | null                      | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "berta/nody"   | "lady-eleonode-rootford/shernode-homes/berta-destinode/nody-mc-nodeface"   | "nody-mc-nodeface"       | "berta-destinode"        | null                     | null                      | "Neos.Neos:Document" |
