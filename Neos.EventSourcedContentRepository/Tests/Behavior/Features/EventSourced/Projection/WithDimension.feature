@fixtures
Feature: Reading of our Graph Projection

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values           | Generalizations       |
      | language   | mul     | mul, de, en, gsw | gsw->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        test:
          type: string
    """
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                           |
      | contentStreamIdentifier       | "cs-identifier"                                                                 |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                        |
      | nodeTypeName                  | "Neos.ContentRepository:Root"                                                   |
      | visibleInDimensionSpacePoints | [{"language": "mul"},{"language": "en"},{"language": "de"},{"language": "gsw"}] |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                                          |

  Scenario: Property Changes with two dimension values
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "de"}                        |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]  |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "foo"                                     |
    And the event NodePropertyWasSet was published with payload:
      | Key                       | Value                                         |
      | contentStreamIdentifier   | "cs-identifier"                               |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                            |
      | originDimensionSpacePoint | {"language": "de"}                            |
      | propertyName              | "test"                                        |
      | value                     | {"value": "original value", "type": "string"} |
    And the graph projection is fully up to date

    Then I expect the graph projection to consist of exactly 2 nodes
    And I expect a node with identifier {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {"language": "de"}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "gsw"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    Then I expect node aggregate identifier "nody-mc-nodeface" and path "foo" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {"language": "de"}}

    # check whether we want to test this here at all once node peer variants are implemented
  #Scenario: Translation of node in aggregate
  #  When the event NodeAggregateWithNodeWasCreated was published with payload:
  #    | Key                           | Value                                     |
  #    | contentStreamIdentifier       | "cs-identifier"                           |
  #    | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
  #    | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
  #    | originDimensionSpacePoint     | {"language": "de"}                        |
  #    | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}]  |
  #    | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
  #    | nodeName                      | "foo"                                     |#

    # Translated node /sites/text1 (language=en)
    #And the Event "Neos.EventSourcedContentRepository:NodeInAggregateWasTranslated" was published to stream "Neos.ContentRepository:ContentStream:cs-identifier:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
    #  | Key                             | Value                                  |
    #  | contentStreamIdentifier         | "cs-identifier"                        |
     # | sourceNodeIdentifier            | "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" |
     ## | destinationNodeIdentifier       | "01831e48-a20c-11e7-851a-dfef4f55c64c" |
     # | destinationParentNodeIdentifier | "ead94f26-a20d-11e7-8ecc-43aabe596a03" |
     # | dimensionSpacePoint             | {"language":"en"}                      |
     # | visibleInDimensionSpacePoints   | [{"language":"en"}]                    |

    #When the graph projection is fully up to date
    #And I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}

    #Then I expect a node "01831e48-a20c-11e7-851a-dfef4f55c64c" to exist in the graph projection
    #And I expect the path "/sites/text1" to lead to the node "01831e48-a20c-11e7-851a-dfef4f55c64c"
