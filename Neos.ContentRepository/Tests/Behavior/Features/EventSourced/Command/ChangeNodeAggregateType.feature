@fixtures
Feature: Change node aggregate type

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
        childOfTypeA: 'Neos.ContentRepository.Testing:ChildOfNodeTypeA'
      properties:
        text:
          type: string
          defaultValue: 'text'
    'Neos.ContentRepository.Testing:NodeTypeB':
      childNodes:
        childOfTypeB: 'Neos.ContentRepository.Testing:ChildOfNodeTypeB'
      properties:
        otherText:
          type: string
          defaultValue: 'otherText'
    """
    And the Event RootNodeWasCreated was published with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | nodeIdentifier           | rn-identifier                        | Uuid |
      | nodeTypeName             | Neos.ContentRepository:Root          |      |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | parent-agg-identifier                                                                | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:ParentNodeType                                        |                        |
      | dimensionSpacePoint           | {"language":"de"}                                                                    | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | parent-identifier-de                                                                 | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                        | Uuid                   |
      | nodeName                      | parent                                                                               |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |

  Scenario: Try to change to a non existing node type
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                    | Type |
      | contentStreamIdentifier | cs-identifier                            | Uuid |
      | nodeAggregateIdentifier | nodea-agg-identifier                     | Uuid |
      | newNodeTypeName         | Neos.ContentRepository.Testing:Undefined |      |
      | strategy                |                                          | null |
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
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | nodea-agg-identifier                                                                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeTypeA                                             |                        |
      | dimensionSpacePoint           | {"language":"de"}                                                                    | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | nodea-identifier-de                                                                  | Uuid                   |
      | parentNodeIdentifier          | parent-identifier-de                                                                 | Uuid                   |
      | nodeName                      | nodea                                                                                |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                    | Type |
      | contentStreamIdentifier | cs-identifier                            | Uuid |
      | nodeAggregateIdentifier | nodea-agg-identifier                     | Uuid |
      | newNodeTypeName         | Neos.ContentRepository.Testing:NodeTypeB |      |
      | strategy                |                                          | null |
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
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | auto-agg-identifier                                                                  | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:AutoCreated                                           |                        |
      | dimensionSpacePoint           | {"language": "de"}                                                                   | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | auto-identifier-de                                                                   | Uuid                   |
      | parentNodeIdentifier          | parent-identifier-de                                                                 | Uuid                   |
      | nodeName                      | autocreated                                                                          |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | nodea-agg-identifier                                                                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeTypeA                                             |                        |
      | dimensionSpacePoint           | {"language":"de"}                                                                    | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | nodea-identifier-de                                                                  | Uuid                   |
      | parentNodeIdentifier          | auto-identifier-de                                                                   | Uuid                   |
      | nodeName                      | nodea                                                                                |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                    | Type |
      | contentStreamIdentifier | cs-identifier                            | Uuid |
      | nodeAggregateIdentifier | nodea-agg-identifier                     | Uuid |
      | newNodeTypeName         | Neos.ContentRepository.Testing:NodeTypeB |      |
      | strategy                |                                          | null |
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
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | auto-agg-identifier                                                                  | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:AutoCreated                                           |                        |
      | dimensionSpacePoint           | {"language": "de"}                                                                   | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | auto-identifier-de                                                                   | Uuid                   |
      | parentNodeIdentifier          | parent-identifier-de                                                                 | Uuid                   |
      | nodeName                      | autocreated                                                                          |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                         | Type |
      | contentStreamIdentifier | cs-identifier                                 | Uuid |
      | nodeAggregateIdentifier | auto-agg-identifier                           | Uuid |
      | newNodeTypeName         | Neos.ContentRepository.Testing:ParentNodeType |      |
      | strategy                |                                               | null |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present children without a conflict resolution strategy
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:ParentNodeTypeB':
      constraints:
        nodeTypes:
          '*': TRUE
          'Neos.ContentRepository.Testing:NodeTypeA': FALSE
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | nodea-agg-identifier                                                                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeTypeA                                             |                        |
      | dimensionSpacePoint           | {"language":"de"}                                                                    | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | nodea-identifier-de                                                                  | Uuid                   |
      | parentNodeIdentifier          | parent-identifier-de                                                                 | Uuid                   |
      | nodeName                      | nodea                                                                                |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                          | Type |
      | contentStreamIdentifier | cs-identifier                                  | Uuid |
      | nodeAggregateIdentifier | parent-agg-identifier                          | Uuid |
      | newNodeTypeName         | Neos.ContentRepository.Testing:ParentNodeTypeB |      |
      | strategy                |                                                | null |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  Scenario: Try to change to a node type that disallows already present grandchildren without a conflict resolution strategy
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
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | auto-agg-identifier                                                                  | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:AutoCreated                                           |                        |
      | dimensionSpacePoint           | {"language": "de"}                                                                   | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | auto-identifier-de                                                                   | Uuid                   |
      | parentNodeIdentifier          | parent-identifier-de                                                                 | Uuid                   |
      | nodeName                      | autocreated                                                                          |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                        | Uuid                   |
      | nodeAggregateIdentifier       | nodea-agg-identifier                                                                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeTypeA                                             |                        |
      | dimensionSpacePoint           | {"language":"de"}                                                                    | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | nodea-identifier-de                                                                  | Uuid                   |
      | parentNodeIdentifier          | auto-identifier-de                                                                   | Uuid                   |
      | nodeName                      | nodea                                                                                |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                   | json                   |
    When the command ChangeNodeAggregateType was published with payload and exceptions are caught:
      | Key                     | Value                                          | Type |
      | contentStreamIdentifier | cs-identifier                                  | Uuid |
      | nodeAggregateIdentifier | parent-agg-identifier                          | Uuid |
      | newNodeTypeName         | Neos.ContentRepository.Testing:ParentNodeTypeB |      |
      | strategy                |                                                | null |
    Then the last command should have thrown an exception of type "NodeConstraintException"

  #Scenario: Change node type
  #  And the Event NodeAggregateWithNodeWasCreated was published with payload:
  #    | Key                                      | Value                                                                                | Type                   |
  #    | contentStreamIdentifier                  | cs-identifier                                                                        | Uuid                   |
  #    | nodeAggregateIdentifier                  | nodea-agg-identifier                                                                 | Uuid                   |
  #    | nodeTypeName                             | Neos.ContentRepository.Testing:NodeTypeA                                             |                        |
  #    | dimensionSpacePoint                      | {"language":"de"}                                                                    | DimensionSpacePoint    |
  #    | visibleDimensionSpacePoints              | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "gsw"}}]} | DimensionSpacePointSet |
  #    | nodeIdentifier                           | nodea-identifier-de                                                                  | Uuid                   |
  #    | parentNodeIdentifier                     | parent-identifier                                                                    | Uuid                   |
  #    | nodeName                                 | nodea                                                                                |                        |
  #    | propertyDefaultValuesAndTypes.text.value | text                                                                                 |                        |
  #    | propertyDefaultValuesAndTypes.text.type  | string                                                                               |                        |
  #  And the event NodeSpecializationWasCreated was published with payload:
  #    | Key                      | Value                                           | Type                   |
  #    | contentStreamIdentifier  | cs-identifier                                   | Uuid                   |
  #    | nodeIdentifier           | nodea-identifier-de                             | Uuid                   |
  #    | specializationIdentifier | nodea-identifier-de                             | Uuid                   |
  #    | specializationLocation   | {"market":"CH", "language":"gsw"}               | DimensionSpacePoint    |
  #    | specializationVisibility | {"points":[{"coordinates":{"language":"gsw"}}]} | DimensionSpacePointSet |
    # node type change must be changed
    # missing autocreated child nodes of target type must be created
    # extra autocreated child nodes of source type must be removed if delete strategy is to be applied
    # missing default property values of target type must be set
    # extra properties of source target type must be removed (TBD)
    # all of this must cascade through all subgraphs
