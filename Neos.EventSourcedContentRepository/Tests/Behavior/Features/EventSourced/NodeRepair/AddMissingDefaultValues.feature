@fixtures
Feature: Add Missing Default Values


  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository:Document': []
    """
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | live-cs-identifier                   | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                           | Type                    |
      | contentStreamIdentifier | live-cs-identifier              | Uuid                    |
      | nodeAggregateIdentifier | doc-agg-identifier              | NodeAggregateIdentifier |
      | nodeTypeName            | Neos.ContentRepository:Document |                         |
      | dimensionSpacePoint     | {}                              | DimensionSpacePoint     |
      | nodeIdentifier          | doc-identifier-de               | Uuid                    |
      | parentNodeIdentifier    | rn-identifier                   | Uuid                    |
      | nodeName                | document                        |                         |
    And the graph projection is fully up to date

  Scenario: Add a new property; ensure Node is adjusted properly

    When I have the following additional NodeTypes configuration:
      """
      'Neos.ContentRepository:Document':
        properties:
          p1:
            defaultValue: "My Default"
      """
    And I run the command "node:repair --only addMissingDefaultValues"
    And the graph projection is fully up to date
    When I am in content stream "live-cs-identifier" and Dimension Space Point {}
    Then I expect the Node "<string>" to have the properties:
    And I expect the Node "doc-identifier-de" to have the properties:
      | Key | Value      |
      | p1  | My Default |

