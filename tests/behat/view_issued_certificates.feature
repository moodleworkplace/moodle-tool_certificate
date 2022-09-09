@tool @tool_certificate @moodleworkplace
Feature: Being able to view the certificates that have been issued
  In order to ensure that a user can view the certificates that have been issued
  As an admin
  I need to view the certificates that have been issued

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager0 | Manager   | Zero     | manager0@example.com |
      | manager1 | Manager   | One      | manager1@example.com |
      | user11   | User      | 11       | user11@example.com   |
      | user12   | User      | 12       | user12@example.com   |
      | user21   | User      | 21       | user21@example.com   |
      | user22   | User      | 22       | user22@example.com   |
    And the following "categories" exist:
      | name      | category | idnumber |
      | Category1 | 0        | CAT1     |
      | Category2 | 0        | CAT2     |
    And the following certificate templates exist:
      | name          | category  |
      | Certificate 0 |           |
      | Certificate 1 | Category1 |
      | Certificate 2 | Category2 |
    And the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
      | Certificate 2 | user21 |
      | Certificate 0 | user22 |
      | Certificate 0 | user12 |
    And the following "roles" exist:
      | shortname             | name                        | archetype |
      | certificatemanager    | Certificate manager         |           |
      | certificateissuer     | Certificate issuer          |           |
      | certificateviewer     | Certificate viewer          |           |
    And the following "role assigns" exist:
      | user     | role                 | contextlevel | reference |
      | manager0 | certificatemanager   | System       |           |
    And the following "permission overrides" exist:
      | capability                           | permission | role                  | contextlevel | reference |
      | tool/certificate:manage              | Allow      | certificatemanager    | System       |           |
      | tool/certificate:issue               | Allow      | certificateissuer     | System       |           |
      | tool/certificate:viewallcertificates | Allow      | certificateviewer     | System       |           |
      | moodle/site:configview               | Allow      | certificatemanager    | System       |           |
      | moodle/site:configview               | Allow      | certificateviewer     | System       |           |

  @javascript
  Scenario: View the issued certificates as manager
    And I log in as "manager0"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I navigate to "Issued certificates" in current page administration
    And I should see "User 11"
    And I should see "User 12"
    And I should not see "User 21"
    And I should not see "User 22"

  @javascript
  Scenario: Revoke an issued certificate not possible without permissions
    When I log in as "manager0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I navigate to "Issued certificates" in current page administration
    And I open the action menu in "User 12" "table_row"
    And I should not see "Revoke"

  @javascript
  Scenario: Revoke an issued certificate as manager
    When the following "role assigns" exist:
      | user     | role                 | contextlevel | reference |
      | manager0 | certificateissuer    | System       |           |
    And I log in as "manager0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I navigate to "Issued certificates" in current page administration
    And I press "Revoke" action in the "User 12" report row
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I should see "User 12"
    And I press "Revoke" action in the "User 12" report row
    And I click on "Revoke" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I should not see "User 12"

  Scenario: Verify any certificate as a guest using the site-wide URL
    And the following "permission overrides" exist:
      | capability              | permission | role  | contextlevel | reference |
      | tool/certificate:verify | Allow      | guest | System       |           |
    And I visit the sites certificates verification url
    And I verify the "Certificate 1" site certificate for the user "user11"
    And I verify the "Certificate 1" site certificate for the user "user12"
    And I verify the "Certificate 2" site certificate for the user "user21"
    And I verify the "Certificate 0" site certificate for the user "user22"
    And I verify the "Certificate 0" site certificate for the user "user12"

  Scenario: User who can verify certificates but can not manage or issue should not see it in site administration
    And the following "permission overrides" exist:
      | capability              | permission | role | contextlevel | reference |
      | tool/certificate:verify | Allow      | user | System       |           |
      | moodle/site:configview  | Allow      | user | System       |           |
    When I log in as "manager1"
    And I follow "Site administration"
    Then "Certificates" "text" should not exist in the "region-main" "region"
    And I should not see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
    And I log out

  @javascript
  Scenario: View certificate as user with certificateviewer role
    When the following "role assigns" exist:
      | user     | role                 | contextlevel | reference |
      | manager1 | certificateviewer    | System       |           |
    And I log in as "manager1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I navigate to "Issued certificates" in current page administration
    And I open the action menu in "User 12" "table_row"
    # Make sure only viewing is permitted.
    And I should not see "Revoke"
    And I should not see "Regenerate issue file"
    And I should see "View"
