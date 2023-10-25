@contentrepository @adapters=DoctrineDBAL
Feature: Individual node publication

  Publishing an individual node works

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content': {}
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        child1:
          type: 'Neos.ContentRepository.Testing:Content'
        child2:
          type: 'Neos.ContentRepository.Testing:Content'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | contentStreamId | "cs-identifier"               |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the graph projection is fully up to date

  ################
  # PUBLISHING
  ################
  Scenario: It is possible to publish a single node; and only this one is live.
    # create nodes in user WS
    Given I am in content stream "user-cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName | tetheredDescendantNodeAggregateIds               |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document | {} |
    And I remember NodeAggregateId of node "sir-david-nodenborough"s child "child2" as "child2Id"
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName | tetheredDescendantNodeAggregateIds               |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Content  | $child2Id             | nody     | {}                                                 |
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                               |
      | workspaceName                   | "user-test"                                                                                                         |
      | nodesToPublish                  | [{"contentStreamId": "user-cs-identifier", "dimensionSpacePoint": {}, "nodeAggregateId": "sir-david-nodenborough"}] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                                      |
    And the graph projection is fully up to date

    And I am in content stream "cs-identifier"

    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph

