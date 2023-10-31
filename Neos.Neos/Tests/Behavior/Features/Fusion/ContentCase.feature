@fixtures
Feature: Tests for the "Neos.Neos:ContentCase" Fusion prototype

  Background:
    Given I have the site "a"
    And I have the following NodeTypes configuration:
    """yaml
    'unstructured': {}
    'Neos.Neos:FallbackNode': {}
    'Neos.Neos:Document': {}
    'Neos.Neos:Test.DocumentType1':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Test.DocumentType2':
      superTypes:
        'Neos.Neos:Document': true
    """
    And I have the following nodes:
      | Identifier | Path                       | Node Type                     |
      | root       | /sites                     | unstructured                  |
      | a          | /sites/a                   | Neos.Neos:Test.DocumentType1  |
      | a1         | /sites/a/a1                | Neos.Neos:Test.DocumentType2  |
    And the Fusion context node is "a1"
    And the Fusion context request URI is "http://localhost"

  Scenario: ContentCase without corresponding implementation
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCase
    """
    Then I expect the following Fusion rendering error:
    """
    The Fusion object "Neos.Neos:Test.DocumentType2" cannot be rendered:
    Most likely you mistyped the prototype name or did not define
    the Fusion prototype with "prototype(Neos.Neos:Test.DocumentType2) < prototype(...)".
    Other possible reasons are a missing parent-prototype or
    a missing "@class" annotation for prototypes without parent.
    It is also possible your Fusion file is not read because
    of a missing "include:" statement.
    """

  Scenario: ContentCase with corresponding implementation
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.DocumentType2) < prototype(Neos.Fusion:Value) {
      value = 'implementation for DocumentType2'
    }

    test = Neos.Neos:ContentCase
    """
    Then I expect the following Fusion rendering result:
    """
    implementation for DocumentType2
    """
