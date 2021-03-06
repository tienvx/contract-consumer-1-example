<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookMaker
{
    private HttpClientInterface $client;
    private string $baseUrl;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function createBook(): bool
    {
        $response = $this->client->request('POST', "{$this->baseUrl}/api/books", [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'isbn' => '0099740915',
                'title' => 'The Handmaid\'s Tale',
                'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.',
                'author' => 'Margaret Atwood',
                'publicationDate' => '1985-07-31T00:00:00+00:00',
            ],
        ]);

        return 201 == $response->getStatusCode();
    }
}
