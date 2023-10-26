@fixtures
Feature: Tests for the "Neos.Neos:ContentCollection" Fusion prototype

  Background:
    Given I have the site "a"
    And I have the following NodeTypes configuration:
    """yaml
    'unstructured': {}
    'Neos.Neos:FallbackNode': {}
    'Neos.Neos:Document': {}
    'Neos.Neos:ContentCollection': {}
    'Neos.Neos:Content': {}
    'Neos.Neos:Test.DocumentType':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.Neos:Test.ContentType':
      superTypes:
        'Neos.Neos:Content': true
    """
    And I have the following nodes:
      | Identifier | Path     | Node Type                   |
      | root       | /sites   | unstructured                |
      | a          | /sites/a | Neos.Neos:Test.DocumentType |
    And the Fusion context node is "a"
    And the Fusion context request URI is "http://localhost"

  Scenario: missing Neos.Neos.ContentCollection node
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCollection
    """
    Then I expect the following Fusion rendering error:
    """
    No content collection of type Neos.Neos:ContentCollection could be found in the current node (/sites/a) or at the path "to-be-set-by-user". You might want to adjust your node type configuration and create the missing child node through the "./flow node:repair --node-type Neos.Neos:Test.DocumentType" command.
    """

  Scenario: invalid nodePath
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCollection {
      nodePath = 'invalid'
    }
    """
    Then I expect the following Fusion rendering error:
    """
    No content collection of type Neos.Neos:ContentCollection could be found in the current node (/sites/a) or at the path "invalid". You might want to adjust your node type configuration and create the missing child node through the "./flow node:repair --node-type Neos.Neos:Test.DocumentType" command.
    """

  Scenario: empty ContentCollection
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Neos:ContentCollection {
      nodePath = 'main'
    }
    """
    Then I expect the following Fusion rendering result as HTML:
    """
    <div class="neos-contentcollection"></div>
    """

  Scenario:
    When I have the following nodes:
      | Identifier | Path                   | Node Type                  |
      | content1   | /sites/a/main/content1 | Neos.Neos:Test.ContentType |
      | content2   | /sites/a/main/content2 | Neos.Neos:Test.ContentType |
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    prototype(Neos.Neos:Test.ContentType) < prototype(Neos.Fusion:Value) {
      value = ${node.identifier + ' (' + node.nodeType.name + ') '}
    }

    test = Neos.Neos:ContentCollection {
      nodePath = 'main'
    }
    """
    Then I expect the following Fusion rendering result as HTML:
    """
    <div class="neos-contentcollection">content1 (Neos.Neos:Test.ContentType) content2 (Neos.Neos:Test.ContentType) </div>
    """
