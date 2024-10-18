@flowEntities @contentrepository
Feature: Test cases for partial publish to live and uri path generation

  Scenario: Create Document in another workspace and partially publish to live
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
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                    |
      | nodeAggregateId           | "shernode-homes"         |
      | nodeTypeName              | "Neos.Neos:Site"         |
      | parentNodeAggregateId     | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"example":"source"}     |
    And the command CreateWorkspace is executed with payload:
      | Key                       | Value                    |
      | workspaceName             | "myworkspace"            |
      | baseWorkspaceName         | "live"                   |
      | workspaceTitle            | "My Personal Workspace"  |
      | workspaceDescription      | ""                       |
      | newContentStreamId        | "cs-myworkspace"         |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                     |
      | nodeAggregateId           | "justsomepage"            |
      | nodeTypeName              | "Neos.Neos:Document"      |
      | parentNodeAggregateId     | "shernode-homes"          |
      | originDimensionSpacePoint | {"example":"source"}      |
      | properties                | {"uriPathSegment": "just"}|
      | workspaceName             | "myworkspace"             |
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "myworkspace"              |
      | nodesToPublish            | [{"nodeAggregateId": "justsomepage", "dimensionSpacePoint": {"example":"source"}}]       |

    Then I expect the documenturipath table to contain exactly:
      # source: 65901ded4f068dac14ad0dce4f459b29
      # spec: 9a723c057afa02982dae9d0b541739be
      # leafSpec: c60c44685475d0e2e4f2b964e6158ce2
      | dimensionspacepointhash            | uripath    | nodeaggregateidpath                                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | "2ca4fae2f65267c94c85602df0cbb728" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "fbe53ddc3305685fbb4dbf529f283a0e" | ""         | "lady-eleonode-rootford"                                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford/shernode-homes"                      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | "65901ded4f068dac14ad0dce4f459b29" | ""         | "lady-eleonode-rootford/shernode-homes/justsomepage"         | "justsomepage"           | "shernode-homes"         | null                     | null                      | "Neos.Neos:Document"     |
