@contentrepository @adapters=DoctrineDBAL
Feature: Change node aggregate type - basic error cases

  As a user of the CR I want to change the type of a node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:ParentNodeType': []
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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamIdentifier     | "cs-identifier"                           |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository:Root"             |
      | coveredDimensionSpacePoints | [{"language": "de"}, {"language": "gsw"}] |
      | initiatingUserIdentifier    | "system-user"                             |
      | nodeAggregateClassification | "root"                                    |
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                           |
      | contentStreamIdentifier       | "cs-identifier"                                 |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint     | {"language":"de"}                               |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                        |
      | nodeName                      | "parent"                                        |
      | initialPropertyValues         | {}                                              |
      | initiatingUserIdentifier      | "user"                                          |

    And the graph projection is fully up to date

  Scenario: Try to change the node aggregate type on a non-existing content stream
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                             |
      | contentStreamIdentifier  | "non-existing"                                    |
      | nodeAggregateIdentifier  | "sir-david-nodenborough"                          |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
      | strategy                 | "happypath"                                       |
      | initiatingUserIdentifier | "user"                                            |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to change the type on a non-existing node aggregate
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                             |
      | contentStreamIdentifier  | "cs-identifier"                                   |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                                |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ChildOfNodeTypeA" |
      | strategy                 | "happypath"                                       |
      | initiatingUserIdentifier | "user"                                            |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to change a node aggregate to a non existing type
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                      |
      | contentStreamIdentifier  | "cs-identifier"                            |
      | nodeAggregateIdentifier  | "sir-david-nodenborough"                   |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:Undefined" |
      | strategy                 | "happypath"                                |
      | initiatingUserIdentifier | "user"                                     |
    Then the last command should have thrown an exception of type "NodeTypeNotFound"

  Scenario: Try to change to a node type disallowed by the parent node
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:ParentNodeType':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    """
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                   |
      | nodeName                      | "parent"                                   |
      | initialPropertyValues         | {}                                         |
      | initiatingUserIdentifier      | "user"                                     |
    And the graph projection is fully up to date
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                      |
      | contentStreamIdentifier  | "cs-identifier"                            |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                         |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                 | "happypath"                                |
      | initiatingUserIdentifier | "user"                                     |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that is not allowed by the grand parent aggregate inside an autocreated parent aggregate
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:AutoCreated': []
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeB': FALSE
    """

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                        | Value                                           |
      | contentStreamIdentifier                    | "cs-identifier"                                 |
      | nodeAggregateIdentifier                    | "parent2-na"                                    |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint                  | {"language":"de"}                               |
      | parentNodeAggregateIdentifier              | "lady-eleonode-rootford"                        |
      | nodeName                                   | "parent2"                                       |
      | initialPropertyValues                      | {}                                              |
      | tetheredDescendantNodeAggregateIdentifiers | {"autocreated": "autocreated-child"}            |
      | initiatingUserIdentifier                   | "user"                                          |
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateIdentifier | "autocreated-child"                        |
      | initialPropertyValues         | {}                                         |
      | initiatingUserIdentifier      | "user"                                     |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                      |
      | contentStreamIdentifier  | "cs-identifier"                            |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                         |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                 | "happypath"                                |
      | initiatingUserIdentifier | "user"                                     |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change the node type of an auto created child node to anything other than defined:
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:AutoCreated': []
    'Neos.ContentRepository.Testing:ParentNodeType':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
    """

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                        | Value                                           |
      | contentStreamIdentifier                    | "cs-identifier"                                 |
      | nodeAggregateIdentifier                    | "parent2-na"                                    |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint                  | {"language":"de"}                               |
      | parentNodeAggregateIdentifier              | "lady-eleonode-rootford"                        |
      | nodeName                                   | "parent2"                                       |
      | initialPropertyValues                      | {}                                              |
      | tetheredDescendantNodeAggregateIdentifiers | {"autocreated": "nody-mc-nodeface"}             |
      | initiatingUserIdentifier                   | "user"                                          |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                      | Value                                           |
      | contentStreamIdentifier  | "cs-identifier"                                 |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                              |
      | newNodeTypeName          | "Neos.ContentRepository.Testing:ParentNodeType" |
      | strategy                 | "happypath"                                     |
      | initiatingUserIdentifier | "user"                                          |
    Then the last command should have thrown an exception of type "NodeConstraintException"
