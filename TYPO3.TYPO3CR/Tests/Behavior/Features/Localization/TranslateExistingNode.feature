Feature: Translate existing node
  In order to translate existing content to new languages
  As an API user of the content repository
  I need a way to create node variant for other languages

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties           | Language |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                       | unstructured              |                      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3         | TYPO3.Neos.NodeTypes:Page | {"title": "Home"}    | mul_ZZ   |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/company | TYPO3.Neos.NodeTypes:Page | {"title": "Company"} | en_ZZ    |

  @fixtures
  Scenario: An existing node can be translated to a new language by adopting it from a different context
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Unternehmen"

    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Unternehmen"

    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Company"

  @fixtures
  Scenario: An existing node variant will be re-used when adopting a node
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                 | Properties               | Language |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/company | TYPO3.Neos.NodeTypes:Page | {"title": "Unternehmen"} | de_ZZ    |
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Firma"

    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Firma"

    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |
    Then I should have one node
    And The node property "title" should be "Company"

  @fixtures
  Scenario: Use a specific target dimension value in a context with fallback languages
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages       |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Languages              | Target dimension: languages |
      | de_DE, de_ZZ, mul_ZZ | de_ZZ                         |
    And I set the node property "title" to "Firma"
    Then I should have a node with path "/sites/neosdemotypo3/company" and value "Firma" for property "title" for the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    And I should have a node with path "/sites/neosdemotypo3/company" and value "Company" for property "title" for the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |

  @fixtures
  Scenario: Setting a property on a node variant not in the best matching language creates a new variant if no explicit target dimension value is set
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages            |
      | en_US, en_ZZ, mul_ZZ |
    And I set the node property "title" to "US company"
    Then I should have a node with path "/sites/neosdemotypo3/company" and value "US company" for property "title" for the following context:
      | Languages            |
      | en_US, en_ZZ, mul_ZZ |
    And I should have a node with path "/sites/neosdemotypo3/company" and value "Company" for property "title" for the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |

  @fixtures
  Scenario: Setting a property on a node variant not in the best matching language creates no new variant if target dimension value matches
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages            | Target dimension: languages |
      | en_US, en_ZZ, mul_ZZ | en_ZZ                       |
    And I set the node property "title" to "New company"
    Then I should have a node with path "/sites/neosdemotypo3/company" and value "New company" for property "title" for the following context:
      | Languages            |
      | en_US, en_ZZ, mul_ZZ |
    And I should have a node with path "/sites/neosdemotypo3/company" and value "New company" for property "title" for the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |

  @fixtures
  Scenario: No node variant is created if target dimension is lower than the dimension value of an existing node variant
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages              | Target dimension: languages |
      | en_US, en_ZZ, mul_ZZ   | mul_ZZ                      |
    And I set the node property "title" to "New company"
    Then I should have a node with path "/sites/neosdemotypo3/company" and value "New company" for property "title" for the following context:
      | Languages              |
      | en_US, en_ZZ, mul_ZZ   |
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages |
      | mul_ZZ    |
    Then I should have 0 nodes

  @fixtures
  Scenario: Node variants are created for configured child nodes when materializing a node
    When I get a node by path "/sites/neosdemotypo3" with the following context:
      | Languages              |
      | en_US, en_ZZ, mul_ZZ   |
    And I set the node property "title" to "New home"
    And I get a node by path "/sites/neosdemotypo3/main" with the following context:
      | Languages |
      | en_US     |
    Then I should have one node

  @fixtures
  Scenario: Node variants are created for configured child nodes when adopting it from another context
    When I get a node by path "/sites/neosdemotypo3/company" with the following context:
      | Languages     |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Unternehmen"
    And I get a node by path "/sites/neosdemotypo3/company/main" with the following context:
      | Languages     |
      | de_ZZ, mul_ZZ |
    Then I should have one node
