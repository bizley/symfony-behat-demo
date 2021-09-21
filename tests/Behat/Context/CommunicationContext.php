<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use PHPUnit\Framework\Assert;

class CommunicationContext implements Context
{
    protected Response $response;

    public function __construct(protected KernelInterface $kernel)
    {
    }

    /**
     * Sends an HTTP request
     *
     * @Given I send a :method request to :url
     */
    public function sendRequestTo($method, $url, PyStringNode $body = null, $files = []): void
    {
        $this->response = $this->kernel->handle(
            Request::create($url, $method, [], [], $files, [], $body?->getRaw())
        );
    }

    /**
     * Sends an HTTP request with some parameters
     *
     * @Given I send a :method request to :url with parameters:
     */
    public function sendRequestToWithParameters($method, $url, TableNode $data): void
    {
        $parameters = [];

        foreach ($data->getHash() as $row) {
            if (!isset($row['key'], $row['value'])) {
                throw new \Exception("You must provide a 'key' and 'value' column in your table node.");
            }

            $parameters[$row['key']] = $row['value'];
        }

        $this->response = $this->kernel->handle(Request::create($url, $method, $parameters));
    }

    /**
     * Sends an HTTP request with a body
     *
     * @Given I send a :method request to :url with body:
     */
    public function sendRequestToWithBody($method, $url, PyStringNode $body): void
    {
        $this->sendRequestTo($method, $url, $body);
    }

    /**
     * Checks, whether the response content is null or empty string
     *
     * @Then the response should be empty
     */
    public function theResponseShouldBeEmpty(): void
    {
        $actual = $this->response->getContent();
        $message = "The response of the current page is not empty, it is: $actual";
        Assert::assertEmpty($actual, $message);
    }

    /**
     * @Then /^the response status code should be (?P<code>\d+)$/
     */
    public function assertResponseStatus(int $code): void
    {
        Assert::assertSame($code, $this->response->getStatusCode());
    }
}
