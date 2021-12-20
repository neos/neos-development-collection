@fixtures
Feature: Node publishing integrity check - dimension matrix check
  TODO: describe what we want to test here

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Presets                |
      | language   | en      | en=en; de=de; ch=ch,de |

    Given I have the following nodes:
      | Identifier                           | Path                     | Node Type                           | Properties            | Language |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites                   | unstructured                        |                       | de       |
      | 86198d18-8c4a-41eb-95fa-56223b2a3a97 | /sites                   | unstructured                        |                       | en       |
      | 594cd631-cf19-4072-9ee8-f8d840e85f5f | /sites/cr                | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | de       |
      | 594cd631-cf19-4072-9ee8-f8d840e85f5f | /sites/cr                | Neos.ContentRepository.Testing:Page | {"title": "CR SEITE"} | en       |
      | 94d5a8a2-d0d2-427b-af0a-2e4152f102ee | /sites/other             | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | de       |
      | 94d5a8a2-d0d2-427b-af0a-2e4152f102ee | /sites/other             | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | ch       |
      | 94d5a8a2-d0d2-427b-af0a-2e4152f102ee | /sites/other             | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | en       |
      | fe762cec-9d28-42cc-a165-295066941e0d | /sites/swiss-only        | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | ch       |
      | 8752ece7-b99a-4b61-93a8-30af230cb023 | /sites/english-only      | Neos.ContentRepository.Testing:Page | {"title": "Other"}    | en       |

  Scenario: Changes to a node in the german dimension do also effect the swiss dimension, when swiss variant IS NOT materialized, does not effect the english dimension
    Given I get a node by path "/sites/cr" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    When I remove the node
    Then only the languages "de;ch,de" are effected when publishing the following nodes:
      | path                     | Workspace  | Language |
      | /sites/cr | user-admin | de       |

  Scenario: Changes to a node in the swiss dimension, which shines through from german dimension, does effect german and swiss dimension, not the english dimension
    Given I get a node by path "/sites/cr" with the following context:
      | Workspace  | Language |
      | user-admin | ch,de    |
    When I remove the node
    Then only the languages "de;ch,de" are effected when publishing the following nodes:
      | path                     | Workspace  | Language |
      | /sites/cr | user-admin | de       |

  Scenario: Changes to a node in the german dimension, does NOT effect swiss dimension, when swiss variant IS materialized, does not effect the english dimension
    Given I get a node by path "/sites/other" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    When I remove the node
    Then only the languages "de" are effected when publishing the following nodes:
      | path         | Workspace  | Language |
      | /sites/other | user-admin | de       |

  Scenario: Changes to a node in the swiss dimension, which is materialized, does not effect german and english dimensions
    Given I get a node by path "/sites/other" with the following context:
      | Workspace  | Language |
      | user-admin | ch,de    |
    When I remove the node
    Then only the languages "ch,de" are effected when publishing the following nodes:
      | path         | Workspace  | Language |
      | /sites/other | user-admin | ch,de    |

  Scenario: Changes to a node in the swiss dimension, which only exists in the swiss dimension, does not effect german and english dimensions
    Given I get a node by path "/sites/swiss-only" with the following context:
      | Workspace  | Language |
      | user-admin | ch,de    |
    When I remove the node
    Then only the languages "ch,de" are effected when publishing the following nodes:
      | path              | Workspace  | Language |
      | /sites/swiss-only | user-admin | ch,de    |

  Scenario: Changes to a node in the english dimension, which only exists in the english dimension, does not effect the german and swiss dimension
    Given I get a node by path "/sites/english-only" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    When I remove the node
    Then only the languages "en" are effected when publishing the following nodes:
      | path                | Workspace  | Language |
      | /sites/english-only | user-admin | en       |
