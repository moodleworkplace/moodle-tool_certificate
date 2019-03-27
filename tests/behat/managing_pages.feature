@tool @tool_certificate @javascript
Feature: Being able to manage pages in a certificate template
  In order to ensure managing pages in a certificate template works as expected
  As an admin
  I need to manage pages in a certificate template

  Background:
    Given the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1 |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link"

  Scenario: Adding a page to a certificate template
    When I follow "Add page"
    And I press "Save" in the modal form dialogue
    And I should see "Page 1"
    And I should see "Page 2"

  Scenario: Deleting a page from a certificate template
    When I add the element "User picture" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I follow "Add page"
    And I press "Save" in the modal form dialogue
    And I should see "Page 1"
    And I should see "Page 2"
    And I click on "Delete" "link" in the "[data-region=\"page\"]" "css_element"
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I should see "Page 1"
    And I should not see "Page 2"
    And I should not see "User picture"
    And I should not see "User field"
    And I log out

  Scenario: Rearrange pages in a certificate template
    When I add the element "User picture" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I follow "Add page"
    And I press "Save" in the modal form dialogue
    And I add the element "User field" to page "2" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    And I click on "Move down" "link" in the "//*[@data-region='page'][1]" "xpath_element"
    Then "User field" "text" should appear before "User picture" "text"
    And I click on "Move up" "link" in the "//*[@data-region='page'][2]" "xpath_element"
    Then "User field" "text" should appear after "User picture" "text"
    And I log out
