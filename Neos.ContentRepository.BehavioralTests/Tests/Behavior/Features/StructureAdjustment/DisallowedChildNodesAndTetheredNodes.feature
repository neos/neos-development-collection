@contentrepository @adapters=DoctrineDBAL
Feature: Remove disallowed Child Nodes and grandchild nodes

  As a user of the CR I want to be able to keep tethered child nodes although their type is not allowed below their parent

  Scenario: Direct constraints
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          '*': false
          'Neos.ContentRepository.Testing:Document': true
    'Neos.ContentRepository.Testing:Document':
      constraints:
        nodeTypes:
          '*': false
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:AnotherDocument'

    'Neos.ContentRepository.Testing:AnotherDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | parentNodeAggregateId  | nodeTypeName                            |
      | nody-mc-nodeface | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document |

    ########################
    # Actual Test
    ########################
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:AnotherDocument"
