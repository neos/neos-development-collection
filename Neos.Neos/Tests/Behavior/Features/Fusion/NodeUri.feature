@fixtures
Feature: Tests for the "Neos.Neos:NodeUri" Fusion prototype

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
    And the Fusion context request URI is "http://localhost"

  Scenario: Node uris
    And the Fusion context node is "a1"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Fusion:DataStructure {
      @process.toString = ${Array.join(Array.map(value, (v, k) => k + ': ' + v), String.chr(10))}
      uri = Neos.Neos:NodeUri {
        node = ${node}
      }
      link = Neos.Neos:NodeLink {
        node = ${node}
      }
      uriWithSection = Neos.Neos:NodeUri {
        node = ${node}
        section = 'foo'
      }
      uriWithAppendExceedingArguments = Neos.Neos:NodeUri {
        node = ${node}
        arguments = ${{q: 'abc'}}
      }
      absoluteUri = Neos.Neos:NodeUri {
        node = ${node}
        absolute = true
      }
      mixed = Neos.Neos:NodeUri {
        node = ${node}
        section = 'foo'
        arguments = ${{q: 'abc'}}
        absolute = true
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    uri: /en/a1
    link: <a href="/en/a1">Neos.Neos:Test.DocumentType (a1)</a>
    uriWithSection: /en/a1#foo
    uriWithAppendExceedingArguments: /en/a1?q=abc
    absoluteUri: http://localhost/en/a1
    mixed: http://localhost/en/a1?q=abc#foo
    """

  Scenario: Node as string node path syntax
    And the Fusion context node is "a"
    When I execute the following Fusion code:
    """fusion
    include: resource://Neos.Fusion/Private/Fusion/Root.fusion
    include: resource://Neos.Neos/Private/Fusion/Root.fusion

    test = Neos.Fusion:DataStructure {
      @process.toString = ${Array.join(Array.map(value, (v, k) => k + ': ' + v), String.chr(10))}
      sitesRootPath = Neos.Neos:NodeUri {
        node = '/sites/a/a1'
      }
      siteRelativePath = Neos.Neos:NodeUri {
        node = '~/a1'
      }
      site = Neos.Neos:NodeUri {
        node = '~'
      }
      relativePath = Neos.Neos:NodeUri {
        node = 'a1'
      }
      dotRelativePath = Neos.Neos:NodeUri {
        node = './a1'
      }
      dotTraversalRelativePath = Neos.Neos:NodeUri {
        @context.childNode = ${q(node).find('#a1').get(0)}
        node = '..'
        baseNodeName = 'childNode'
      }
      contextPath = Neos.Neos:NodeUri {
        node = '/sites/a/a1@live'
      }
    }
    """
    Then I expect the following Fusion rendering result:
    """
    sitesRootPath: /en/a1
    siteRelativePath: /en/a1
    site: /
    relativePath: /en/a1
    dotRelativePath: /en/a1
    dotTraversalRelativePath: /
    contextPath: /en/a1
    """
