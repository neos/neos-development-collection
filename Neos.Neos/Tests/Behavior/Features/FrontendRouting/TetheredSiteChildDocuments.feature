@flowEntities @contentrepository
Feature: Tests for site node child documents. These are special in that they have the first non-dimension uri path segment.

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
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Acme.Site:Document.Homepage':
      superTypes:
        'Neos.Neos:Site': true
      childNodes:
        notFound:
          type: 'Neos.Neos:Document'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | contentStreamId | "cs-identifier"          |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    # We explicitly create a site node with a tethered child document without uriPathSegment, so its uriPath is empty, exactly as the site node's
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId  | nodeTypeName                | initialPropertyValues | nodeName |
      | shernode-homes  | lady-eleonode-rootford | Acme.Site:Document.Homepage | {}                    | site     |
    And A site exists for node name "site"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'site':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And The documenturipath projection is up to date

  Scenario: Set tethered child uriPathSegment
    When I remember NodeAggregateId of node "shernode-homes"s child "notFound" as "notFoundId"
    And the command SetNodeProperties is executed with payload:
      | Key             | Value                           |
      | nodeAggregateId | "$notFoundId"                   |
      | propertyValues  | {"uriPathSegment": "not-found"} |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    And I am on URL "/"
    Then the matched node should be "shernode-homes" in content stream "cs-identifier" and dimension "{}"
    And the node "$notFoundId" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/not-found"
