Feature: Nodes can be created

  - CreateInto
  - TODO: CreateAfter
  - TODO: CreateBefore

  for
  - Document Nodes
  - TODO: Content Nodes

  Background:
    Given I start with a clean database only once per feature
    Given I execute the flow command "user:create" with the following arguments only once per feature:
      | Name      | Value         |
      | username  | admin         |
      | password  | password      |
      | firstName | A             |
      | lastName  | D             |
      | roles     | Administrator |
    Given I execute the flow command "site:import" with the following arguments only once per feature:
      | Name       | Value     |
      | packageKey | Neos.Demo |
    And I execute the flow command "contentrepositorymigrate:run" only once per feature
    And I am logged in as "admin" "password"
    Given I am in the active content stream of workspace "user-admin" and Dimension Space Point {"language": "en_US"}
    And I get the node address for the node at path "/neosdemo", remembering it as "HOMEPAGE"

  Scenario: CreateInto on Document Nodes
    When I send the following changes:
    # TODO: create a separate testcase with server-generated name (i.e. without "title" property)
      | Type                    | Subject Node Address | Payload                                                                                                                                                                                                                                                      |
      | Neos.Neos.Ui:CreateInto | HOMEPAGE             | {"nodeType":"Neos.NodeTypes:Page", "data": {"title":"newnode"}, "parentContextPath": "HOMEPAGE", "parentDomAddress": {"contextPath": "HOMEPAGE", "fusionPath": "landingPage<Neos.NodeTypes:Page>/body<Neos.Fusion:Template>/footer/__meta/context/node<>"}} |
    Then the feedback contains "Neos.Neos.Ui:UpdateWorkspaceInfo"
    Then the feedback contains "Neos.Neos.Ui:UpdateNodeInfo"
    Then the feedback contains "Neos.Neos.Ui:NodeCreated"

    #When the graph projection is fully up to date
    #And I get the node at path "/neosdemo/newnode"
    #And I expect the current Node to have the properties:
    #  | Key            | Value   |
    #  | title          | newnode |
    #  | urlPathSegment | newnode |
