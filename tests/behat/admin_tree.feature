@tool @tool_certificate
Feature: View links on admin tree
  In order to manage certificate
  As a manager
  I need to be able to view, manage, issue and verify certificates

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user1    | User      | One      | one@example.com |
      | user2    | User      | Two      | two@example.com |
      | manager  | Max       | Manager  | man@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |


  Scenario: All options available for default to manager
    When I log in as "manager"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should see "Verify certificates"
    And I should see "Add certificate template"
    And I should see "Certificate images"

#    And I set the following system permissions of "Manager" role:
#      | capability | permission |
#      | tool/policy:acceptbehalf | Allow |
