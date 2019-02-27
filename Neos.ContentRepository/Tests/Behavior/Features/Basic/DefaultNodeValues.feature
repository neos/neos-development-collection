Feature: Set Default Node Values on node creation
  In order to create nodes with a certain set of default properties
  As an API user of the content repository
  I need support to configure the default properties of a node.

  @fixtures
  Scenario: default node values for public properties
    Given I have the following NodeTypes configuration:
    """
    unstructured: []
    'Neos.ContentRepository.Testing:DefaultProperties':
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    """
    And I have the following nodes:
      | Identifier                           | Path   | Node Type                               |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /node-a | Neos.ContentRepository.Testing:DefaultProperties |
    When I get a node by path "/node-a" with the following context:
      | Workspace |
      | live      |
    Then the node property "text" should be "My default text"


  @fixtures
  Scenario: default node values for internal node properties which start with "_" (NEOS-169)
    Given I have the following NodeTypes configuration:
    """
    unstructured: []
    'Neos.ContentRepository.Testing:DefaultProperties':
      properties:
        '_hiddenInIndex':
          type: boolean
          defaultValue: true
    """
    And I have the following nodes:
      | Identifier                           | Path   | Node Type                               |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /node-a | Neos.ContentRepository.Testing:DefaultProperties |
    When I get a node by path "/node-a" with the following context:
      | Workspace |
      | live      |
    Then the node should be hidden in index
