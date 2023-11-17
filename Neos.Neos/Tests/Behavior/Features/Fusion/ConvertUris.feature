@flowEntities
Feature: Tests for the "Neos.Neos:ConvertUris" Fusion prototype

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | contentStreamId | "cs-identifier"   |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                |initialPropertyValues                              | nodeName |
      | a               | root                  | Neos.Neos:Site              |{"title": "Node a"}                                | a        |
      | a1              | a                     | Neos.Neos:Test.DocumentType |{"uriPathSegment": "a1", "title": "Node a1"}       | a1       |
    And A site exists for node name "a" and domain "http://localhost"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And the Fusion context node is "a"
    And the Fusion context request URI is "http://localhost"

  Scenario: Default output
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris
    """
    Then I expect the following Fusion rendering result:
    """
    """

  Scenario: Without URI
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'Some value without URI'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    Some value without URI
    """

  Scenario: URI to non-existing node
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'Some value with node URI to non-existing node: node://non-existing.'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    Some value with node URI to non-existing node: .
    """

  Scenario: URI to existing node
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'Some value with node URI: node://a1.'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    Some value with node URI: /a1.
    """

  Scenario: Anchor tag without node or asset URI
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'some <a href="https://neos.io">Link</a>'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    some <a target="_blank" rel="noopener external" href="https://neos.io">Link</a>
    """

  Scenario: Anchor tag with node URI to non-existing node
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'some <a href="node://non-existing">Link</a>'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    some Link
    """

  Scenario: Anchor tag with URI to existing node
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'some <a href="node://a1">Link</a>'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    some <a href="/a1">Link</a>
    """

  Scenario: URI to non-existing asset
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'Some value with node URI to non-existing asset: asset://non-existing.'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    Some value with node URI to non-existing asset: .
    """

  Scenario: URI to existing asset
    When an asset exists with id "362f3049-b9bb-454d-8769-6b35167e471e"
    And I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ConvertUris {
      value = 'Some value with node URI: asset://362f3049-b9bb-454d-8769-6b35167e471e.'
    }
    """
    Then I expect the following Fusion rendering result:
    """
    Some value with node URI: http://localhost/_Resources/Testing/Persistent/d0a1342bcb0e515bea83269427d8341d5f62a43d/test.svg.
    """
