@flowEntities @contentrepository
Feature: Linking between multiple websites

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Content': []

    'Neos.Neos:Test.Routing.Page':
      superTypes:
        'Neos.Neos:Document': true
      constraints:
        nodeTypes:
          '*': true
          'Neos.Neos:Test.Routing.Page': true
          'Neos.Neos:Test.Routing.SomeOtherPage': true
          'Neos.Neos:Test.Routing.Content': true

    'Neos.Neos:Test.Routing.Content':
      superTypes:
        'Neos.Neos:Content': true

    'Neos.Neos:Test.Routing.SomeOtherPage':
      superTypes:
        'Neos.Neos:Test.Routing.Page': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                    |
      | nodeAggregateId             | "lady-eleonode-rootford" |
      | nodeTypeName                | "Neos.Neos:Sites"        |

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        duke-of-contentshire (content node)
    #        earl-o-documentbourgh
    #
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId         | parentNodeAggregateId  | nodeTypeName                                       | initialPropertyValues                    | nodeName |
      | homepage1               | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "ignore-me"}          | site-1   |
      | sir-david-nodenborough  | homepage1              | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "david-nodenborough"} | node2    |
      | homepage2               | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "ignore-me"}          | site-2   |
      | sir-david-nodenborough2 | homepage2              | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "david-nodenborough"} | node3    |
    And A site exists for node name "site-1" and domain "http://domain1.tld"
    And A site exists for node name "site-2" and domain "http://domain2.tld"
    And the sites configuration is:
    """
    Neos:
      Neos:
        sites:
          'site-1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
          'site-2':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """

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
