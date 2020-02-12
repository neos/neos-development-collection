@fixtures
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referenceable node aggregate of a specific type with an initial node
  in a specific dimension space point.

  This is the tale of venerable root node aggregate Sir David Nodenborough already persistent in the content graph
  and its soon-to-be child node Sir Nodeward Nodington III, Esquire

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "sir-david-nodenborough"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [[]]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "regular"                              |
    And the graph projection is fully up to date

  Scenario: Try to create a node aggregate in a content stream that currently does not exist:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                          |
      | contentStreamIdentifier       | "non-existent-cs-identifier"                                   |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {}                                                             |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "esquire"                                                      |

    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a node aggregate in a content stream where it is already present:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                          |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {}                                                             |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "esquire"                                                      |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a (non-root) node aggregate of a root node type:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "cs-identifier"                        |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"           |
      | nodeTypeName                  | "Neos.ContentRepository:Root"          |
      | originDimensionSpacePoint     | {}                                     |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000" |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"               |
      | nodeName                      | "esquire"                              |

    Then the last command should have thrown an exception of type "NodeTypeIsOfTypeRoot"

  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                          |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {"undeclared": "undefined"}                                    |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "esquire"                                                      |

  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:
    When the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                           | Value                                                          |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {"undeclared": "undefined"}                                    |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "esquire"                                                      |

    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"


  Scenario: Try to create a node aggregate in an origin dimension space point not within the allowed dimension subspace:

  Scenario: Create node aggregate with initial node without auto-created child nodes
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                                          |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {}                                                             |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "esquire"                                                      |
    And the graph projection is fully up to date

    Then I expect exactly 3 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                       |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | []                                                             |
      | coveredDimensionSpacePoints   | [[]]                                                           |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | nodeName                      | "esquire"                                                      |
      | initialPropertyValues         | {"text": {"value": "my default", "type": "string"}}            |
      | nodeAggregateClassification   | "regular"                                                      |

  Scenario: Create node aggregate with node with auto-created child nodes
    Given I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:SubSubNode': []
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        foo:
          type: 'Neos.ContentRepository.Testing:SubSubNode'

    'Neos.ContentRepository.Testing:NodeWithTetheredChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:SubNode'
    """

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                        | Value                                                       |
      | contentStreamIdentifier                    | "cs-identifier"                                             |
      | nodeAggregateIdentifier                    | "sir-nodeward-nodington-iii"                                |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes" |
      | originDimensionSpacePoint                  | {}                                                          |
      | parentNodeAggregateIdentifier              | "sir-david-nodenborough"                                    |
      | nodeName                                   | "esquire"                                                   |
      | tetheredDescendantNodeAggregateIdentifiers | {"main": "nody-mc-nodeface", "main/foo": "nodimus-prime"}   |
    And the graph projection is fully up to date

    Then I expect exactly 5 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                             |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes" |
      | originDimensionSpacePoint     | []                                                          |
      | coveredDimensionSpacePoints   | [[]]                                                        |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                    |
      | nodeName                      | "esquire"                                                   |
      | initialPropertyValues         | {"text": {"value": "my default", "type": "string"}}         |
      | nodeAggregateClassification   | "regular"                                                   |
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                 |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubNode" |
      | originDimensionSpacePoint     | []                                       |
      | coveredDimensionSpacePoints   | [[]]                                     |
      | parentNodeAggregateIdentifier | "sir-nodeward-nodington-iii"             |
      | nodeName                      | "main"                                   |
      | nodeAggregateClassification   | "tethered"                               |
    And event at index 4 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                    |
      | contentStreamIdentifier       | "cs-identifier"                             |
      | nodeAggregateIdentifier       | "nodimus-prime"                             |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubSubNode" |
      | originDimensionSpacePoint     | []                                          |
      | coveredDimensionSpacePoints   | [[]]                                        |
      | parentNodeAggregateIdentifier | "nody-mc-nodeface"                          |
      | nodeName                      | "foo"                                       |
      | nodeAggregateClassification   | "tethered"                                  |
