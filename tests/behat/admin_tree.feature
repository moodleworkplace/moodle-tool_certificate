@tool @tool_certificate @moodleworkplace
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

  Scenario: Manager without manage capability should not see option to add certificate template
    And the following "permission overrides" exist:
      | capability              | permission | role    | contextlevel | reference |
      | tool/certificate:manage | Prevent    | manager | System       |           |
    And I log in as "manager"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should see "Verify certificates"
    And I should not see "Certificate images"
    And I should not see "Add certificate template"

  Scenario: Manager without manage and image capabilities should not see option to manage images
    And the following "permission overrides" exist:
      | capability              | permission | role    | contextlevel | reference |
      | tool/certificate:manage | Prevent    | manager | System       |           |
    And I log in as "manager"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Certificate images"

  @javascript
  Scenario: Issue new certificate as manager without manage capability
    And the following "permission overrides" exist:
      | capability              | permission | role    | contextlevel | reference |
      | tool/certificate:manage | Prevent    | manager | System       |           |
    And I log in as "manager"
    And I am on site homepage
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Issue certificates from this template" "link"
    And I set the field "Select users to issue certificate to" to "User One"
    And I press "Save"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    Then "User One" "text" should exist in the "tool-certificate-issues" "table"
    And I log out

  Scenario: Manager without issue capability
    And the following "permission overrides" exist:
      | capability             | permission | role    | contextlevel | reference |
      | tool/certificate:issue | Prohibit   | manager | System       |           |
    And I log in as "manager"
    And I am on site homepage
    When I navigate to "Certificates > Manage certificate templates" in site administration
    Then I should not see "Issue certificates from this template"
