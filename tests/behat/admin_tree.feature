@tool @tool_certificate
Feature: View links on admin tree
  In order to manage certificate
  As a manager
  I need to be able to view, manage, issue and verify certificates

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user1    | User      | One      | one@example.com |
      | manager  | Max       | Manager  | man@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |

  Scenario: Options available for default to manager
    When I log in as "manager"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should see "Verify certificates"
    And I should see "Add certificate template"

  Scenario: Manager without manage capability should not see option to add certificate template
    When I log in as "admin"
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manage | Prevent |
    And I log out
    And I log in as "manager"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should see "Verify certificates"
    And I should not see "Certificate images"
    And I should not see "Add certificate template"

  Scenario: Manager without manage and image capabilities should not see option to manage images
    When I log in as "admin"
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manage | Prevent |
    And I log out
    And I log in as "manager"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Certificate images"

  @javascript
  Scenario: Issue new certificate as manager without manage capability
    When I log in as "admin"
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manage | Prevent |
    And I log out
    And I log in as "manager"
    And I am on site homepage
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Issue new certificate from this template" "link"
    And I set the field "Select users to issue certificate for" to "User One"
    And I wait until the page is ready
    And I press "Issue new certificates"
    Then I should see "One issue was created"

  Scenario: Manager without issue capability
    When I log in as "admin"
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:issue | Prohibit |
    And I log out
    And I log in as "manager"
    And I am on site homepage
    When I navigate to "Certificates > Manage certificate templates" in site administration
    Then I should not see "Issue new certificate from this template"