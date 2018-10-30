@fixtures
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referenceable node aggregate of a specific type with a node
  in a specific dimension space point.

  Scenario: Create node aggregate with node with content dimensions
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |


    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """

    When the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                                           | Type                |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                            |                     |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |                     |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |                     |
      | dimensionSpacePoint     | {"language": "de"}                                              | DimensionSpacePoint |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                            |                     |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                            |                     |
      | nodeName                | foo                                                             |                     |
    And the graph projection is fully up to date
    And I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {"language": "de"}

    # event 1 is the one from the "Given" part
    Then I expect exactly 3 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                                      | Expected                                                                         | AssertionType |
      | contentStreamIdentifier                  | c75ae6a2-7254-4d42-a31b-a629e264069d                                             |               |
      | nodeAggregateIdentifier                  | 35411439-94d1-4bd4-8fac-0646856c6a1f                                             |               |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |               |
      | dimensionSpacePoint                      | {"language":"de"}                                               | json          |
      | visibleInDimensionSpacePoints              | [{"language":"de"},{"language":"ch"}]                           | json          |
      | nodeIdentifier                           | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                                             |               |
      | parentNodeIdentifier                     | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                                             |               |
      | nodeName                                 | foo                                                                              |               |
      | propertyDefaultValuesAndTypes.text.value | my default                                                                       |               |
      | propertyDefaultValuesAndTypes.text.type  | string                                                                           |               |
    And I expect a node identified by aggregate identifier "35411439-94d1-4bd4-8fac-0646856c6a1f" to exist in the subgraph
