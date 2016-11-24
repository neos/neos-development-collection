Feature: Export and Import of sites containing content dimensions
  In order to backup a neos website, or transfer it to a different website
  As a user of the system
  I need a way export and import content, including content dimensions.

  @fixtures
  Scenario: Export and re-import a site with dimensions
    Given I have the site "neosdemotypo3"
    And I have the following nodes:
      | Identifier                           | Path                               | Node Type                 | Properties            | Language |
      | 35df233d-0970-499a-8406-cb29e164acc6 | /sites                             | unstructured              |                       | mul_ZZ   |
      | cbc98b0e-9742-4b55-90f7-e81bb2ba5a97 | /sites/neosdemotypo3               | Neos.Neos.NodeTypes:Page | {"title": "Home"}     | mul_ZZ   |
      | cded55cd-e74b-4398-be70-01cf5e3273a6 | /sites/neosdemotypo3/company       | Neos.Neos.NodeTypes:Page | {"title": "Company"}  | mul_ZZ   |
      | 4e32260c-0fbf-4963-ab2d-dab1e684deb5 | /sites/neosdemotypo3/service       | Neos.Neos.NodeTypes:Page | {"title": "Company"}  | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/company/about | Neos.Neos.NodeTypes:Page | {"title": "About"}    | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3/service/about | Neos.Neos.NodeTypes:Page | {"title": "Über uns"} | de_DE    |

    When I export the site "neosdemotypo3"
    And I prune all sites
    Then I get a node by path "/sites/neosdemotypo3/company/about" with the following context:
      | Language      |
      | en_US, mul_ZZ |
    Then I should have 0 nodes

    When I import the last exported site

    And I get a node by path "/sites/neosdemotypo3/company/about" with the following context:
      | Language      |
      | en_US, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "About"
    When I get a node by path "/sites/neosdemotypo3/service/about" with the following context:
      | Language             |
      | de_DE, en_US, mul_ZZ |
    Then I should have one node
    And the node property "title" should be "Über uns"
