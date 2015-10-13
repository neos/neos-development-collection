Feature: Privilege to restrict creation of nodes

  Background:
    Given I have the following policies:
    """
    privilegeTargets:

      'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege':

        'TYPO3.TYPO3CR:Service':
          matcher: 'isDescendantNodeOf("/sites/typo3cr/service/") && nodeIsOfType("TYPO3.TYPO3CR.Testing:Document") && createdNodeIsOfType("TYPO3.Neos.NodeTypes:Text")'

        'TYPO3.TYPO3CR:Company':
          matcher: 'isDescendantNodeOf("68ca0dcd-2afb-ef0e-1106-a5301e65b8a0")'

    roles:
      'TYPO3.Flow:Everybody':
        privileges: []

      'TYPO3.Flow:Anonymous':
        privileges: []

      'TYPO3.Flow:AuthenticatedUser':
        privileges: []

      'TYPO3.TYPO3CR:Administrator':
        privileges:
          -
            privilegeTarget: 'TYPO3.TYPO3CR:Service'
            permission: GRANT
          -
            privilegeTarget: 'TYPO3.TYPO3CR:Company'
            permission: GRANT
      """
    And I have the following nodes:
      | Identifier                           | Path                        | Node Type                      | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                      | unstructured                   |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr              | TYPO3.TYPO3CR.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/company      | TYPO3.TYPO3CR.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service      | TYPO3.TYPO3CR.Testing:Document | {"title": "Service"} | live      |

  @Isolated @fixtures
  Scenario: creating text nodes under service is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should get FALSE when asking the node authorization service if creating a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text" is granted

  @Isolated @fixtures
  Scenario: creating image nodes under service is granted to everybody
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewimage" child node of type "TYPO3.Neos.NodeTypes:Image"
    And I should get TRUE when asking the node authorization service if creating a new "mynewimage" child node of type "TYPO3.Neos.NodeTypes:Image" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under service is granted to administrators
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should get TRUE when asking the node authorization service if creating a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under service is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace |
      | live      |
    Then I should get the following list of denied node types for this node from the node authorization service:
      | nodeTypeName              |
      | TYPO3.Neos.NodeTypes:Text |

  @Isolated @fixtures
  Scenario: creating text nodes under company is granted to administrators
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace |
      | live      |
    Then I should be granted to create a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should get TRUE when asking the node authorization service if creating a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text" is granted

  @Isolated @fixtures
  Scenario: creating text nodes under company is denied to everybody
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace |
      | live      |
    Then I should not be granted to create a new "mynewtext" child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should get the list of all available node types as denied node types for this node from the node authorization service