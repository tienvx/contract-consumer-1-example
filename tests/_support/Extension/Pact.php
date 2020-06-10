<?php
namespace App\Tests\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Test\Descriptor;
use GuzzleHttp\Psr7\Uri;
use PhpPact\Broker\Service\BrokerHttpClient;
use PhpPact\Http\GuzzleClient;
use PhpPact\Standalone\MockService\MockServer;
use PhpPact\Standalone\MockService\MockServerConfigInterface;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use PhpPact\Standalone\MockService\Service\MockServerHttpService;

class Pact extends Extension
{
    protected $config = ['suite' => 'unit'];

    /**
     * @var MockServer
     */
    protected MockServer $server;

    /**
     * @var MockServerConfigInterface
     */
    protected MockServerConfigInterface $mockServerConfig;

    public function __construct($config, $options)
    {
        parent::__construct($config, $options);
        $this->mockServerConfig = new MockServerEnvConfig();
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::SUITE_BEFORE => 'beforeSuite',
            Events::SUITE_AFTER  => 'afterSuite',
        ];
    }

    public function beforeSuite(SuiteEvent $e)
    {
        if ($this->config['suite'] === $e->getSuite()->getName()) {
            $this->server = new MockServer($this->mockServerConfig);
            $this->server->start();
        }
    }

    public function afterSuite(SuiteEvent $e)
    {
        if ($this->config['suite'] === $e->getSuite()->getName()) {
            try {
                $httpService = new MockServerHttpService(new GuzzleClient(), $this->mockServerConfig);
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
                $brokerHttpService->tag($this->mockServerConfig->getConsumer(), $consumerVersion, $tag);
                $brokerHttpService->publishJson($json, $consumerVersion);
                print 'Pact file has been uploaded to the Broker successfully.';
            }
        }
    }
}
