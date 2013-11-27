<?php

namespace OAuth2Demo\Client\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OAuth2Demo\Client\Client as OAuth2Client;
use Guzzle\Http\Client as GuzzleHttpClient;

class CollectEggsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('eggs:collect')
            ->setDescription('Collect eggs for the specified client')
            ->addArgument(
                'client_id',
                InputArgument::REQUIRED,
                'Your Client ID'
            )
            ->addArgument(
                'client_secret',
                InputArgument::REQUIRED,
                'Your Client Secret'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // load configuration from data/parameters.json
        $client = new OAuth2Client();
        $config = $client->loadParameters();

        // create our http client
        $http = new GuzzleHttpClient();

        /* 1. Get the Access Token */

        // get the parameters from the command input
        $parameters = array(
            'client_id'     => $input->getArgument('client_id'),
            'client_secret' => $input->getArgument('client_secret'),
            'grant_type'    => 'client_credentials',
        );

        // get the token url from parameters.json
        $endpoint = $config['token_url'];

        // make a request to the token Url
        $response = $http->post($endpoint, null, $parameters)->send();
        $token = json_decode((string) $response->getBody(), true);

        $output->writeln('Received access token: '.$token['access_token']);

        /* 2. Call the APIs with the Access Token */

        // create OAuth2 Authorization header using the Access Token
        $headers = array('Authorization' => sprintf('Bearer %s', $token['access_token']));

        // get the resource url from parameters.json
        $endpoint = $config['resource_url'].'/eggs-collect';

        // make the request
        $response = $http->post($endpoint, $headers)->send();
        $api_response = json_decode((string) $response->getBody(), true);

        $output->writeln(sprintf('<info>%s</info>', $api_response['message']));
    }
}