Feature: ForkContentStream Without Dimensions

  We have only one node underneath the root node: /foo.
  LIVE Content Stream ID: cs-identifier
  We fork the live content stream as ID user-cs-identifier
  and then we commit a modification in the LIVE content stream.
  We then expect the *forked* content stream to contain the *original* value; and the *live* content stream must contain the changed value.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | nodeAggregateId             | "nody-mc-nodeface"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                  | "child"                                  |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "live"                     |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "original value"} |
