@user
Feature:
  User API

  Background:
    Given the following fixtures are loaded:
      | user |

  Scenario: As a user I can list User resources
    Given I send a "GET" JSON request to "/api/users"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hydra:totalItems" should be equal to "10"
    And the JSON nodes should contain:
      | hydra:member[0].id   | |
      | hydra:member[0].name | |

  Scenario: As a user I can list single User resource
    Given I send a "GET" JSON request to "/api/users/10"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON nodes should contain:
      | id   | 10       |
      | name | John Doe |

  Scenario: As a user I can create User resource
    Given I send a "POST" JSON request to "/api/users" with body:
    """
    {
      "name": "New User"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON nodes should contain:
      | id   | 11       |
      | name | New User |
    And there is row in "User" table:
      | name | New User |

  Scenario: As a user I can update User resource
    Given I send a "PUT" JSON request to "/api/users/10" with body:
    """
    {
      "name": "Updated User"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON nodes should contain:
      | id   | 10           |
      | name | Updated User |
    And there is row in "User" table:
      | name | Updated User |

  Scenario: As a user I can delete User resource
    Given I send a "DELETE" JSON request to "/api/users/10"
    Then the response status code should be 204
    And there are no rows in "User" table:
      | id |
      | 10 |
