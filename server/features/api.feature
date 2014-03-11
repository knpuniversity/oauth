Feature: API
  In order to integrate 3rd party apps with Coop
  As a client
  I need to be able to make API calls and authenticate

  Background:
    Given there is a user "brent"
    And "brent" creates an application called "FooApp" with "abc123" secret

  Scenario: Client credentials authentication
    When I make a request to "/token" with the following:
      | client_id | FooApp |
      | client_secret | abc123 |
      | grant_type    | client_credentials |
    Then the response should be valid JSON
    And the response should contain an "access_token" key

  Scenario: Collect eggs
    Given I have a valid access token
    # the {current_user_id} is replaced with Brent's user id
    When I make an authenticated request to "/api/{current_user_id}/eggs-collect"
    Then the response should be valid JSON
    And the response should contain an "action" key
    And the response should contain an "success" key
    And the response should contain an "data" key