@contentrepository @adapters=DoctrineDBAL
Feature: Move node to a new parent / within the current parent before a sibling / to the end of the sibling list

  As a user of the CR I want to move a node to a new parent / within the current parent before a sibling / to the end of the sibling list,
  without affecting other nodes in the node aggregate.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands

  Content Structure:
  - lady-eleonode-rootford (Neos.ContentRepository:Root)
  - sir-david-nodenborough (Neos.ContentRepository.Testing:DocumentWithTetheredChildNode)
  - "tethered" nodewyn-tetherton (Neos.ContentRepository.Testing:Content)
  - sir-nodeward-nodington-iii (Neos.ContentRepository.Testing:Document)

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | market     | DE, CH      | CH->DE          |
      | language   | de, gsw, fr | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Content':
      constraints:
        nodeTypes:
          '*': true
          'Neos.ContentRepository.Testing:Document': false
    'Neos.ContentRepository.Testing:DocumentWithTetheredChildNode':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Content'
          constraints:
            nodeTypes:
              '*': true
              'Neos.ContentRepository.Testing:Content': false
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | contentStreamId | "cs-identifier"               |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            |  nodeName   | parentNodeAggregateId  | nodeTypeName                                                 | initialPropertyValues    | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough     |  document       | lady-eleonode-rootford | Neos.ContentRepository.Testing:DocumentWithTetheredChildNode | {"text": "initial text"} | {"tethered": "nodewyn-tetherton"}                               |
      | sir-nodeward-nodington-iii    |  esquire       | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | {"text": "initial text"} | {"tethered": "nodewyn-tetherton"}                               |

  Scenario: Move a node that has no name
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                                                                                                              |
      | nodeAggregateId             | "nody-mc-nodeface"                                                                                                                                 |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                                                                                          |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                                                                                                           |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                              |
      | nodeAggregateId              | "nody-mc-nodeface"                 |
      | newParentNodeAggregateId     | "lady-eleonode-rootford"           |
    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and dimension space point {"market": "DE", "language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE","language":"de"}
