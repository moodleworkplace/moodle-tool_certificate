@tool @tool_certificate @moodleworkplace
Feature: Being able to verify that a certificate is valid or not
  In order to ensure that a user can verify a certificate is valid
  As an admin and non-user
  I need to be able to verify a certificate

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
      | Certificate 2 |

  Scenario: Verify a certificate as admin
    When I log in as "admin"
    And the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |
    And I navigate to "Certificates > Verify certificates" in site administration
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    Then I should see "Not verified"
    And I verify the "Certificate 1" site certificate for the user "student1"

  Scenario: Verify a certificate as an admin
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
    And the following "permission overrides" exist:
      | capability              | permission | role  | contextlevel | reference |
      | tool/certificate:verify | Allow      | guest | System       |           |
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

  Scenario: User with capability to verify certificates can verify certificates in all tenants
    Given "2" tenants exist with "4" users and "0" courses in each
    And the following certificate templates exist:
      | name          | category  |
      | Certificate 00 |           |
      | Certificate 11 | Category1 |
      | Certificate 22 | Category2 |
    And the following certificate issues exist:
      | template      | user   |
      | Certificate 11 | user11 |
      | Certificate 11 | user12 |
      | Certificate 22 | user21 |
      | Certificate 00 | user22 |
      | Certificate 00 | user12 |
    And the following "permission overrides" exist:
      | capability              | permission | role | contextlevel | reference |
      | tool/certificate:verify | Allow      | user | System       |           |
    And I log in as "user23"
    And I visit the sites certificates verification url
    And I verify the "Certificate 11" site certificate for the user "user11"
    And I verify the "Certificate 11" site certificate for the user "user12"
    And I verify the "Certificate 22" site certificate for the user "user21"
    And I verify the "Certificate 00" site certificate for the user "user22"
    And I verify the "Certificate 00" site certificate for the user "user12"
    And I log out
