<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Silex\Application;
use Behat\MinkExtension\Context\RawMinkContext;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use OAuth2Demo\Server\Storage\FixturesManager;
use Guzzle\Http\Client;

//
// Require 3rd-party libraries here:
//
require_once __DIR__.'/../../vendor/phpunit/phpunit/PHPUnit/Autoload.php';
require_once __DIR__.'/../../vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context.
 */
class ApiContext extends RawMinkContext
{
    /** @var \Guzzle\Http\Message\Response */
    private $currentResponse;

    private $currentApp;

    private $accessToken;

    /**
     * @Given /^"([^"]*)" creates an application called "([^"]*)" with "([^"]*)" secret$/
     */
    public function thereIsAnApplicationCalledWithSecret($ownerUsername, $appName, $appSecret)
    {
        $this->getStorage()
            ->setClientDetails(
                $appName,
                $appSecret,
                '',
                null,
                'barn-unlock toiletseat-down chickens-feed eggs-collect eggs-count',
                $ownerUsername
            );

        $this->currentApp = $appName;
    }

    /**
     * @When /^I make a request to "([^"]*)" with the following:$/
     */
    public function iMakeARequestToWithTheFollowing($url, TableNode $table)
    {
        $client = new Client();

        $request = $client->post($this->locatePath($url), array(), $table->getRowsHash());
        $response = $request->send();

        $this->currentResponse = $response;
    }

    /**
     * @When /^I make an authenticated request to "([^"]*)"$/
     */
    public function iMakeAnAuthenticatedRequestTo($url)
    {
        $client = new Client('', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $client->post(
            $this->locatePath($url)
        );
        $request->addHeader('Authorization', 'Bearer '.$this->accessToken);
        $response = $request->send();

        $this->currentResponse = $response;
    }

    /**
     * @Then /^the response should be valid JSON$/
     */
    public function theResponseShouldBeValidJson()
    {
        assertNotNull(json_decode($this->currentResponse->getBody(true)));
    }

    /**
     * @Given /^the response should contain an "([^"]*)" key$/
     */
    public function theResponseShouldContainAnKey($key)
    {
        $json = $this->currentResponse->json();

        assertTrue(
            isset($json[$key]),
            sprintf(
                'Valid keys are "%s"',
                print_r(array_keys($json), true)
            )
        );
    }

    /**
     * @Given /^I have a valid access token$/
     */
    public function iHaveAValidAccessToken()
    {
        $this->accessToken = 'ABCD1234TOKEN';

        $this->getStorage()
            ->setAccessToken(
                $this->accessToken,
                $this->currentApp,
                $this->getMainContext()->getCurrentUserId(),
                time() + 86400,
                'eggs-collect'
            )
        ;
    }

    /**
     * @return FeatureContext
     */
    public function getMainContext()
    {
        return parent::getMainContext();
    }

    private function getAppService($service)
    {
        $app = $this->getMainContext()->getApp();

        return $app[$service];
    }

    /**
     * @return \OAuth2Demo\Server\Storage\Pdo
     */
    private function getStorage()
    {
        return $this->getAppService('storage');
    }

    public function locatePath($path)
    {
        $path = parent::locatePath($path);

        $currentUsername = $this->getMainContext()->getCurrentUserId();
        $userDetails = $this->getStorage()->getUser($currentUsername);

        // in case we need to reference an id in the URL, but don't know the id
        $path = str_replace('{current_user_id}', $userDetails['id'], $path);

        return $path;
    }
}
