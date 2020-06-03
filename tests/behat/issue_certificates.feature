@tool @tool_certificate @moodleworkplace @javascript
Feature: Being able to manually issue a certificate to a user
  In order to manually issue a new certificate to a user
  As an admin
  I need to be able to issue a certificate from a list of users

  Background:
    Given "2" tenants exist with "5" users and "0" courses in each
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | issuer0  | Issuer    | A        | issuer0@example.com  |
    And the following certificate templates exist:
      | name          | category  |
      | Certificate 0 |           |
      | Certificate 1 | Category1 |
      | Certificate 2 | Category2 |
    And the following "roles" exist:
      | shortname            | name                       | archetype |
      | certificateissuer    | Certificate issuer         |           |
      | certificateissuerall | Certificate issuer for all |           |
      | configviewer         | Config viewer              |           |
    And the following "role assigns" exist:
      | user    | role                 | contextlevel | reference |
      | user14  | certificateissuer    | System       |           |
      | user14  | configviewer         | System       |           |
      | user24  | certificateissuer    | Category     | CAT2      |
      | user24  | configviewer         | System       |           |
      | issuer0 | certificateissuerall | System       |           |
    And the following "permission overrides" exist:
      | capability                     | permission | role                 | contextlevel | reference |
      | moodle/site:configview         | Allow      | configviewer         | System       |           |
      | tool/certificate:issue         | Allow      | certificateissuer    | System       |           |
      | tool/certificate:issue         | Allow      | certificateissuerall | System       |           |
      | tool/tenant:allocate           | Allow      | certificateissuerall | System       |           |
      | moodle/site:configview         | Allow      | certificateissuerall | System       |           |
      | moodle/category:viewcourselist | Allow      | certificateissuerall | System       |           |

  Scenario: Issue a certificate as admin, from the list of templates
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Issue certificates from this template" "link" in the "Certificate 0" "table_row"
    And I wait "3" seconds
    And I open the autocomplete suggestions list
    And I click on "User 11" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    Then "User 11" "text" should exist in the "report-table" "table"
    And I log out
    # Check notifications are triggered.
    And I log in as "user11"
    And I am on site homepage
    When I click on ".popover-region-notifications" "css_element"
    And I click on "View full notification" "link" in the ".popover-region-notifications" "css_element"
    Then I should see "Your certificate is available!"
    And I log out

  Scenario: Issue a certificate as admin, from the list of issues
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And I click on "Issue certificates" "link"
    And I open the autocomplete suggestions list
    And I click on "User 11" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    Then "User 11" "text" should exist in the "report-table" "table"
    And I log out

  Scenario: Issue certificate as a tenant issuer
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 2 | user21 |
      | Certificate 0 | user12 |
      | Certificate 0 | user22 |
    When I log in as "user14"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
#    And I should see "Verify certificates"
    And I should not see "Add certificate template"
#    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    # The templates from other tenants should not be visible.
    And I should not see "Certificate 2"
    # Issue a certificate for a template that belongs to the same tenant (user from my tenants that don't have certificate yet).
    And I click on "Issue certificates from this template" "link" in the "Certificate 1" "table_row"
    And I open the autocomplete suggestions list
    And I should not see "User 2"
    And I should not see "User 11"
    And I should see "User 13"
    And I click on "User 12" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"
    And I should not see "User 13"
    And I should not see "User 2"
    And I follow "Manage certificate templates"
    # Issue a certificate for a template that is shared between tenants (user from my tenants that don't have certificate yet).
    And I click on "Issue certificates from this template" "link" in the "Certificate 0" "table_row"
    And I open the autocomplete suggestions list
    And I should not see "User 2"
    And I should not see "User 12"
    And I should see "User 11"
    And I click on "User 13" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And I should not see "User 2"
    And I should not see "User 11"
    And I should see "User 12"
    And I should see "User 13"
    And I log out

  Scenario: View issued certificates as an issuer for all tenants
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 2 | user21 |
      | Certificate 0 | user12 |
      | Certificate 0 | user22 |
    # Now make sure we can see all issues and issue for all users as issuer0.
    And I log in as "issuer0"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
#    And I should see "Verify certificates"
    And I should not see "Add certificate template"
#    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And I should see "User 12"
    And I should see "User 22"
    And I should not see "User 11"
    And I should not see "User 21"
    And I follow "Manage certificate templates"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should not see "User 12"
    And I should not see "User 22"
    And I should see "User 11"
    And I should not see "User 21"
    And I follow "Manage certificate templates"
    And I click on "Certificates issued" "link" in the "Certificate 2" "table_row"
    And I should not see "User 12"
    And I should not see "User 22"
    And I should not see "User 11"
    And I should see "User 21"
    And I log out

  Scenario: Issue certificates as an issuer for all tenants
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 2 | user21 |
      | Certificate 0 | user12 |
      | Certificate 0 | user22 |
    # Now make sure we can see all issues and issue for all users as issuer0.
    And I log in as "issuer0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    # It is possible to issue certificate0 to users from all tenants (except for those who already have this certificate).
    And I wait "3" seconds
    And I click on "Issue certificates from this template" "link" in the "Certificate 0" "table_row"
    And I open the autocomplete suggestions list
    And I should not see "User 12"
    And I should not see "User 22"
    And I should see "User 11"
    And I should see "User 21"
    And I should see "Admin User"
    And I click on "User 13" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And I should see "User 13"
    And I should see "User 22"
    And I should not see "User 11"
    And I should see "User 12"
    And I should not see "User 21"
    And I follow "Manage certificate templates"
    # It is possible to issue certificate1 only to users from tenant1 (except for those who already have this certificate).
    And I click on "Issue certificates from this template" "link" in the "Certificate 1" "table_row"
    And I open the autocomplete suggestions list
    And I should see "User 12"
    And I should see "User 13"
    And I should see "User 2"
    And I should not see "User 11"
    And I should see "Admin User"
    And I click on "User 12" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 12"
    And I should not see "User 2"
    And I should see "User 11"
    And I should not see "User 13"
    And I log out

  Scenario: Revoke issued certificate as a tenant issuer
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
    When I log in as "user14"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"
    And I click on "Revoke" "link" in the "User 11" "table_row"
    And I click on "Revoke" "button" in the "Confirm" "dialogue"
    And I should not see "User 11"
    And I should see "User 12"
    And I log out

  Scenario: Issue certificates within a tenant without capability to issue in system context
    When I log in as "user24"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should not see "Certificate 0"
    And I should not see "Certificate 1"
    And I click on "Issue certificates from this template" "link" in the "Certificate 2" "table_row"
    And I open the autocomplete suggestions list
    And I should see "User 21"
    And I should not see "User 1"
    And I click on "User 22" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate to"
    And I press "Save" in the modal form dialogue
    And I click on "Certificates issued" "link" in the "Certificate 2" "table_row"
    And I should see "User 22"
    And I should not see "User 21"
    And I log out

  Scenario: Issue certificates within a tenant with capability to issue in system context
    When I log in as "admin"
    And I set the following system permissions of "Tenant administrator" role:
      | tool/certificate:issue | Inherit |
    And I log out
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 2 | user21 |
      | Certificate 0 | user12 |
      | Certificate 0 | user22 |
    And I log in as "tenantadmin2"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should see "Certificate 0"
    And I should not see "Certificate 1"
    And "Issue certificates from this template" "link" should not exist in the "Certificate 0" "table_row"
    And "Issue certificates from this template" "link" should exist in the "Certificate 2" "table_row"
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And I should see "User 22"
    And I should not see "User 1"
    And I log out
