Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referencable node aggregate of a specific type with an initial node
  in a specific dimension space point.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph
  and its soon-to-be descendants

  Scenario: Publish simple node aggregate creation
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes':
      properties:
        defaultText:
          defaultValue: 'my default'
          type: string
        text:
          type: string
        nullText:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                               |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"} |
    And I am user identified by "initiating-user-identifier"

    When the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                                                 | initialPropertyValues    |
      | local         | sir-david-nodenborough     | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {"text": "initial text"} |
      | local         | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local-3       | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
    And I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partial publishing of simple node aggregate creation
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes':
      properties:
        defaultText:
          defaultValue: 'my default'
          type: string
        text:
          type: string
        nullText:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the current date and time is "2026-05-11T14:19:00+00:00"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                               |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"} |

    When the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId            | nodeName   | parentNodeAggregateId  | nodeTypeName                                                 | initialPropertyValues    |
      | local         | sir-david-nodenborough     | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {"text": "initial text"} |
      | local         | nody-mc-nodeface           | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local-3       | sir-nodeward-nodington-iii | esquire    | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                      |
      | workspaceName                   | "local"                    |
      | nodesToPublish                  | ["sir-david-nodenborough"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"    |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish node aggregate creation with succeeding sibling
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                                                                                                                                                                        |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"}                                                                                                                                          |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "nodeAggregateId": "sir-david-nodenborough", "nodeTypeName": "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes", "originDimensionSpacePoint": {}, "parentNodeAggregateId": "lady-eleonode-rootford", "nodeName": "node"} |
    And I am user identified by "initiating-user-identifier"

    When the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                                                 | originDimensionSpacePoint | succeedingSiblingNodeAggregateId | nodeName |
      | local         | sir-nodeward-nodington-iii | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                        | sir-david-nodenborough           | esquire  |
      | local-3       | nody-mc-nodeface           | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                        | sir-david-nodenborough           | child    |
    And I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partially publish node aggregate creation with succeeding sibling
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the current date and time is "2026-05-11T14:19:00+00:00"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                                                                                                                                                                        |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"}                                                                                                                                          |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "nodeAggregateId": "sir-david-nodenborough", "nodeTypeName": "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes", "originDimensionSpacePoint": {}, "parentNodeAggregateId": "lady-eleonode-rootford", "nodeName": "node"} |

    When the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                                                 | originDimensionSpacePoint | succeedingSiblingNodeAggregateId | nodeName |
      | local         | sir-nodeward-nodington-iii | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                        | sir-david-nodenborough           | esquire  |
      | local-3       | nody-mc-nodeface           | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                        | sir-david-nodenborough           | child    |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                          |
      | workspaceName                   | "local"                        |
      | nodesToPublish                  | ["sir-nodeward-nodington-iii"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish node aggregate creation with tethered descendants
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:SubSubNode':
      properties:
        text:
          defaultValue: 'my sub sub default'
          type: string
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        grandchild-node:
          type: 'Neos.ContentRepository.Testing:SubSubNode'
      properties:
        text:
          defaultValue: 'my sub default'
          type: string
    'Neos.ContentRepository.Testing:NodeWithTetheredChildNodes':
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                               |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"} |
    And I am user identified by "initiating-user-identifier"

    When the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                                              | originDimensionSpacePoint | nodeName | tetheredDescendantNodeAggregateIds                                                    |
      | local         | sir-david-nodenborough     | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes | {}                        | document | {"child-node": "nodewyn-tetherton", "child-node/grandchild-node": "nodimus-prime"}    |
      | local         | sir-nodeward-nodington-iii | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes | {}                        | esquire  | {"child-node": "nodimer-tetherton", "child-node/grandchild-node": "nodimus-mediocre"} |
      | local-3       | nody-mc-nodeface           | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes | {}                        | node     | {"child-node": "noderius-tetherton", "child-node/grandchild-node": "nodimus-subpar"}  |
    And I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partially publish node aggregate creation with tethered descendants
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:SubSubNode':
      properties:
        text:
          defaultValue: 'my sub sub default'
          type: string
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        grandchild-node:
          type: 'Neos.ContentRepository.Testing:SubSubNode'
      properties:
        text:
          defaultValue: 'my sub default'
          type: string
    'Neos.ContentRepository.Testing:NodeWithTetheredChildNodes':
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the current date and time is "2026-05-11T14:19:00+00:00"
    And I am user identified by "initiating-user-identifier"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                               |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"} |

    When the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId            | parentNodeAggregateId  | nodeTypeName                                              | originDimensionSpacePoint | nodeName | tetheredDescendantNodeAggregateIds                                                    |
      | local         | sir-david-nodenborough     | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes | {}                        | document | {"child-node": "nodewyn-tetherton", "child-node/grandchild-node": "nodimus-prime"}    |
      | local         | sir-nodeward-nodington-iii | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes | {}                        | esquire  | {"child-node": "nodimer-tetherton", "child-node/grandchild-node": "nodimus-mediocre"} |
      | local-3       | nody-mc-nodeface           | lady-eleonode-rootford | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes | {}                        | node     | {"child-node": "noderius-tetherton", "child-node/grandchild-node": "nodimus-subpar"}  |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                      |
      | workspaceName                   | "local"                    |
      # @todo: without an explicit succeeding sibling, nodes will be created at the end of its siblings.
      # publishing the other node first then changes the order since the commands are applied in reverse order during rebase.
      | nodesToPublish                  | ["sir-david-nodenborough"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"    |
    And I expect the graph state for workspace "local" to be unchanged
    And I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
