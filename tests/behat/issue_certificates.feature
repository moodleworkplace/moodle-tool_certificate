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

  Scenario: Issue a certificate as issuer user, from the list of templates
    When I log in as "issuer0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Actions" "icon" in the "Certificate 0" "table_row"
    And I choose "Issue certificates" in the open action menu
    And I set the field "Select users to issue certificate to" to "User 11"
    And I press "Save"
    And I wait until ".toast-message" "css_element" does not exist
    And I follow "Certificate 0"
    Then "User 11" "text" should exist in the "reportbuilder-table" "table"
    And I log out
    # Check notifications are triggered.
    And I log in as "user11"
    And I am on site homepage
    When I click on ".popover-region-notifications" "css_element"
    And I click on "View full notification" "link" in the ".popover-region-notifications" "css_element"
    Then I should see "Your certificate is available!"
    And I log out

  Scenario: Issue a certificate as issuer user, from the list of issues
    When I log in as "issuer0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 0"
    And I navigate to "Issued certificates" in current page administration
    And I click on "Issue certificates" "link"
    And I set the field "Select users to issue certificate to" to "User 11"
    And I click on "Save" "button" in the "Issue certificates" "dialogue"
    And the following should exist in the "reportbuilder-table" table:
      | First name / Surname | Date issued         |
      | User 11              | ##today##%d %B %Y## |
    # Issue second certificate to another user.
    And I click on "Issue certificates" "link"
    And I set the field "Select users to issue certificate to" to "User 12"
    And I click on "Save" "button" in the "Issue certificates" "dialogue"
    And the following should exist in the "reportbuilder-table" table:
      | First name / Surname | Date issued         |
      | User 11              | ##today##%d %B %Y## |
      | User 12              | ##today##%d %B %Y## |
    And I log out

  Scenario: Issue a certificate with expiry date as issuer user
    When I log in as "issuer0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    # Issue a certificate for user11 with absolute expiry date.
    And I click on "Actions" "icon" in the "Certificate 0" "table_row"
    And I choose "Issue certificates" in the open action menu
    And the field "expirydatetype" matches value "Never"
    And I set the following fields to these values:
      | Select users to issue certificate to  | User 11                 |
      | Expiry date type                      | Select date             |
      | expirydateabsolute[day]               | ##tomorrow##%d##        |
      | expirydateabsolute[month]             | ##tomorrow##%B##        |
      | expirydateabsolute[year]              | ##tomorrow##%Y##        |
    And I press "Save"
    # Issue a certificate for user11 with relative expiry date.
    And I click on "Actions" "icon" in the "Certificate 0" "table_row"
    And I choose "Issue certificates" in the open action menu
    And I set the following fields to these values:
      | Select users to issue certificate to  | User 12 |
      | Expiry date type                      | After   |
      | expirydaterelative[number]            | 1       |
      | expirydaterelative[timeunit]          | days    |
    And I press "Save"
    And I wait until ".toast-message" "css_element" does not exist
    And I follow "Certificate 0"
    Then "User 11" "text" should exist in the "reportbuilder-table" "table"
    And the following should exist in the "reportbuilder-table" table:
      | First name / Surname | Expiry date            |
      | User 11              | ##tomorrow##%d %B %Y## |
      | User 12              | ##tomorrow##%d %B %Y## |
    And I log out

  Scenario: Revoke issued certificate as issuer user
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
    When I log in as "issuer0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I navigate to "Issued certificates" in current page administration
    And I should see "User 11"
    And I should see "User 12"
    And I press "Revoke" action in the "User 11" report row
    And I click on "Revoke" "button" in the "Confirm" "dialogue"
    And I should not see "User 11"
    And I should see "User 12"
    And I log out

  Scenario: Regenerate issued certificate file as issuer user
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
    When I log in as "issuer0"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I navigate to "Issued certificates" in current page administration
    And I press "Regenerate issue file" action in the "User 11" report row
    And I click on "Regenerate" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I log out

  Scenario: Filter issued certificates datasource by cohort
    Given the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
    And the following "cohorts" exist:
      | name     | idnumber | contextlevel | reference |
      | Cohort 1 | CH1      | System       |           |
      | Cohort 2 | CH2      | System       |           |
    And the following "cohort members" exist:
      | user    | cohort |
      | user11  | CH2    |
      | user12  | CH1    |
    When I log in as "admin"
    And I navigate to "Reports > Report builder > Custom reports" in site administration
    And I click on "New report" "button"
    And I set the following fields in the "New report" "dialogue" to these values:
      | Name                  | Report1              |
      | Report source         | Issued certificates  |
      | Include default setup | 1                    |
    And I click on "Save" "button" in the "New report" "dialogue"
    And I click on "Add column 'Name'" "link"
    And the following "core_reportbuilder > Filter" exists:
      | report           | Report1     |
      | uniqueidentifier | cohort:name |
    And I click on "Switch to preview mode" "button"
    Then I should see "User 11" in the "reportbuilder-table" "table"
    And I should see "Cohort 2" in the "reportbuilder-table" "table"
    And I should see "User 12" in the "reportbuilder-table" "table"
    And I should see "Cohort 1" in the "reportbuilder-table" "table"
    And I click on "Filters" "button"
    And I set the following fields in the "Name" "core_reportbuilder > Filter" to these values:
      | Name operator | Is equal to   |
      | Name value    | Cohort 2      |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I should see "User 11" in the "reportbuilder-table" "table"
    And I should see "Cohort 2" in the "reportbuilder-table" "table"
    And I set the following fields in the "Name" "core_reportbuilder > Filter" to these values:
      | Name operator | Is equal to   |
      | Name value    | Cohort 1      |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    And I should see "User 12" in the "reportbuilder-table" "table"
    And I should see "Cohort 1" in the "reportbuilder-table" "table"
