Feature: Privilege to restrict creation of nodes

  Background:
    Given I have the following policies:
    """
    privilegeTargets:
      'Neos\ContentRepository\Security\Authorization\Privilege\Node\CreateNodePrivilege':
        'Neos.ContentRepository.Testing:ServiceText':
          matcher: 'isDescendantNodeOf("/sites/content-repository/service/") && createdNodeIsOfType("Neos.ContentRepository.Testing:Text")'

        'Neos.ContentRepository.Testing:ServiceTextSubnodes':
          matcher: 'isDescendantNodeOf("/sites/content-repository/service/") && createdNodeIsOfType("Neos.ContentRepository.Testing:Text", true)'

        'Neos.ContentRepository.Testing:Company':
          matcher: 'isDescendantNodeOf("68ca0dcd-2afb-ef0e-1106-a5301e65b8a0")'

    roles:
      'Neos.Flow:Everybody':
        privileges: []

      'Neos.Flow:Anonymous':
        privileges: []

      'Neos.Flow:AuthenticatedUser':
        privileges: []

      'Neos.ContentRepository:Administrator':
        privileges:
          - privilegeTarget: 'Neos.ContentRepository.Testing:Company'
            permission: GRANT

      'Neos.ContentRepository.Testing:CreateServiceText':
        - privilegeTarget: 'Neos.ContentRepository.Testing:ServiceText'
          permission: GRANT

      'Neos.ContentRepository.Testing:CreateServiceTextSubnodes':
        - privilegeTarget: 'Neos.ContentRepository.Testing:ServiceTextSubnodes'
          permission: GRANT
      """
    And I have the following nodes:
      | Identifier                           | Path                                   | Node Type                               | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                                 | unstructured                            |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository              | Neos.ContentRepository.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company      | Neos.ContentRepository.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service      | Neos.ContentRepository.Testing:Document | {"title": "Service"} | live      |

  @Isolated @fixtures
  Scenario: creating text nodes under service is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text"
    And I should get false when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text" is granted

  @Isolated @fixtures
  Scenario: creating TextWithImage nodes under service is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtextwithimage" child node of type "Neos.ContentRepository.Testing:TextWithImage"
    And I should get false when asking the node authorization service if creating a new "mynewtextwithimage" child node of type "Neos.ContentRepository.Testing:TextWithImage" is granted

  @Isolated @fixtures
  Scenario: creating image nodes under service is granted to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewimage" child node of type "Neos.ContentRepository.Testing:Image"
    And I should get true when asking the node authorization service if creating a new "mynewimage" child node of type "Neos.ContentRepository.Testing:Image" is granted

  @Isolated @fixtures
  Scenario: unauthenticated users cannot create Text or TextWithImage nodes under service
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should get the following list of denied node types for this node from the node authorization service:
      | nodeTypeName                                 |
      | Neos.ContentRepository.Testing:Text          |
      | Neos.ContentRepository.Testing:TextWithImage |

  @Isolated @fixtures
  Scenario: CreateServiceText role cannot create TextWithImage nodes under service
    Given I am authenticated with role "Neos.ContentRepository.Testing:CreateServiceText"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should get the following list of denied node types for this node from the node authorization service:
      | nodeTypeName                                 |
      | Neos.ContentRepository.Testing:TextWithImage |

  @Isolated @fixtures
  Scenario: creating text nodes under service is granted to role CreateServiceText
    Given I am authenticated with role "Neos.ContentRepository.Testing:CreateServiceText"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text"
    And I should get true when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under service is granted to role CreateServiceTextSubnodes
    Given I am authenticated with role "Neos.ContentRepository.Testing:CreateServiceTextSubnodes"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text"
    And I should get true when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text" is granted

  @Isolated @fixtures
  Scenario: creating TextWithImage nodes under service is granted to role CreateServiceTextSubnodes
    Given I am authenticated with role "Neos.ContentRepository.Testing:CreateServiceTextSubnodes"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtextwithimage" child node of type "Neos.ContentRepository.Testing:TextWithImage"
    And I should get true when asking the node authorization service if creating a new "mynewtextwithimage" child node of type "Neos.ContentRepository.Testing:TextWithImage" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under company is granted to administrators
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text"
    And I should get true when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under company is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtext" child node of type "Neos.ContentRepository.Testing:Text"
    And I should get the list of all available node types as denied node types for this node from the node authorization service
