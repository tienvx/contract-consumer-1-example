<?php
namespace App\Tests\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;
use GuzzleHttp\Psr7\Uri;
use PhpPact\Broker\Service\BrokerHttpClient;
use PhpPact\Http\GuzzleClient;
use PhpPact\Standalone\MockService\MockServer;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use PhpPact\Standalone\MockService\Service\MockServerHttpService;

class Pact extends Extension
{
    protected $config = ['suites' => []];

    /**
     * @var MockServer
     */
    protected MockServer $server;

    public static $events = [
        Events::SUITE_INIT => [
            ['initSuite', -1]
        ],
        Events::SUITE_AFTER  => 'afterSuite',
    ];

    public function initSuite(SuiteEvent $e)
    {
        if (in_array($e->getSuite()->getName(), $this->config['suites'])) {
            $mockServerConfig = new MockServerEnvConfig();
            $this->server = new MockServer($mockServerConfig);
            $this->server->start();
        }
    }

    public function afterSuite(SuiteEvent $e)
    {
        if (in_array($e->getSuite()->getName(), $this->config['suites'])) {
            $mockServerConfig = new MockServerEnvConfig();
            try {
                $httpService = new MockServerHttpService(new GuzzleClient(), $mockServerConfig);
                $httpService->verifyInteractions();

                $json = $httpService->getPactJson();
            } finally {
                $this->server->stop();
            }

            if ($e->getResult()->failureCount() > 0) {
                print 'A unit test has failed. Skipping PACT file upload.';
            } elseif (!($pactBrokerUri = \getenv('PACT_BROKER_URI'))) {
                print 'PACT_BROKER_URI environment variable was not set. Skipping PACT file upload.';
            } elseif (!($consumerVersion = \getenv('PACT_CONSUMER_VERSION'))) {
                print 'PACT_CONSUMER_VERSION environment variable was not set. Skipping PACT file upload.';
            } elseif (!($tag = \getenv('PACT_CONSUMER_TAG'))) {
                print 'PACT_CONSUMER_TAG environment variable was not set. Skipping PACT file upload.';
            } else {
                $clientConfig = [];
                if (($user = \getenv('PACT_BROKER_HTTP_AUTH_USER')) &&
                    ($pass = \getenv('PACT_BROKER_HTTP_AUTH_PASS'))
                ) {
                    $clientConfig = [
                        'auth' => [$user, $pass],
                    ];
                }

                if (($sslVerify = \getenv('PACT_BROKER_SSL_VERIFY'))) {
                    $clientConfig['verify'] = $sslVerify !== 'no';
                }

                $headers = [];
                if ($bearerToken = \getenv('PACT_BROKER_BEARER_TOKEN')) {
                    $headers['Authorization'] = 'Bearer ' . $bearerToken;
                }

                $client = new GuzzleClient($clientConfig);

                $brokerHttpService = new BrokerHttpClient($client, new Uri($pactBrokerUri), $headers);
                $brokerHttpService->tag($mockServerConfig->getConsumer(), $consumerVersion, $tag);
                $brokerHttpService->publishJson($json, $consumerVersion);
                print 'Pact file has been uploaded to the Broker successfully.';
            }
        }
    }
}
