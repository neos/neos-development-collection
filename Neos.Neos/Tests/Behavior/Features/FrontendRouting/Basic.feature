@fixtures @contentrepository
# Note: For the routing tests to work we rely on Configuration/Testing/Behat/NodeTypes.Test.Routing.yaml
Feature: Basic routing functionality (match & resolve document nodes in one dimension)

  Background:
    Given I have no content dimensions
    And I am user identified by "initiating-user-identifier"
    And I have the following NodeTypes configuration:
    """
    'Neos.Neos:Sites': {}
    'Neos.EventSourcedNeosAdjustments:Test.Routing.Page':
      properties:
        uriPathSegment:
          type: string
    'Neos.EventSourcedNeosAdjustments:Test.Routing.Content':
      properties:
        uriPathSegment:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                        |
      | contentStreamIdentifier     | "cs-identifier"              |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"     |
      | nodeTypeName                | "Neos.Neos:Sites"            |
      | coveredDimensionSpacePoints | [{}]                         |
      | initiatingUserIdentifier    | "initiating-user-identifier" |
      | nodeAggregateClassification | "root"                       |
    And the graph projection is fully up to date

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        duke-of-contentshire (content node)
    #        earl-o-documentbourgh
    #      nody-mc-nodeface
    #
    # NOTE: The "nodeName" column only exists because it's currently not possible to create unnamed nodes (see https://github.com/neos/contentrepository-development-collection/pull/162)
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                          | initialPropertyValues                    | nodeName |
      | shernode-homes          | lady-eleonode-rootford        | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "ignore-me"}          | node1    |
      | sir-david-nodenborough  | shernode-homes                | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "david-nodenborough"} | node2    |
      | duke-of-contentshire    | sir-david-nodenborough        | Neos.EventSourcedNeosAdjustments:Test.Routing.Content | {"uriPathSegment": "ignore-me"}          | node3    |
      | earl-o-documentbourgh   | sir-david-nodenborough        | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "earl-document"}      | node4    |
      | nody-mc-nodeface        | shernode-homes                | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "nody"}               | node5    |
    And A site exists for node name "node1"
    And the sites configuration is:
    """
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And The documenturipath projection is up to date

  Scenario: Match homepage URL
    When I am on URL "/"
    Then the matched node should be "shernode-homes" in content stream "cs-identifier" and dimension "{}"

  Scenario: Resolve nodes correctly from homepage
    When I am on URL "/"
    Then the node "shernode-homes" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Match node lower in the tree
    When I am on URL "/david-nodenborough/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

  Scenario: Resolve from node lower in the tree
    When I am on URL "/david-nodenborough/earl-document"
    Then the node "shernode-homes" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Change uri path segment
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | contentStreamIdentifier   | "cs-identifier"                                  |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated"} |
    And The documenturipath projection is up to date
    And I am on URL "/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated/earl-document"

  Scenario: Move node upwards in the tree
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                   |
      | contentStreamIdentifier                     | "cs-identifier"         |
      | nodeAggregateIdentifier                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                         | {}                      |
      | newParentNodeAggregateIdentifier            | "shernode-homes"        |
      | newSucceedingSiblingNodeAggregateIdentifier | null                    |
    And The documenturipath projection is up to date
    And I am on URL "/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/earl-document"

  Scenario: Move node downwards in the tree
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                   |
      | contentStreamIdentifier                     | "cs-identifier"         |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"      |
      | dimensionSpacePoint                         | {}                      |
      | newParentNodeAggregateIdentifier            | "earl-o-documentbourgh" |
      | newSucceedingSiblingNodeAggregateIdentifier | null                    |
    And The documenturipath projection is up to date
    And I am on URL "/david-nodenborough/earl-document/nody"
    Then the matched node should be "nody-mc-nodeface" in content stream "cs-identifier" and dimension "{}"
    And the node "nody-mc-nodeface" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document/nody"
