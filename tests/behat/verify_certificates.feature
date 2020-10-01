@tool @tool_certificate @moodleworkplace
Feature: Being able to verify that a certificate is valid or not
  In order to ensure that a user can verify a certificate is valid
  As an admin and non-user
  I need to be able to verify a certificate

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
      | Certificate 2 |

  Scenario: Verify a certificate as admin
    When the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |
      | Certificate 2 | student1 |
    And I log in as "admin"
    And I visit the sites certificates verification url
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    Then I should see "Not verified"
    And I verify the "Certificate 1" site certificate for the user "student1"
    And I verify the "Certificate 2" site certificate for the user "student1"

  Scenario: Verify a certificate as a guest using the site-wide URL
    And the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |
      | Certificate 2 | student1 |
    And I log in as "guest"
    And I visit the sites certificates verification url
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    Then I should see "Not verified"
    And I verify the "Certificate 1" site certificate for the user "student1"
    And I verify the "Certificate 2" site certificate for the user "student1"

  Scenario: Verify certificate of a removed user
    And the following certificate issues exist:
      | template      | user      | code      |
      | Certificate 1 | student1  | aaaaaaaaa |
    And I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I click on "Delete" "link" in the "Student 1" "table_row"
    And I press "Delete"
    And I visit the sites certificates verification url
    And I verify the site certificate with code "aaaaaaaaa"
    And I should see "Student 1"
