@tool @tool_certificate
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
    And I verify the "Certificate 1" certificate for the user "student1"

  Scenario: Verify a certificate as an admin
    When the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |
      | Certificate 2 | student1 |
    And I log in as "admin"
    And I visit the verification url for the site
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    Then I should see "Not verified"
    And I verify the "Certificate 1" certificate for the user "student1"
    And I verify the "Certificate 2" certificate for the user "student1"

  Scenario: Verify a certificate as a guest using the site-wide URL
    When I log in as "admin"
    And I set the following system permissions of "Guest" role:
      | capability | permission |
      | tool/certificate:verifyallcertificates | Allow |
    And the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |
      | Certificate 2 | student1 |
    And I log out
    And I log in as "guest"
    And I visit the verification url for the site
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    Then I should see "Not verified"
    And I verify the "Certificate 1" certificate for the user "student1"
    And I verify the "Certificate 2" certificate for the user "student1"
