@flowEntities @contentrepository
Feature: Test cases for node variation edge cases

  Scenario: Create peer variant of node to dimension space point with specializations
    Given using the following content dimensions:
      | Identifier | Values               | Generalizations |
      | example    | source,peer,peerSpec | peerSpec->peer  |
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
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                    |
      | nodeAggregateId           | "shernode-homes"         |
      | nodeTypeName              | "Neos.Neos:Site"         |
      | parentNodeAggregateId     | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"example":"source"}     |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "shernode-homes"     |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | originDimensionSpacePoint | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Set up our test subject document
      | nody-mc-nodeface     | {"example":"source"}      | document          | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "nody"}     |
    # Let's create some siblings, both in source and target, to check ordering
      | elder-mc-nodeface    | {"example":"source"}      | elder-document    | shernode-homes        | nody-mc-nodeface                 | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | youngest-mc-nodeface | {"example":"source"}      | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |
      | eldest-mc-nodeface   | {"example":"peer"}        | eldest-document   | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "nody-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                |
      | nodeAggregateId | "elder-mc-nodeface"  |
      | sourceOrigin    | {"example":"source"} |
      | targetOrigin    | {"example":"peer"}   |
    And the graph projection is fully up to date
    # Complete the sibling set with a node in the target DSP between the middle and last node
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                         |
      | nodeAggregateId           | "younger-mc-nodeface"         |
      | nodeTypeName              | "Neos.Neos:Document"          |
      | parentNodeAggregateId     | "shernode-homes"              |
      | originDimensionSpacePoint | {"example":"peer"}            |
      | nodeName                  | "younger-document"            |
      | initialPropertyValues     | {"uriPathSegment": "younger"} |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "youngest-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"peer"}     |
    And the graph projection is fully up to date

    Then I expect the documenturipath table to contain exactly:
      # source: 65901ded4f068dac14ad0dce4f459b29
      # peer: fbe53ddc3305685fbb4dbf529f283a0e
      # peerSpec: 2ca4fae2f65267c94c85602df0cbb728
      | dimensionspacepointhash            | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | "2ca4fae2f65267c94c85602df0cbb728" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "2ca4fae2f65267c94c85602df0cbb728" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "2ca4fae2f65267c94c85602df0cbb728" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | null                     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "2ca4fae2f65267c94c85602df0cbb728" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "nody-mc-nodeface"       | null                      | "Neos.Neos:Document" |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |

  Scenario: Create generalization of node to dimension space point with further generalization and specializations
    Given using the following content dimensions:
      | Identifier | Values                              | Generalizations                                                   |
      | example    | rootGeneral, general, source, specB | source -> general -> rootGeneral, specB -> general -> rootGeneral |
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
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                     |
      | nodeAggregateId           | "shernode-homes"          |
      | nodeTypeName              | "Neos.Neos:Site"          |
      | parentNodeAggregateId     | "lady-eleonode-rootford"  |
      | originDimensionSpacePoint | {"example":"rootGeneral"} |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | originDimensionSpacePoint | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Let's create some siblings, both in source and target, to check ordering
      | eldest-mc-nodeface   | {"example":"general"}     | eldest-document   | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |
      | nody-mc-nodeface     | {"example":"source"}      | document          | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "nody"}     |
      | elder-mc-nodeface    | {"example":"source"}      | elder-document    | shernode-homes        | nody-mc-nodeface                 | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | younger-mc-nodeface  | {"example":"general"}     | younger-document  | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "younger"}  |
      | youngest-mc-nodeface | {"example":"source"}      | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "elder-mc-nodeface"   |
      | sourceOrigin    | {"example":"source"}  |
      | targetOrigin    | {"example":"general"} |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "youngest-mc-nodeface" |
      | sourceOrigin    | {"example":"source"}   |
      | targetOrigin    | {"example":"general"}  |
    And the graph projection is fully up to date

    Then I expect the documenturipath table to contain exactly:
      # general: 033e5de7b423f45bb4f5a09f73af839e
      # source: 65901ded4f068dac14ad0dce4f459b29
      # sourceB: 9447118dcac98e2912f66a3387f057a0
      # rootGeneral: f02657442189da118ab86d745842894e
      | dimensionspacepointhash            | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | "033e5de7b423f45bb4f5a09f73af839e" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "9447118dcac98e2912f66a3387f057a0" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "f02657442189da118ab86d745842894e" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "033e5de7b423f45bb4f5a09f73af839e" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "9447118dcac98e2912f66a3387f057a0" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "f02657442189da118ab86d745842894e" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "033e5de7b423f45bb4f5a09f73af839e" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "9447118dcac98e2912f66a3387f057a0" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "033e5de7b423f45bb4f5a09f73af839e" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "9447118dcac98e2912f66a3387f057a0" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "033e5de7b423f45bb4f5a09f73af839e" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "9447118dcac98e2912f66a3387f057a0" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "033e5de7b423f45bb4f5a09f73af839e" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "9447118dcac98e2912f66a3387f057a0" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "033e5de7b423f45bb4f5a09f73af839e" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | "9447118dcac98e2912f66a3387f057a0" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |

  Scenario: Delete the node in a virtual specialization and then create the node in that specialization, forcing the edges to be recreated
    Given using the following content dimensions:
      | Identifier | Values                 | Generalizations            |
      | example    | source, spec, leafSpec | leafSpec -> spec -> source |
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
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                    |
      | nodeAggregateId           | "shernode-homes"         |
      | nodeTypeName              | "Neos.Neos:Site"         |
      | parentNodeAggregateId     | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"example":"source"}     |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId      | nodeName          | parentNodeAggregateId | succeedingSiblingNodeAggregateId | nodeTypeName       | initialPropertyValues          |
    # Let's create our test subject...
      | nody-mc-nodeface     | document          | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "nody"}     |
    # ...and add some siblings to check orderings. Also, everything gets better with siblings.
      | elder-mc-nodeface    | elder-document    | shernode-homes        | nody-mc-nodeface                 | Neos.Neos:Document | {"uriPathSegment": "elder"}    |
      | eldest-mc-nodeface   | eldest-document   | shernode-homes        | elder-mc-nodeface                | Neos.Neos:Document | {"uriPathSegment": "eldest"}   |
      | younger-mc-nodeface  | younger-document  | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "younger"}  |
      | youngest-mc-nodeface | youngest-document | shernode-homes        |                                  | Neos.Neos:Document | {"uriPathSegment": "youngest"} |
    And the command RemoveNodeAggregate is executed with payload:
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

    Then I expect the documenturipath table to contain exactly:
      # source: 65901ded4f068dac14ad0dce4f459b29
      # spec: 9a723c057afa02982dae9d0b541739be
      # leafSpec: c60c44685475d0e2e4f2b964e6158ce2
      | dimensionspacepointhash            | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "9a723c057afa02982dae9d0b541739be" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "c60c44685475d0e2e4f2b964e6158ce2" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "9a723c057afa02982dae9d0b541739be" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "c60c44685475d0e2e4f2b964e6158ce2" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "65901ded4f068dac14ad0dce4f459b29" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "elder"    | "lady-eleonode-rootford/shernode-homes/elder-mc-nodeface"    | "elder-mc-nodeface"      | "shernode-homes"         | "eldest-mc-nodeface"     | "nody-mc-nodeface"        | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "eldest"   | "lady-eleonode-rootford/shernode-homes/eldest-mc-nodeface"   | "eldest-mc-nodeface"     | "shernode-homes"         | null                     | "elder-mc-nodeface"       | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "nody"     | "lady-eleonode-rootford/shernode-homes/nody-mc-nodeface"     | "nody-mc-nodeface"       | "shernode-homes"         | "elder-mc-nodeface"      | "younger-mc-nodeface"     | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "younger"  | "lady-eleonode-rootford/shernode-homes/younger-mc-nodeface"  | "younger-mc-nodeface"    | "shernode-homes"         | "nody-mc-nodeface"       | "youngest-mc-nodeface"    | "Neos.Neos:Document" |
      | "65901ded4f068dac14ad0dce4f459b29" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | "9a723c057afa02982dae9d0b541739be" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
      | "c60c44685475d0e2e4f2b964e6158ce2" | "youngest" | "lady-eleonode-rootford/shernode-homes/youngest-mc-nodeface" | "youngest-mc-nodeface"   | "shernode-homes"         | "younger-mc-nodeface"    | null                      | "Neos.Neos:Document" |
