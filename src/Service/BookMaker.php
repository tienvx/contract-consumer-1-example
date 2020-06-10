<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class BookMaker
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private string $baseUrl;

    public function __construct($baseUrl)
    {
        $this->client = HttpClient::create();
        $this->baseUrl = $baseUrl;
    }

    public function createBook(): bool
    {
        $before = $this->countBooks();
        $response = $this->client->request('POST', "{$this->baseUrl}/api/books", [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => [
                'isbn' => '0099740915',
                'title' => 'The Handmaid\'s Tale',
                'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.',
                'author' => 'Margaret Atwood',
                'publicationDate' => '1985-07-31T00:00:00+00:00',
            ],
        ]);

        if ($response->getStatusCode() == 201) {
            $after = $this->countBooks();

            if ($after === $before + 1) {
                // Success
                return true;
            }
        }

        // Failure
        return false;
    }

    protected function countBooks(): int
    {
        $response = $this->client->request('GET', "{$this->baseUrl}/api/books?page=1", [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        return $response->toArray()['hydra:totalItems'];
    }
}
