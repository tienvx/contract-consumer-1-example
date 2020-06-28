<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class BookMaker
{
    /**
     * @var HttpClient
     */
    private $client;

    private string $baseUrl;

    public function __construct($baseUrl)
    {
        $this->client = HttpClient::create();
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

        if (201 == $response->getStatusCode()) {
            return $this->generateCover($response->toArray()['@id']);
        }

        // Failure
        return false;
    }

    protected function generateCover(string $iri): bool
    {
        $response = $this->client->request('PUT', "{$this->baseUrl}/{$iri}/generate-cover", [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        return 204 === $response->getStatusCode();
    }
}
