Feature: Node variants
  In order to manage content with multiple content dimensions and fallbacks
  As an API user of the content repository
  I need a way to retrieve information about node variants

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties               | Workspace | Language |
      | 85f17826-64d1-11e4-a6e3-14109fd7a2dd | /sites                 | unstructured               |                          | live      | mul_ZZ   |
      | 8952d7b2-64d1-11e4-9fe2-14109fd7a2dd | /sites/typo3cr         | TYPO3.TYPO3CR.Testing:Page | {"title": "Startseite"}  | live      | mul_ZZ   |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Page | {"title": "Company"}     | live      | en       |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Page | {"title": "Unternehmen"} | live      | de       |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Page | {"title": "Firma"}       | live      | de_CH    |
      | 8ed74376-64d1-11e4-b98b-14109fd7a2dd | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Page | {"title": "Entreprise"}  | live      | fr       |

  @fixtures
  Scenario: Get other node variants of an aggregate node
    When I get a node by path "/sites/typo3cr/company" with the following context:
      | Language |
      | de       |
    And I get other node variants of the node
    Then I should have the following nodes in any order:
      | Path                   | Language |
      | /sites/typo3cr/company | en       |
      | /sites/typo3cr/company | de_CH    |
      | /sites/typo3cr/company | fr       |

  @fixtures
  Scenario: Get other node variants of an aggregate node with fallbacks
    When I get a node by path "/sites/typo3cr/company" with the following context:
      | Language  |
      | de_CH, de |
    And I get other node variants of the node
    Then I should have the following nodes in any order:
      | Path                   | Language |
      | /sites/typo3cr/company | en       |
      | /sites/typo3cr/company | de       |
      | /sites/typo3cr/company | fr       |

  @fixtures
  Scenario: Get node variants for an identifier from the context
    When I get node variants of "8ed74376-64d1-11e4-b98b-14109fd7a2dd" with the following context:
      | Language |
      | mul_ZZ   |
    Then I should have the following nodes in any order:
      | Path                   | Language |
      | /sites/typo3cr/company | en       |
      | /sites/typo3cr/company | de       |
      | /sites/typo3cr/company | de_CH    |
      | /sites/typo3cr/company | fr       |
