@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - basic error cases

  As a user of the CR I want to change the type of a node aggregate.

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:Simple': []
    'Neos.ContentRepository.Testing:AbstractNode':
      abstract: true
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeB': false
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeB': false
    'Neos.ContentRepository.Testing:GrandParentNodeType':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:ParentNodeType'
    'Neos.ContentRepository.Testing:ChildOfNodeTypeA': []
    'Neos.ContentRepository.Testing:ChildOfNodeTypeB': []
    'Neos.ContentRepository.Testing:NodeTypeA':
      childNodes:
        childOfTypeA:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      properties:
        text:
          type: string
          defaultValue: 'text'
    'Neos.ContentRepository.Testing:NodeTypeB':
      childNodes:
        childOfTypeB:
          type: 'Neos.ContentRepository.Testing:ChildOfNodeTypeB'
      properties:
        otherText:
          type: string
          defaultValue: 'otherText'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | parentNodeAggregateId  | nodeTypeName                                  | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | parent   | lady-eleonode-rootford | Neos.ContentRepository.Testing:ParentNodeType | {"tethered": "nodewyn-tetherton"}  |
      | nody-mc-nodeface       | null     | sir-david-nodenborough | Neos.ContentRepository.Testing:Simple         | {}                                 |
      | nodimus-prime          | null     | nodewyn-tetherton      | Neos.ContentRepository.Testing:Simple         | {}                                 |

  Scenario: Try to change the node aggregate type in a workspace that currently does not exist
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                             |
      | workspaceName   | "non-existing"                                    |
      | nodeAggregateId | "sir-david-nodenborough"                          |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
      | strategy        | "happypath"                                       |
    Then the last command should have thrown an exception of type "WorkspaceDoesNotExist"

  Scenario: Try to change the node aggregate type in a workspace whose content stream is closed
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    And the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                             |
      | workspaceName   | "live"                                            |
      | nodeAggregateId | "sir-david-nodenborough"                          |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
      | strategy        | "happypath"                                       |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

  Scenario: Try to change the type on a non-existing node aggregate
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                             |
      | nodeAggregateId | "non-existing"                                    |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
      | strategy        | "happypath"                                       |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to change the type of a root node aggregate:
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                        |
      | nodeAggregateId | "lady-eleonode-rootford"                     |
      | newNodeTypeName | "Neos.ContentRepository.Testing:AnotherRoot" |
      | strategy        | "happypath"                                  |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to change the type of a tethered node aggregate:
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                           |
      | nodeAggregateId | "nodewyn-tetherton"                             |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeType" |
      | strategy        | "happypath"                                     |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to change a node aggregate to a non existing type
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                      |
      | nodeAggregateId | "sir-david-nodenborough"                   |
      | newNodeTypeName | "Neos.ContentRepository.Testing:Undefined" |
      | strategy        | "happypath"                                |
    Then the last command should have thrown an exception of type "NodeTypeNotFound"

  Scenario: Try to change node aggregate to a root type:
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                        |
      | nodeAggregateId | "sir-david-nodenborough"                     |
      | newNodeTypeName | "Neos.ContentRepository.Testing:AnotherRoot" |
      | strategy        | "happypath"                                  |
    Then the last command should have thrown an exception of type "NodeTypeIsOfTypeRoot"

  Scenario: Try to change a node aggregate to an abstract type
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                         |
      | nodeAggregateId | "sir-david-nodenborough"                      |
      | newNodeTypeName | "Neos.ContentRepository.Testing:AbstractNode" |
      | strategy        | "happypath"                                   |
    Then the last command should have thrown an exception of type "NodeTypeIsAbstract"

  Scenario: Try to change to a node type disallowed by the parent node
    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                      |
      | nodeAggregateId | "nody-mc-nodeface"                         |
      | newNodeTypeName | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy        | "happypath"                                |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type disallowed by the parent node in a variant
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"language": "gsw"}      |
      | newParentNodeAggregateId            | "lady-eleonode-rootford" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "scatter"                |
    And the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                      |
      | nodeAggregateId | "nody-mc-nodeface"                         |
      | newNodeTypeName | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy        | "happypath"                                |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that is not allowed by the grand parent aggregate inside a tethered parent aggregate

    When the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                      |
      | nodeAggregateId | "nodimus-prime"                            |
      | newNodeTypeName | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy        | "happypath"                                |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that is not allowed by the grand parent aggregate inside a tethered parent aggregate in a variant
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nodimus-prime"          |
      | dimensionSpacePoint                 | {"language": "gsw"}      |
      | newParentNodeAggregateId            | "lady-eleonode-rootford" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "scatter"                |
    And the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                      |
      | nodeAggregateId | "nodimus-prime"                            |
      | newNodeTypeName | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy        | "happypath"                                |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change a node to a type with a tethered node declaration, whose name is already occupied by a non-tethered node
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | nodeName | parentNodeAggregateId | nodeTypeName                          |
      | oddnode-tetherton | tethered | nody-mc-nodeface      | Neos.ContentRepository.Testing:Simple |
    And the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                           |
      | nodeAggregateId | "nody-mc-nodeface"                              |
      | newNodeTypeName | "Neos.ContentRepository.Testing:ParentNodeType" |
      | strategy        | "happypath"                                     |
    Then the last command should have thrown an exception of type "NodeAggregateIsUntethered"

  Scenario: Try to change a node to a type with a descendant tethered node declaration, whose name is already occupied by a non-tethered node (recursive case)
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId   | nodeName | parentNodeAggregateId | nodeTypeName                          |
      | oddnode-tetherton | tethered | nodewyn-tetherton     | Neos.ContentRepository.Testing:Simple |
    And the command ChangeNodeAggregateType is executed with payload and exceptions are caught:
      | Key             | Value                                                |
      | nodeAggregateId | "sir-david-nodenborough"                             |
      | newNodeTypeName | "Neos.ContentRepository.Testing:GrandParentNodeType" |
      | strategy        | "happypath"                                          |
    Then the last command should have thrown an exception of type "NodeAggregateIsUntethered"
