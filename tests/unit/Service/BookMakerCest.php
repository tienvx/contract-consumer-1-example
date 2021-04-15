<?php

namespace App\Tests\unit\Service;

use App\Service\BookMaker;
use App\Tests\UnitTester;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Standalone\MockService\MockServerEnvConfig;

class BookMakerCest
{
    protected Matcher $matcher;
    protected MockServerEnvConfig $config;
    protected InteractionBuilder $builder;
    protected array $book;
    protected string $bookIri;

    public function _before(UnitTester $I)
    {
        $this->config = new MockServerEnvConfig();
        $this->builder = new InteractionBuilder($this->config);

        $this->matcher = new Matcher();

        $this->bookIri = '/api/books/0114b2a8-3347-49d8-ad99-0e792c5a30e6';

        $this->book = [
            '@context' => '/api/contexts/Book',
            '@id' => $this->matcher->term($this->bookIri, '^\\/api\\/books\\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$'),
            '@type' => 'Book',
            'title' => $this->matcher->like('Voluptas et tempora repellat corporis excepturi.'),
            'description' => $this->matcher->like('Quaerat odit quia nisi accusantium natus voluptatem. Explicabo corporis eligendi ut ut sapiente ut qui quidem. Optio amet velit aut delectus. Sed alias asperiores perspiciatis deserunt omnis. Mollitia unde id in.'),
            'author' => $this->matcher->like('Melisa Kassulke'),
            'publicationDate' => $this->matcher->dateTimeISO8601('1999-02-13T00:00:00+07:00'),
            'reviews' => [],
        ];

        $this->setUpCreatingBook();
    }

    public function testCreateBook(UnitTester $I)
    {
        $service = new BookMaker();
        $service->setBaseUrl($this->config->getBaseUri());

        $result = $service->createBook();
        $I->assertTrue($result, "Let's make sure we created a book");

        $this->builder->verify();
    }

    protected function setUpCreatingBook(): void
    {
        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/books')
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                'isbn' => $this->matcher->like('0099740915'),
                'title' => $this->matcher->like("The Handmaid's Tale"),
                'description' => $this->matcher->like('Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.'),
                'author' => $this->matcher->like('Margaret Atwood'),
                'publicationDate' => $this->matcher->like('1985-07-31T00:00:00+00:00'),
            ]);

        // build the response
        $response = new ProviderResponse();
        $response
            ->setStatus(201)
            ->addHeader('Content-Type', 'application/ld+json; charset=utf-8')
            ->setBody($this->book);

        $this->builder->given('Book Fixtures Loaded')
            ->uponReceiving('A POST request to create book')
            ->with($request)
            ->willRespondWith($response);
    }
}
