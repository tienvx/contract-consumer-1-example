<?php

namespace App\Tests\unit\Service;

use App\Service\BookMaker;
use App\Tests\UnitTester;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use PhpPact\Standalone\MockService\Service\MockServerHttpService;
use PHPUnit\Framework\TestCase;

class BookMakerTest extends TestCase
{
    public function testCreateBook()
    {
        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath('/api/books?page=1')
            ->addHeader('Content-Type', 'application/json');

        $matcher = new Matcher();

        $review = new \stdClass();
        $review->{'@id'} = $matcher->term('/api/books/0114b2a8-3347-49d8-ad99-0e792c5a30e6', '\/api\/books\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}');
        $review->{'@type'} = 'http://schema.org/Review';
        $review->body = 'Necessitatibus eius commodi odio ut aliquid. Sit enim molestias in minus aliquid repudiandae qui. Distinctio modi officiis eos suscipit. Vel ut modi quia recusandae qui eligendi. Voluptas totam asperiores ab tenetur voluptatem repudiandae reiciendis.';

        $book = new \stdClass();
        $book->{'@id'} = $matcher->term('/api/books/0114b2a8-3347-49d8-ad99-0e792c5a30e6', '\/api\/books\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}');
        $book->{'@type'} = 'Book';
        $book->title = $matcher->like('Voluptas et tempora repellat corporis excepturi.');
        $book->description = $matcher->like('Quaerat odit quia nisi accusantium natus voluptatem. Explicabo corporis eligendi ut ut sapiente ut qui quidem. Optio amet velit aut delectus. Sed alias asperiores perspiciatis deserunt omnis. Mollitia unde id in.');
        $book->author = $matcher->like('Melisa Kassulke');
        $book->publicationDate = $matcher->dateTimeISO8601('1999-02-13T00:00:00+07:00');
        $book->reviews = $matcher->eachLike($review);

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/ld+json; charset=utf-8')
            ->setBody([
                '@context' => '/api/contexts/Book',
                '@id' => '/api/books',
                '@type' => 'hydra:Collection',
                'hydra:member' => $matcher->eachLike($book),
                'hydra:totalItems' => $matcher->like(20),
                'hydra:search' => file_get_contents(__DIR__.'/data/search.json')
            ]);


        // build up the expected results and appropriate responses
        $config      = new MockServerEnvConfig();

        $mockService = new InteractionBuilder($config);
        $mockService->given('Books Provider')
            ->uponReceiving('A GET request to return JSON')
            ->with($request)
            ->willRespondWith($response);


        $service = new BookMaker($config->getBaseUri()); // Pass in the URL to the Mock Server.
        $result = $service->createBook();

        $this->assertTrue($result, "Let's make sure we created a book");

        $hasException = false;
        try {
            $mockService->verify();
        } catch(\Exception $e) {
            $hasException = true;
        }

        $this->assertFalse($hasException, 'We expect the pacts to validate');
    }
}
