@tool @tool_certificate @moodleworkplace
Feature: Being able to view the certificates that have been issued
  In order to ensure that a user can view the certificates that have been issued
  As an admin
  I need to view the certificates that have been issued

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | manager0 | Manager   | A        | viewer1@example.com |
      | manager1 | Manager   | 1        | viewer1@example.com |
      | manager2 | Manager   | 2        | viewer1@example.com |
      | user11   | User      | 11       | user11@example.com  |
      | user12   | User      | 12       | user12@example.com  |
      | user21   | User      | 21       | user21@example.com  |
      | user22   | User      | 22       | user22@example.com  |
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
      | certificatemanagerall | Certificate manager for all |           |
      | certificateissuer     | Certificate issuer          |           |
      | certificateissuerall  | Certificate issuer for all  |           |
      | certificateviewer     | Certificate viewer          |           |
      | configviewer          | Config viewer               |           |
    And the following "permission overrides" exist:
      | capability                           | permission | role                  | contextlevel | reference |
      | tool/certificate:manage              | Allow      | certificatemanagerall | System       |           |
      | moodle/category:viewcourselist       | Allow      | certificatemanagerall | System       |           |
      | moodle/site:configview               | Allow      | certificatemanagerall | System       |           |
      | tool/certificate:manage              | Allow      | certificatemanager    | System       |           |
      | tool/certificate:issue               | Allow      | certificateissuer     | System       |           |
      | tool/certificate:issue               | Allow      | certificateissuerall  | System       |           |
      | moodle/category:viewcourselist       | Allow      | certificateissuerall  | System       |           |
      | moodle/site:configview               | Allow      | certificateissuerall  | System       |           |
      | tool/certificate:viewallcertificates | Allow      | certificateviewer     | System       |           |
      | moodle/site:configview               | Allow      | certificateviewer     | System       |           |
      | moodle/site:configview               | Allow      | configviewer          | System       |           |

  Scenario: View the issued certificates as admin
    When I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"

  @javascript
  Scenario: Revoke an issued certificate as admin
    When I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"
    And I click on "Revoke" "link" in the "User 12" "table_row"
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I should see "User 12"
    And I click on "Revoke" "link" in the "User 12" "table_row"
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

  Scenario: View certificate of a removed user
    And I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I click on "Delete" "link" in the "User 11" "table_row"
    And I press "Delete"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
