Feature: Run projection integrity violation detection regarding naming of tethered nodes

  As a user of the CR I want to be able to detect whether there are unnamed tethered events

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                     |
      | workspaceName                      | "live"                                    |
      | nodeAggregateId                    | "sir-david-nodenborough"                  |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint          | {"language":"de"}                         |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                  |
      | nodeName                           | "document"                                |
      | tetheredDescendantNodeAggregateIds | {"tethered-node": "nodewyn-tetherton"}    |

  Scenario: Remove tethered node's name
    And I change the following node's name:
      | Key                       | Value               |
      | contentStreamId           | "cs-identifier"     |
      | originDimensionSpacePoint | {"language":"de"}   |
      | nodeAggregateId           | "nodewyn-tetherton" |
      | newName                   | null                |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597923103
