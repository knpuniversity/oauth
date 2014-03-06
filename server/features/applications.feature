Feature: Applications
  In order to control my client application and scopes
  As a user
  I need to be able to create, edit and view my applications

  Background:
    Given I am logged in
    And I am on "/api"

  Scenario: Create an application
    When I click "Create your Application"
    And I fill in "Application Name" with "Foo app"
    And I check "Unlock the Barn"
    And I check "Feed Your Chickens"
    And I press "Submit"
    Then I should see "Congratulations"
    And I should see the following scopes listed:
      | barn-unlock   |
      | chickens-feed |


  Scenario: Edit an application

  Scenario: Cannot create an application with the same name

  Scenario: Only list my applications

  Scenario: Can only view applications I own
