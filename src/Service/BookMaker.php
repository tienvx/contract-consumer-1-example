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
            return $this->countBooks() > 0;
        }

        // Failure
        return false;
    }

    public function generateCover(string $id): bool
    {
        $response = $this->client->request('PUT', "{$this->baseUrl}/books/{$id}/generate-cover");

        return 200 === $response->getStatusCode();
    }

    protected function countBooks(): int
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/api/books?page=1", [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        return $response->toArray()['hydra:totalItems'];
    }
}
