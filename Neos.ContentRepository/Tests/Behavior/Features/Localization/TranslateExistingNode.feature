Feature: Translate existing node
  In order to translate existing content to new languages
  As an API user of the content repository
  I need a way to create node variant for other languages

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties           | Language | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                 | unstructured               |                      | mul_ZZ   | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository         | Neos.ContentRepository.Testing:Page | {"title": "Home"}    | mul_ZZ   | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Page | {"title": "Company"} | en_ZZ    | live      |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: An existing node can be translated to a new language by adopting it from a different context
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Unternehmen"

    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Unternehmen"

    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Company"

  @fixtures
  Scenario: An existing node can be translated to a new language in a workspace
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      | Workspace  |
      | en_ZZ, mul_ZZ | user-admin |
    And I adopt the node to the following context:
      | Language      | Workspace  |
      | de_ZZ, mul_ZZ | user-admin |
    And I set the node property "title" to "Unternehmen"

    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      | Workspace  |
      | de_ZZ, mul_ZZ | user-admin |
    Then I should have one node
    And the node property "title" should be "Unternehmen"

    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      | Workspace  |
      | en_ZZ, mul_ZZ | user-admin |
    Then I should have one node
    And the node property "title" should be "Company"

  @fixtures
  Scenario: An existing node variant will be re-used when adopting a node
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties               | Language | Workspace |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Page | {"title": "Unternehmen"} | de_ZZ    | live      |
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Firma"

    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Firma"

    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Company"

  @fixtures
  Scenario: Use a specific target dimension value in a context with fallback languages
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Language             | Target dimension: language  |
      | de_DE, de_ZZ, mul_ZZ | de_ZZ                       |
    And I set the node property "title" to "Firma"
    Then I should have a node with path "/sites/content-repository/company" and value "Firma" for property "title" for the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    And I should have a node with path "/sites/content-repository/company" and value "Company" for property "title" for the following context:
      | Language      |
      | en_ZZ, mul_ZZ |

  @fixtures
  Scenario: Setting a property on a node variant not in the best matching language creates a new variant if no explicit target dimension value is set
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language             |
      | en_US, en_ZZ, mul_ZZ |
    And I set the node property "title" to "US company"
    Then I should have a node with path "/sites/content-repository/company" and value "US company" for property "title" for the following context:
      | Language             |
      | en_US, en_ZZ, mul_ZZ |
    And I should have a node with path "/sites/content-repository/company" and value "Company" for property "title" for the following context:
      | Language      |
      | en_ZZ, mul_ZZ |

  @fixtures
  Scenario: Setting a property on a node variant not in the best matching language creates no new variant if target dimension value matches
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language             | Target dimension: language  |
      | en_US, en_ZZ, mul_ZZ | en_ZZ                       |
    And I set the node property "title" to "New company"
    Then I should have a node with path "/sites/content-repository/company" and value "New company" for property "title" for the following context:
      | Language             |
      | en_US, en_ZZ, mul_ZZ |
    And I should have a node with path "/sites/content-repository/company" and value "New company" for property "title" for the following context:
      | Language      |
      | en_ZZ, mul_ZZ |

  @fixtures
  Scenario: No node variant is created if target dimension is lower than the dimension value of an existing node variant
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language               | Target dimension: language  |
      | en_US, en_ZZ, mul_ZZ   | mul_ZZ                      |
    And I set the node property "title" to "New company"
    Then I should have a node with path "/sites/content-repository/company" and value "New company" for property "title" for the following context:
      | Language               |
      | en_US, en_ZZ, mul_ZZ   |
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language  |
      | mul_ZZ    |
    Then I should have 0 nodes

  @fixtures
  Scenario: Node variants are created for configured child nodes when materializing a node
    When I get a node by path "/sites/content-repository" with the following context:
      | Language               |
      | en_US, en_ZZ, mul_ZZ   |
    And I set the node property "title" to "New home"
    And I get a node by path "/sites/content-repository/main" with the following context:
      | Language  |
      | en_US     |
    Then I should have one node

  @fixtures
  Scenario: Node variants are created for configured child nodes when adopting it from another context
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    And I adopt the node to the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Unternehmen"
    And I get a node by path "/sites/content-repository/company/main" with the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    Then I should have one node

  @fixtures
  Scenario: Recursive adopt creates node variants for all non-aggregate descendants
    Given I have the following nodes:
      | Identifier                           | Path                                              | Node Type                       | Language |
      | cc302952-6442-11e4-9935-14109fd7a2dd | /sites/content-repository/company/contact                    | Neos.ContentRepository.Testing:Page      | en_ZZ    |
      | 74fe032a-6442-11e4-8135-14109fd7a2dd | /sites/content-repository/company/main/two-col               | Neos.ContentRepository.Testing:TwoColumn | en_ZZ    |
      | 864b6a8c-6442-11e4-8791-14109fd7a2dd | /sites/content-repository/company/main/two-col/column0/text0 | Neos.ContentRepository.Testing:Text      | en_ZZ    |
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Language      |
      | en_ZZ, mul_ZZ |
    And I adopt the node recursively to the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    And I set the node property "title" to "Unternehmen"
    And I get a node by path "/sites/content-repository/company/main/two-col/column0/text0" with the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    Then I should have one node
    When I get a node by path "/sites/content-repository/company/contact" with the following context:
      | Language      |
      | de_ZZ, mul_ZZ |
    Then I should have 0 nodes
