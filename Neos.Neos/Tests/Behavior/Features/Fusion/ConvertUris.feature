@fixtures
Feature: Tests for the "Neos.Neos:ConvertUris" Fusion prototype

  Background:
    Given I have the site "a"
    And I have the following NodeTypes configuration:
    """yaml
    'unstructured': {}
    'Neos.Neos:FallbackNode': {}
    'Neos.Neos:Document': {}
    'Neos.Neos:ContentCollection': {}
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
    """
    And I have the following nodes:
      | Identifier | Path        | Node Type                   | Properties                                   |
      | root       | /sites      | unstructured                |                                              |
      | a          | /sites/a    | Neos.Neos:Test.DocumentType | {"uriPathSegment": "a", "title": "Node a"}   |
      | a1         | /sites/a/a1 | Neos.Neos:Test.DocumentType | {"uriPathSegment": "a1", "title": "Node a1"} |
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
    Some value with node URI: /en/a1.
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
    some <a href="/en/a1">Link</a>
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
