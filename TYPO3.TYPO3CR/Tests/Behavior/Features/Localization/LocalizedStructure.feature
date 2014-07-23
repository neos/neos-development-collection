Feature: Localized structure
  In order to have nodes in different places for different languages
  As an API user of the content repository
  I need support for having independent paths for each language

  # TODO Discuss if we want to support this feature or if node path and identifier should be equivalent

  Background:
    Given I have the following nodes:
      | Path                         | Node Type                 | Properties           | Language |
      | /sites                       | unstructured              |                      | mul_ZZ   |
      | /sites/neosdemotypo3         | TYPO3.Neos.NodeTypes:Page | {"title": "Home"}    | mul_ZZ   |
      | /sites/neosdemotypo3/company | TYPO3.Neos.NodeTypes:Page | {"title": "Company"} | mul_ZZ   |
      | /sites/neosdemotypo3/service | TYPO3.Neos.NodeTypes:Page | {"title": "Company"} | mul_ZZ   |

  @fixtures
  Scenario: The same node can be fetched using different node paths in different languages
    Given I have the following nodes:
      | Identifier                           | Path                               | Node Type                 | Properties            | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/company/about | TYPO3.Neos.NodeTypes:Page | {"title": "About"}    | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/service/about | TYPO3.Neos.NodeTypes:Page | {"title": "Über uns"} | de_DE    |
    When I get a node by path "/sites/neosdemotypo3/company/about" with the following context:
      | Language      |
      | en_US, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "About"
    When I get a node by path "/sites/neosdemotypo3/service/about" with the following context:
      | Language      |
      | de_DE, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Über uns"

  @fixtures
  Scenario: Child nodes can be fetched using different node paths in different languages
    Given I have the following nodes:
      | Identifier                           | Path                                 | Node Type                 | Properties              | Language |
      | c0a3d935-4903-c668-787c-66de575c72c7 | /sites/neosdemotypo3/company/history | TYPO3.Neos.NodeTypes:Page | {"title": "History"}    | en_US    |
      | c0a3d935-4903-c668-787c-66de575c72c7 | /sites/neosdemotypo3/company/history | TYPO3.Neos.NodeTypes:Page | {"title": "Geschichte"} | de_DE    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/company/about   | TYPO3.Neos.NodeTypes:Page | {"title": "About"}      | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/service/about   | TYPO3.Neos.NodeTypes:Page | {"title": "Über uns"}   | de_DE    |
    When I get the child nodes of "/sites/neosdemotypo3/company" with filter "TYPO3.Neos:Document" and the following context:
      | Language      |
      | en_US, mul_ZZ |
    And I should have the following nodes:
      | Path                                 | Properties           | Language  |
      | /sites/neosdemotypo3/company/history | {"title": "History"} | en_US     |
      | /sites/neosdemotypo3/company/about   | {"title": "About"}   | en_US     |
    When I get the child nodes of "/sites/neosdemotypo3/company" with filter "TYPO3.Neos:Document" and the following context:
      | Language        |
      | de_DE, mul_ZZ |
    And I should have the following nodes:
      | Path                                 | Properties              | Language  |
      | /sites/neosdemotypo3/company/history | {"title": "Geschichte"} | de_DE     |
