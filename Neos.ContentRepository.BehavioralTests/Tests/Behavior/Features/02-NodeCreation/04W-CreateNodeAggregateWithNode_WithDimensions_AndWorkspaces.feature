Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referencable node aggregate of a specific type with a node
  in a specific dimension space point.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, peer, spec | spec->source->general, peer->general |
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
    And the following CreateNodeAggregateWithNode commands are executed:
      | workspaceName | nodeAggregateId               | originDimensionSpacePoint | nodeName            | parentNodeAggregateId  | succeedingSiblingNodeAggregateId | nodeTypeName                                                 | initialPropertyValues    |
      | local         | sir-david-nodenborough        | {"example":"general"}     | node                | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {"text": "initial text"} |
      | local         | nody-mc-nodeface              | {"example":"source"}      | child-node          | sir-david-nodenborough |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local         | nody-mc-nodeface-the-eldest   | {"example":"spec"}        | eldest-child-node   | sir-david-nodenborough | nody-mc-nodeface                 | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local         | nody-mc-nodeface-the-younger  | {"example":"spec"}        | younger-child-node  | sir-david-nodenborough |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local         | nody-mc-nodeface-the-elder    | {"example":"source"}      | elder-child-node    | sir-david-nodenborough | nody-mc-nodeface                 | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local         | nody-mc-nodeface-the-youngest | {"example":"source"}      | youngest-child-node | sir-david-nodenborough |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local         | sir-nodeward-nodington-iii    | {"example":"peer"}        | esquire             | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |
      | local-3       | oddnode-nodington             | {"example":"peer"}        | oddnode             | lady-eleonode-rootford |                                  | Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes | {}                       |

  Scenario: Publish node aggregate creation
    When I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    Then I expect the graph state for workspace "local" to be unchanged
    And I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partial publishing of node aggregate creation
    When I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                                                                                   |
      | workspaceName                   | "local"                                                                                                                                                                 |
      | nodesToPublish                  | ["sir-david-nodenborough","nody-mc-nodeface","nody-mc-nodeface-the-eldest","nody-mc-nodeface-the-younger","nody-mc-nodeface-the-elder","nody-mc-nodeface-the-youngest"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                                                                                                                                                 |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
