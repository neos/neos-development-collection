@flowEntities
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
      | nodeName                  | "site"                   |
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
      | initialPropertyValues     | {"uriPathSegment": "just"}|
      | workspaceName             | "myworkspace"             |
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "myworkspace"              |
      | nodesToPublish            | ["justsomepage"] |

    Then I expect the documenturipath table to contain exactly:
      | dimensionspacepointhash    | uripath | nodeaggregateidpath                                  | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename         |
      | hash{"example":"peerSpec"} | ""      | "lady-eleonode-rootford"                             | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"source"}   | ""      | "lady-eleonode-rootford"                             | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"peer"}     | ""      | "lady-eleonode-rootford"                             | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"    |
      | hash{"example":"source"}   | ""      | "lady-eleonode-rootford/shernode-homes"              | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Site"     |
      | hash{"example":"source"}   | "just"  | "lady-eleonode-rootford/shernode-homes/justsomepage" | "justsomepage"           | "shernode-homes"         | null                     | null                      | "Neos.Neos:Document" |
