Feature: Privilege to restrict creation of nodes

  Background:
    Given I have the following policies:
    """
    privilegeTargets:

      'Neos\ContentRepository\Security\Authorization\Privilege\Node\CreateNodePrivilege':

        'Neos.ContentRepository:Service':
          matcher: 'isDescendantNodeOf("/sites/content-repository/service/") && nodeIsOfType("Neos.ContentRepository.Testing:Document") && createdNodeIsOfType("Neos.NodeTypes:Text")'

        'Neos.ContentRepository:Company':
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
          -
            privilegeTarget: 'Neos.ContentRepository:Service'
            permission: GRANT
          -
            privilegeTarget: 'Neos.ContentRepository:Company'
            permission: GRANT
      """
    And I have the following nodes:
      | Identifier                           | Path                        | Node Type                      | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                      | unstructured                   |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository              | Neos.ContentRepository.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company      | Neos.ContentRepository.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service      | Neos.ContentRepository.Testing:Document | {"title": "Service"} | live      |

  @Isolated @fixtures
  Scenario: creating text nodes under service is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtext" child node of type "Neos.NodeTypes:Text"
    And I should get false when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.NodeTypes:Text" is granted

  @Isolated @fixtures
  Scenario: creating image nodes under service is granted to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewimage" child node of type "Neos.NodeTypes:Image"
    And I should get true when asking the node authorization service if creating a new "mynewimage" child node of type "Neos.NodeTypes:Image" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under service is granted to administrators
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "Neos.NodeTypes:Text"
    And I should get true when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.NodeTypes:Text" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under service is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | live      |
    Then I should get the following list of denied node types for this node from the node authorization service:
      | nodeTypeName              |
      | Neos.NodeTypes:Text |

  @Isolated @fixtures
  Scenario: creating text nodes under company is granted to administrators
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "Neos.NodeTypes:Text"
    And I should get true when asking the node authorization service if creating a new "mynewtext" child node of type "Neos.NodeTypes:Text" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under company is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtext" child node of type "Neos.NodeTypes:Text"
    And I should get the list of all available node types as denied node types for this node from the node authorization service
