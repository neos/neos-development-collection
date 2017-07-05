Feature: Find site node in non-default content dimension context
  In order to determine the full path leading to a node with non-default dimension values
  As an API user of the content repository
  I need a way to access the site node regardless of the current dimension value context

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets                                                                                        |
      | language   | en      | de=de; fr=fr; nl=nl; es=es; it=it                                                              |
      | country    | int     | be=be,int; de=de,int; fr=fr,int lu=lu,int; nl=nl,int; ch=ch,int; gb=gb,int; us=us,int; int=int |

    And I have the following nodes:
      | Identifier                           | Path                                    | Node Type                  | Properties                         | Dimension: language | Target dimension: language | Dimension: country | Target dimension: country |
      | 0befa678-79ad-11e5-b465-14109fd7a2dd | /sites                                  | unstructured               |                                    |                     |                            |                    |                           |
      | 8f9b7036-17b7-b7e0-c3e0-844ffd82aace | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home DE DE"}            | de                  | de                         | de                 | de                        |
      | a00bf041-6939-172f-5a71-c29495301c7a | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home DE INT"}           | de                  | de                         | int                | int                       |
      | b02c29d2-bf3d-1092-44a7-43a92834fa72 | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home EN FR"}            | en                  | en                         | fr                 | fr                        |
      | c6499b0c-f9a2-e94b-860a-6ac7e57bbbb7 | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home EN INT"}           | en                  | en                         | int                | int                       |
      | 56b1c647-4a08-ab32-0995-ab452222ec70 | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home NL BE"}            | nl                  | nl                         | be                 | be                        |
      | 6b4a6b39-5a49-fa66-b0a2-b312b02183a1 | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home NL LU"}            | nl                  | nl                         | lu                 | lu                        |
      | 1f46a576-20bb-225e-a517-682e07b4a046 | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home FR FR"}            | fr                  | fr                         | fr                 | fr                        |
      | 61eb69ba-de68-4ec2-079c-b1d04fc2719a | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home ES INT"}           | es                  | es                         | int                | int                       |
      | e3424a9b-f5cc-f294-2366-2764187e5c4f | /sites/wwwexamplecom                    | Neos.ContentRepository.Testing:Page | {"title": "Home IT INT"}           | it                  | it                         | int                | int                       |
      | a616c868-67dd-4c30-83ef-e14893bc7bb9 | /sites/wwwexamplecom/shop               | Neos.ContentRepository.Testing:Page | {"title": "Shop DE INT"}           | de                  | de                         | int                | int                       |
      | fcc8f596-c358-4fc1-8ba4-efe864e07e0b | /sites/wwwexamplecom/shop               | Neos.ContentRepository.Testing:Page | {"title": "Shop EN INT"}           | en                  | en                         | int                | int                       |
      | 92d9ca30-c769-4660-94e0-dc93bcfcbb7a | /sites/wwwexamplecom/shop               | Neos.ContentRepository.Testing:Page | {"title": "Shop ES INT"}           | es                  | es                         | int                | int                       |
      | affba0f6-52d8-4456-93ea-d208cad6c97e | /sites/wwwexamplecom/shop               | Neos.ContentRepository.Testing:Page | {"title": "Shop IT INT"}           | it                  | it                         | int                | int                       |
      | e2dba764-27c8-4279-aadf-e060f0b6621d | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine DE DE"}  | de                  | de                         | de                 | de                        |
      | bbde4a73-ec71-40de-8dc0-fc1beae9accd | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine DE INT"} | de                  | de                         | int                | int                       |
      | 723a5937-5429-45c6-ac8a-7ac55b6c91d5 | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine EN FR"}  | en                  | en                         | fr                 | fr                        |
      | 0f7071ab-4a4f-403c-9503-68f7bf34d8cc | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine EN INT"} | en                  | en                         | int                | int                       |
      | 397b9558-bac7-46d2-bf7a-13249cec1195 | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine NL BE"}  | nl                  | nl                         | be                 | be                        |
      | 607d28e4-8e5c-4746-9f02-ab85a07c1b97 | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine NL LU"}  | nl                  | nl                         | lu                 | lu                        |
      | 0d80f01c-1234-4b73-9c0f-d1ec6f4592ed | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine FR FR"}  | fr                  | fr                         | fr                 | fr                        |
      | 46c505c2-fb5f-46f5-aba1-37fe17a1a000 | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine ES INT"} | es                  | es                         | int                | int                       |
      | ebd4bef1-5f51-4348-9d31-f221cc8cc1d7 | /sites/wwwexamplecom/shop/coffeemachine | Neos.ContentRepository.Testing:Page | {"title": "Coffee Machine IT INT"} | it                  | it                         | int                | int                       |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Retrieve site node from a node which is connected to the site node through a node with the same dimension values
    When I get a node by path "/sites/wwwexamplecom/shop" with the following context:
      | Dimension: language | Dimension: country |
      | de                  | int                |
    Then I should have one node
    And the node property "title" should be "Shop DE INT"

    When I get a node by path "/sites/wwwexamplecom/shop/coffeemachine" with the following context:
      | Dimension: language | Dimension: country |
      | de                  | int                |
    Then I should have one node
    And the node property "title" should be "Coffee Machine DE INT"

    When I run getNode with the path "../../" on the current node
    Then I should have one node
    And the node property "title" should be "Home DE INT"

  @fixtures
  Scenario: Retrieve site node from a node which is NOT connected to the site node through a node with the same dimension values
    When I get a node by identifier "e2dba764-27c8-4279-aadf-e060f0b6621d" with the following context:
      | Dimension: language | Dimension: country |
      | de                  | de                 |
    Then I should have one node
    And the node property "title" should be "Coffee Machine DE DE"

    When I run getNode with the path "../../" on the current node
    Then I should have one node
    And the node property "title" should be "Home DE DE"
