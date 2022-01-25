@tool @tool_certificate @moodleworkplace @javascript
Feature: Being able to manually issue a certificate to a user
  In order to manually issue a new certificate to a user
  As an admin
  I need to be able to issue a certificate from a list of users

  Background:
    Given the following "categories" exist:
      | name                  | category | idnumber |
      | Category1             | 0        | CAT1     |
      | Category2             | 0        | CAT2     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | user11   | User      | 11       | user11@example.com   |
      | user12   | User      | 12       | user12@example.com   |
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
      | issuer0 | certificateissuerall | System       |           |
    And the following "permission overrides" exist:
      | capability                     | permission | role                 | contextlevel | reference |
      | moodle/site:configview         | Allow      | configviewer         | System       |           |
      | tool/certificate:issue         | Allow      | certificateissuer    | System       |           |
      | tool/certificate:issue         | Allow      | certificateissuerall | System       |           |
      | moodle/site:configview         | Allow      | certificateissuerall | System       |           |
      | moodle/category:viewcourselist | Allow      | certificateissuerall | System       |           |

  Scenario: Issue a certificate as admin, from the list of templates
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Issue certificates from this template" "link" in the "Certificate 0" "table_row"
    And I set the field "Select users to issue certificate to" to "User 11"
    And I press "Save"
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    Then "User 11" "text" should exist in the "tool-certificate-issues" "table"
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
    And I set the field "Select users to issue certificate to" to "User 11"
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then the following should exist in the "tool-certificate-issues" table:
      | First name / Surname | Date issued             |
      | User 11              | ##today##%d %B %Y## |
    # Issue second certificate to another user.
    And I click on "Issue certificates" "link"
    And I set the field "Select users to issue certificate to" to "User 12"
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And the following should exist in the "tool-certificate-issues" table:
      | First name / Surname | Date issued             |
      | User 11              | ##today##%d %B %Y## |
      | User 12              | ##today##%d %B %Y## |
    And I log out

  Scenario: Issue a certificate with expiry date as admin
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    # Issue a certificate for user11 with absolute expiry date.
    And I click on "Issue certificates from this template" "link" in the "Certificate 0" "table_row"
    And the field "expirydatetype" matches value "Never"
    And I set the following fields to these values:
      | Select users to issue certificate to  | User 11                 |
      | Expiry date type                      | Select date             |
      | expirydateabsolute[day]               | ##tomorrow##%d##        |
      | expirydateabsolute[month]             | ##tomorrow##%B##        |
      | expirydateabsolute[year]              | ##tomorrow##%Y##        |
    And I press "Save"
    # Issue a certificate for user11 with relative expiry date.
    And I click on "Issue certificates from this template" "link" in the "Certificate 0" "table_row"
    And I set the following fields to these values:
      | Select users to issue certificate to  | User 12 |
      | Expiry date type                      | After   |
      | expirydaterelative[number]            | 1       |
      | expirydaterelative[timeunit]          | days    |
    And I press "Save"
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    Then "User 11" "text" should exist in the "tool-certificate-issues" "table"
    And the following should exist in the "tool-certificate-issues" table:
      | First name / Surname | Expiry date            |
      | User 11              | ##tomorrow##%d %B %Y## |
      | User 12              | ##tomorrow##%d %B %Y## |
    And I log out

  Scenario: Revoke issued certificate as admin
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"
    And I click on "Revoke" "link" in the "User 11" "table_row"
    And I click on "Revoke" "button" in the "Confirm" "dialogue"
    And I should not see "User 11"
    And I should see "User 12"
    And I log out

  Scenario: Regenerate issued certificate file as admin
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I click on "Regenerate issue file" "link" in the "User 11" "table_row"
    And I click on "Regenerate" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I log out
