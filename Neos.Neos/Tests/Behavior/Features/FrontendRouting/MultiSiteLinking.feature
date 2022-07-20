@fixtures
# Note: For the routing tests to work we rely on Configuration/Testing/Behat/NodeTypes.Test.Routing.yaml
Feature: Linking between multiple websites

  Background:
    Given I have no content dimensions
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
      | initiatingUserIdentifier   | "u"             |
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
    #
    # NOTE: The "nodeName" column only exists because it's currently not possible to create unnamed nodes (see https://github.com/neos/contentrepository-development-collection/pull/162)
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                       | initialPropertyValues                    | nodeName |
      | homepage1               | lady-eleonode-rootford        | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "ignore-me"}          | site-1   |
      | sir-david-nodenborough  | homepage1                     | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "david-nodenborough"} | node2    |
      | homepage2               | lady-eleonode-rootford        | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "ignore-me"}          | site-2   |
      | sir-david-nodenborough2 | homepage2                     | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "david-nodenborough"} | node3    |
    And A site exists for node name "site-1" and domain "http://domain1.tld"
    And A site exists for node name "site-2" and domain "http://domain2.tld"
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

  Scenario: Resolve foreign website homepage node
    When I am on URL "http://domain1.tld/"
    Then the node "homepage1" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/"
    And the node "homepage2" in content stream "cs-identifier" and dimension "{}" should resolve to URL "http://domain2.tld/"
    When I am on URL "http://domain2.tld/"
    Then the node "homepage1" in content stream "cs-identifier" and dimension "{}" should resolve to URL "http://domain1.tld/"
    And the node "homepage2" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/"

  Scenario: Resolve foreign website nodes
    When I am on URL "http://domain1.tld/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And the node "sir-david-nodenborough2" in content stream "cs-identifier" and dimension "{}" should resolve to URL "http://domain2.tld/david-nodenborough"
    When I am on URL "http://domain2.tld/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "http://domain1.tld/david-nodenborough"
    And the node "sir-david-nodenborough2" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"

  Scenario: Match homepage node
    When I am on URL "http://domain1.tld/"
    Then the matched node should be "homepage1" in content stream "cs-identifier" and dimension "{}"
    When I am on URL "http://domain2.tld/"
    Then the matched node should be "homepage2" in content stream "cs-identifier" and dimension "{}"

  Scenario: Match other nodes
    When I am on URL "http://domain1.tld/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    When I am on URL "http://domain2.tld/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough2" in content stream "cs-identifier" and dimension "{}"
