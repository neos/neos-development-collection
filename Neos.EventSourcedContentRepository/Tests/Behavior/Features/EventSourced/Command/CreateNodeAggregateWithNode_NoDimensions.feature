@fixtures
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referenceable node aggregate of a specific type with an initial node
  in a specific dimension space point.

  This is the tale of venerable root node aggregate Sir David Nodenborough already persistent in the content graph
  and its soon-to-be esquire Sir Nodeward Nodington III

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                | Type                    |
      | workspaceName                  | live                                 | WorkspaceName           |
      | workspaceTitle                 | Live                                 | WorkspaceTitle          |
      | workspaceDescription           | The live workspace                   | WorkspaceDescription    |
      | initiatingUserIdentifier       | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |
      | currentContentStreamIdentifier | cs-identifier                        | ContentStreamIdentifier |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                | Type                    |
      | contentStreamIdentifier       | cs-identifier                        | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-david-nodenborough               | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository:Root          | NodeTypeName            |
      | visibleInDimensionSpacePoints | [[]]                                 | DimensionSpacePointSet  |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |

  Scenario: Try to create a node aggregate in a content stream that currently does not exist:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                           | Type                    |
      | contentStreamIdentifier       | non-existent-cs-identifier                                      | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-nodeward-nodington-iii                                      | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes | NodeTypeName            |
      | originDimensionSpacePoint     | {}                                                              | DimensionSpacePoint     |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000                            | UserIdentifier          |
      | parentNodeAggregateIdentifier | sir-david-nodenborough                                          | NodeAggregateIdentifier |
      | nodeName                      | esquire                                                         | NodeName                |

    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a node aggregate in a content stream where it is already present:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                           | Type                    |
      | contentStreamIdentifier       | cs-identifier                                                   | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-david-nodenborough                                          | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes | NodeTypeName            |
      | originDimensionSpacePoint     | {}                                                              | DimensionSpacePoint     |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000                            | UserIdentifier          |
      | parentNodeAggregateIdentifier | sir-david-nodenborough                                          | NodeAggregateIdentifier |
      | nodeName                      | esquire                                                         | NodeName                |

    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a (non-root) node aggregate of a root node type:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                | Type                    |
      | contentStreamIdentifier       | cs-identifier                        | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-nodeward-nodington-iii           | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository:Root          | NodeTypeName            |
      | originDimensionSpacePoint     | {}                                   | DimensionSpacePoint     |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |
      | parentNodeAggregateIdentifier | sir-david-nodenborough               | NodeAggregateIdentifier |
      | nodeName                      | esquire                              | NodeName                |

    Then the last command should have thrown an exception of type "NodeTypeIsOfTypeRoot"

  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                           | Type                    |
      | contentStreamIdentifier       | cs-identifier                                                   | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-nodeward-nodington-iii                                      | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes | NodeTypeName            |
      | originDimensionSpacePoint     | {"undeclared": "undefined"}                                     | DimensionSpacePoint     |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000                            | UserIdentifier          |
      | parentNodeAggregateIdentifier | sir-david-nodenborough                                          | NodeAggregateIdentifier |
      | nodeName                      | esquire                                                         | NodeName                |

  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                           | Type                    |
      | contentStreamIdentifier       | cs-identifier                                                   | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-nodeward-nodington-iii                                      | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes | NodeTypeName            |
      | originDimensionSpacePoint     | {"undeclared": "undefined"}                                     | DimensionSpacePoint     |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000                            | UserIdentifier          |
      | parentNodeAggregateIdentifier | sir-david-nodenborough                                          | NodeAggregateIdentifier |
      | nodeName                      | esquire                                                         | NodeName                |

    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotWithinTheAllowedDimensionSubspace"


  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:

  Scenario: Create node aggregate with initial node without auto-created child nodes
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                                           | Type                    |
      | contentStreamIdentifier       | cs-identifier                                                   | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-nodeward-nodington-iii                                      | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes | NodeTypeName            |
      | originDimensionSpacePoint     | {}                                                              | DimensionSpacePoint     |
      | parentNodeAggregateIdentifier | sir-david-nodenborough                                          | NodeAggregateIdentifier |
      | nodeName                      | esquire                                                         | NodeName                |

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier:NodeAggregate:sir-nodeward-nodington-iii"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                                      | Expected                                                        | AssertionType |
      | contentStreamIdentifier                  | cs-identifier                                                   |               |
      | nodeAggregateIdentifier                  | sir-nodeward-nodington-iii                                      |               |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |               |
      | originDimensionSpacePoint                | {}                                                              | json          |
      | visibleInDimensionSpacePoints            | [{}]                                                            | json          |
      | parentNodeAggregateIdentifier            | sir-david-nodenborough                                          |               |
      | nodeName                                 | esquire                                                         |               |
      | propertyDefaultValuesAndTypes.text.value | my default                                                      |               |
      | propertyDefaultValuesAndTypes.text.type  | string                                                          |               |

  Scenario: Create node aggregate with node with auto-created child nodes
    Given I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:SubSubNode': {}
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        foo:
          type: 'Neos.ContentRepository.Testing:SubSubNode'

    'Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:SubNode'
    """

    When the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                                        | Type                |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                         |                     |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f                         |                     |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes |                     |
      | dimensionSpacePoint     | {}                                                           | DimensionSpacePoint |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                         |                     |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                         |                     |
      | nodeName                | foo                                                          |                     |

    # event 1 is the one from the "Given" part
    Then I expect exactly 5 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                                      | Expected                                                     | AssertionType |
      | contentStreamIdentifier                  | c75ae6a2-7254-4d42-a31b-a629e264069d                         |               |
      | nodeAggregateIdentifier                  | 35411439-94d1-4bd4-8fac-0646856c6a1f                         |               |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes |               |
      | dimensionSpacePoint                      | {}                                                           | json          |
      | visibleInDimensionSpacePoints            | [{}]                                                         | json          |
      | nodeIdentifier                           | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                         |               |
      | parentNodeIdentifier                     | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                         |               |
      | nodeName                                 | foo                                                          |               |
      | propertyDefaultValuesAndTypes.text.value | my default                                                   |               |
      | propertyDefaultValuesAndTypes.text.type  | string                                                       |               |
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                               | AssertionType |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d   |               |
      | nodeTypeName                  | Neos.ContentRepository.Testing:SubNode |               |
      | dimensionSpacePoint           | {}                                     | json          |
      | visibleInDimensionSpacePoints | [{}]                                   | json          |
      | parentNodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |               |
      | nodeName                      | main                                   |               |
    And event at index 4 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                  | AssertionType |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d      |               |
      | nodeName                      | foo                                       |               |
      | nodeTypeName                  | Neos.ContentRepository.Testing:SubSubNode |               |
      | dimensionSpacePoint           | {}                                        | json          |
      | visibleInDimensionSpacePoints | [{}]                                      | json          |
