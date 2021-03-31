@fixtures
Feature: Change node aggregate type - behavior of HAPPYPATH strategy

  As a user of the CR I want to change the type of a node aggregate.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
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

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                           |
      | contentStreamIdentifier       | "cs-identifier"                                 |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint     | {"language":"de"}                               |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                        |
      | nodeName                      | "parent"                                        |
      | initialPropertyValues         | {}                                              |

    And the graph projection is fully up to date

  Scenario: Try to change to a node type that disallows already present children with the HAPPYPATH conflict resolution strategy
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    """
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                   |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                            |
      | contentStreamIdentifier | "cs-identifier"                                  |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                         |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy                | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present grandchildren with the HAPPYPATH conflict resolution strategy
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
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      childNodes:
        autocreated:
          type: 'Neos.ContentRepository.Testing:AutoCreated'
          constraints:
            nodeTypes:
              '*': TRUE
              'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    """
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                        | Value                                           |
      | contentStreamIdentifier                    | "cs-identifier"                                 |
      | nodeAggregateIdentifier                    | "parent2-na"                                    |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:ParentNodeType" |
      | originDimensionSpacePoint                  | {"language":"de"}                               |
      | parentNodeAggregateIdentifier              | "lady-eleonode-rootford"                        |
      | nodeName                                   | "parent2"                                       |
      | tetheredDescendantNodeAggregateIdentifiers | {"autocreated": "autocreated-child"}            |
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateIdentifier | "autocreated-child"                        |
      | initialPropertyValues         | {}                                         |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                            |
      | contentStreamIdentifier | "cs-identifier"                                  |
      | nodeAggregateIdentifier | "parent2-na"                                     |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:ParentNodeTypeB" |
      | strategy                | "happypath"                                      |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Change node type successfully
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "nodea-identifier-de"                      |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeTypeA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                   |
      | initialPropertyValues         | {}                                         |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                 |
      | contentStreamIdentifier | "cs-identifier"       |
      | nodeAggregateIdentifier | "nodea-identifier-de" |
      | sourceOrigin            | {"language":"de"}     |
      | targetOrigin            | {"language":"gsw"}    |
    And the graph projection is fully up to date

    When the command ChangeNodeAggregateType was published with payload:
      | Key                     | Value                                      |
      | contentStreamIdentifier | "cs-identifier"                            |
      | nodeAggregateIdentifier | "nodea-identifier-de"                      |
      | newNodeTypeName         | "Neos.ContentRepository.Testing:NodeTypeB" |
      | strategy                | "happypath"                                |
    And the graph projection is fully up to date

    # the type has changed
    When I am in content stream "cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node identified by aggregate identifier "nodea-identifier-de" to exist in the subgraph
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    When I am in content stream "cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node identified by aggregate identifier "nodea-identifier-de" to exist in the subgraph
    And I expect this node to be of type "Neos.ContentRepository.Testing:NodeTypeB"

    # the old "childOfTypeA" has not been removed with this strategy.
    Then I expect the node aggregate "nodea-identifier-de" to have the following child nodes:
      | Name         |
      | childOfTypeA |
      | childOfTypeB |


#      #missing default property values of target type must be set
#      #extra properties of source target type must be removed (TBD)
