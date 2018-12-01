@tool @tool_certificate @javascript
Feature: Being able to manually issue a certificate to a user
  In order to manually issue a new certificate to a user
  As an admin
  I need to be able to issue a certificate from a list of users

  Background:
    Given the following tenants exist:
      | name    |
      | Tenant1 |
      | Tenant2 |
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | user11   | User      | 11       | user11@example.com   |
      | user12   | User      | 12       | user12@example.com   |
      | user13   | User      | 13       | user13@example.com   |
      | user21   | User      | 21       | user21@example.com   |
      | user22   | User      | 22       | user22@example.com   |
      | issuer0  | Issuer    | A        | issuer0@example.com  |
      | issuer1  | Issuer    | 1        | issuer1@example.com  |
      | issuer2  | Issuer    | 2        | issuer2@example.com  |
    And the following users allocations to tenants exist:
      | user     | tenant  |
      | user11   | Tenant1 |
      | user12   | Tenant1 |
      | user13   | Tenant1 |
      | user21   | Tenant2 |
      | user22   | Tenant2 |
      | issuer1  | Tenant1 |
      | issuer2  | Tenant2 |
    And the following certificate templates exist:
      | name          | tenant  |
      | Certificate 0 |         |
      | Certificate 1 | Tenant1 |
      | Certificate 2 | Tenant2 |
    And the following "roles" exist:
      | shortname            | name                       | archetype |
      | certificateissuer    | Certificate issuer         |           |
      | certificateissuerall | Certificate issuer for all |           |
    And the following "role assigns" exist:
      | user    | role                 | contextlevel | reference |
      | issuer1 | certificateissuer    | System       |           |
      | issuer2 | certificateissuer    | System       |           |
      | issuer0 | certificateissuerall | System       |           |
    And I log in as "admin"
    And I set the following system permissions of "Certificate issuer" role:
      | capability             | permission |
      | tool/certificate:issue | Allow      |
      | moodle/site:configview | Allow      |
    And I set the following system permissions of "Certificate issuer for all" role:
      | capability                          | permission |
      | tool/certificate:issueforalltenants | Allow      |
      | moodle/site:configview              | Allow      |
      | tool/certificate:issue | Allow      |
    # TODO: remove capability "tool/certificate:issue" from role "Certificate issuer for all".
    And I log out

  Scenario: Issue a certificate as admin, from the list of templates
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Issue new certificate from this template" "link" in the "Certificate 0" "table_row"
    And I set the field "Select users to issue certificate for" to "Student"
    And I wait until the page is ready
    And I press "Issue new certificates"
    Then I should see "One issue was created"

  Scenario: Issue a certificate as admin, from the list of issues
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And I click on "Issue new certificates" "link"
    And I set the field "Select users to issue certificate for" to "Student"
    And I wait until the page is ready
    And I press "Issue new certificates"
    Then I should see "One issue was created"

  Scenario: Issue certificate as a tenant issuer
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 2 | user21 |
      | Certificate 0 | user12 |
      | Certificate 0 | user22 |
    When I log in as "issuer1"
    And I am on site homepage
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    # TODO uncomment: And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    # The templates from other tenants should not be visible.
    # TODO uncomment: And I should not see "Certificate 2"
    # Issue a certificate for a template that belongs to the same tenant (user from my tenants that don't have certificate yet).
    And I click on "Issue new certificate from this template" "link" in the "Certificate 1" "table_row"
    And I open the autocomplete suggestions list
    And I should not see "User 2"
    # TODO uncomment: And I should not see "User 11"
    And I should see "User 13"
    And I click on "User 12" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate for"
    And I press "Issue new certificates"
    And I should see "User 11"
    And I should see "User 12"
    And I should not see "User 13"
    And I should not see "User 2"
    And I follow "Manage certificate templates"
    # Issue a certificate for a template that is shared between tenants (user from my tenants that don't have certificate yet).
    And I click on "Issue new certificate from this template" "link" in the "Certificate 0" "table_row"
    And I open the autocomplete suggestions list
    And I should not see "User 2"
    # TODO uncomment: And I should not see "User 12"
    And I should see "User 11"
    And I click on "User 13" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate for"
    And I press "Issue new certificates"
    # TODO uncomment: And I should not see "User 2"
    And I should not see "User 11"
    And I should see "User 12"
    # TODO uncomment: And I should see "User 13"
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
    # TODO uncomment: And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
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
    And I click on "Issue new certificate from this template" "link" in the "Certificate 0" "table_row"
    And I open the autocomplete suggestions list
    And I should not see "User 12"
    And I should not see "User 22"
    # TODO uncomment: And I should see "User 11"
    # TODO uncomment: And I should see "User 21"
    And I should see "Admin User"
    # TODO uncomment: And I click on "User 13" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate for"
    And I press "Issue new certificates"
    # TODO remove:
    And I press "Cancel"
    # TODO uncomment: And I should see "User 13"
    And I should see "User 22"
    And I should not see "User 11"
    And I should see "User 12"
    And I should not see "User 21"
    And I follow "Manage certificate templates"
    # It is possible to issue certificate1 only to users from tenant1 (except for those who already have this certificate).
    And I click on "Issue new certificate from this template" "link" in the "Certificate 1" "table_row"
    And I open the autocomplete suggestions list
    # TODO uncomment: And I should see "User 12"
    # TODO uncomment: And I should see "User 13"
    And I should not see "User 2"
    And I should not see "User 11"
    # TODO uncomment: And I should not see "Admin User"
    # TODO uncomment: And I click on "User 12" item in the autocomplete list
    And I press key "27" in the field "Select users to issue certificate for"
    And I press "Issue new certificates"
    # TODO remove:
    And I press "Cancel"
    # TODO uncomment: And I should see "User 12"
    And I should not see "User 2"
    And I should see "User 11"
    And I should not see "User 13"
    And I log out
