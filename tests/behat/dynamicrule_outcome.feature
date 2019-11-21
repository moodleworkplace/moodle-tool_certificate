@tool @tool_certificate @javascript @moodleworkplace
Feature: Issue certificate with dynamic rules
  In order to issue a certificate in a dynamic rule
  As a manager
  I need to be able to create and edit certificate outcome

  Scenario: Add a rule with certificate outcome
    Given the following certificate templates exist:
      | name |
      | Certificate 1 |
      | Certificate 2 |
    When I log in as "admin"
    And I navigate to "Dynamic rules" in site administration
    And I follow "New rule"
    And I set the following fields to these values:
      | Name | Rule1 |
    And I press "Save" in the modal form dialogue
    And I follow "Actions"
    And I click on "Issue certificate" "link" in the "#ruleoutcomes" "css_element"
    And I open the autocomplete suggestions list
    And I click on "Certificate 1" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    Then I should see "Issue certificate 'Certificate 1' to users"
    And I follow "Edit action"
    And I open the autocomplete suggestions list
    And I click on "Certificate 2" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    Then I should see "Issue certificate 'Certificate 2' to users"

  Scenario: Issue a certificate on course completion
    Given "2" tenants exist with "3" users and "2" courses in each
    And the following "course enrolments" exist:
      | user   | course | role    |
      | user11 | C11    | student |
    Given the following certificate templates exist:
      | name          | category  | numberofpages |
      | Certificate 1 | Category1 | 1             |
      | Certificate 2 | Category2 | 1             |
    And I log in as "tenantadmin1"
    # Create a certificate that prints the course full name.
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link" in the "Certificate 1" "table_row"
    And I add the element "Dynamic rule data" to page "1" of the "Certificate 1" site certificate template
    And I follow "Show more..."
    And I set the following fields to these values:
      | Field      | Course full name |
      | Position X | 100              |
    And I press "Save" in the modal form dialogue
    # Create a dynamic rule that gives this certificate on completion of a course.
    And I navigate to "Dynamic rules" in site administration
    And I follow "New rule"
    And I set the following fields to these values:
      | Name | Rule1 |
    And I press "Save" in the modal form dialogue
    And I follow "Course completed"
    And I open the autocomplete suggestions list
    And I click on "Course11" item in the autocomplete list
    And I press key "27" in the field "Course"
    And I press "Save changes"
    And I follow "Actions"
    And I click on "Issue certificate" "link" in the "#ruleoutcomes" "css_element"
    And I open the autocomplete suggestions list
    And I click on "Certificate 1" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    And I click on "Enable" "button"
    And I click on "Enable" "button" in the "Confirm" "dialogue"
    And I log out
    # Complete course as a student.
    And I log in as "user11"
    And I am on "Course11" course homepage
    And I click on "Expand all" "button"
    And I click on "[data-modulename='URL1']" "css_element"
    And I click on "[data-modulename='URL2']" "css_element"
    And I log out
    # Completion cron won't mark the whole course completed unless the
    # individual criteria was marked completed more than a second ago. So
    # run it twice, first to mark the criteria and second for the course.
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    # Check certificate was issued.
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I click on "View" "link" in the "User 11" "table_row"
    # TODO WP-1212 validate that issued certificate contains the "Course11"
    #And I should see "Course11"
    And I am on homepage
    And I log out

  Scenario: Issue a certificate on program completion
    Given "2" tenants exist with "3" users and "2" courses in each
    Given the following tool program data "programs" exist:
      | fullname | tenant  |
      | Program1 | Tenant1 |
    Given the following tool program data "program_courses" exist:
      | program  | course |
      | Program1 | C11    |
    And the following tool program data "program_users" exist:
      | program  | user   |
      | Program1 | user11 |
    Given the following certificate templates exist:
      | name          | category  | numberofpages |
      | Certificate 1 | Category1 | 1             |
      | Certificate 2 | Category2 | 1             |
    And I log in as "tenantadmin1"
    # Create a certificate that prints the course full name.
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link" in the "Certificate 1" "table_row"
    And I add the element "Dynamic rule data" to page "1" of the "Certificate 1" site certificate template
    And I follow "Show more..."
    And I set the following fields to these values:
      | Field      | Program name |
      | Position X | 100          |
    And I press "Save" in the modal form dialogue
    # Create a dynamic rule that gives this certificate on completion of a course.
    And I navigate to "Dynamic rules" in site administration
    And I follow "New rule"
    And I set the following fields to these values:
      | Name | Rule1 |
    And I press "Save" in the modal form dialogue
    And I follow "Program completed"
    And I click on ".form-autocomplete-downarrow" "css_element" in the ".select_program_field" "css_element"
    Then I click on "Program1" "text" in the ".select_program_field .form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I follow "Actions"
    And I click on "Issue certificate" "link" in the "#ruleoutcomes" "css_element"
    And I open the autocomplete suggestions list
    And I click on "Certificate 1" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    And I click on "Enable" "button"
    And I click on "Enable" "button" in the "Confirm" "dialogue"
    And I log out
    # Complete course as a student.
    And I log in as "user11"
    And I click on "Course11" "button"
    And I click on "Expand all" "button"
    And I click on "[data-modulename='URL1']" "css_element"
    And I click on "[data-modulename='URL2']" "css_element"
    And I log out
    # Completion cron won't mark the whole course completed unless the
    # individual criteria was marked completed more than a second ago. So
    # run it twice, first to mark the criteria and second for the course.
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    # TODO WP-1204 remove this, it should listen to events.
    And I run the scheduled task "tool_dynamicrule\task\process_rules"
    # Check certificate was issued.
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I click on "View" "link" in the "User 11" "table_row"
    # TODO WP-1212 validate that issued certificate contains the "Program1"
    #And I should see "Program1"
    And I am on homepage
    And I log out

  Scenario: Issue a certificate on program completion
    Given "2" tenants exist with "3" users and "2" courses in each
    Given the following tool program data "programs" exist:
      | fullname | tenant  |
      | Program1 | Tenant1 |
    Given the following tool program data "program_courses" exist:
      | program  | course |
      | Program1 | C11    |
    Given the following tool certification data "certifications" exist:
      | fullname       | archived | tenant  | program  |
      | Certification1 | 0        | Tenant1 | Program1 |
    Given the following users allocations to certifications exist:
      | certification  | user   |
      | Certification1 | user11 |
    Given the following certificate templates exist:
      | name          | category  | numberofpages |
      | Certificate 1 | Category1 | 1             |
      | Certificate 2 | Category2 | 1             |
    And I log in as "tenantadmin1"
    # Create a certificate that prints the course full name.
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link" in the "Certificate 1" "table_row"
    And I add the element "Dynamic rule data" to page "1" of the "Certificate 1" site certificate template
    And I follow "Show more..."
    And I set the following fields to these values:
      | Field      | Certification name |
      | Position X | 100                |
    And I press "Save" in the modal form dialogue
    # Create a dynamic rule that gives this certificate on completion of a course.
    And I navigate to "Dynamic rules" in site administration
    And I follow "New rule"
    And I set the following fields to these values:
      | Name | Rule1 |
    And I press "Save" in the modal form dialogue
    And I follow "Certification certified"
    And I click on ".form-autocomplete-downarrow" "css_element" in the ".select_certification" "css_element"
    Then I click on "Certification1" "text" in the ".select_certification .form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I follow "Actions"
    And I click on "Issue certificate" "link" in the "#ruleoutcomes" "css_element"
    And I open the autocomplete suggestions list
    And I click on "Certificate 1" item in the autocomplete list
    And I press key "27" in the field "Select certificate"
    And I press "Save changes"
    And I click on "Enable" "button"
    And I click on "Enable" "button" in the "Confirm" "dialogue"
    And I log out
    # Complete course as a student.
    And I log in as "user11"
    And I click on "Course11" "button"
    And I click on "Expand all" "button"
    And I click on "[data-modulename='URL1']" "css_element"
    And I click on "[data-modulename='URL2']" "css_element"
    And I log out
    # Completion cron won't mark the whole course completed unless the
    # individual criteria was marked completed more than a second ago. So
    # run it twice, first to mark the criteria and second for the course.
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    # TODO WP-1204 remove this, it should listen to events.
    And I run the scheduled task "tool_dynamicrule\task\process_rules"
    # Check certificate was issued.
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I click on "View" "link" in the "User 11" "table_row"
    # TODO WP-1212 validate that issued certificate contains the "Certification1"
    #And I should see "Certification1"
    And I am on homepage
    And I log out
