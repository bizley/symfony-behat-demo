<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Tests\Behat\JSON\Inspector;
use App\Tests\Behat\JSON\Json;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

final class JsonContext extends CommunicationContext
{
    private Inspector $inspector;

    public function __construct(KernelInterface $kernel, string $evaluationMode = 'javascript')
    {
        $this->inspector = new Inspector($evaluationMode);
        parent::__construct($kernel);
    }

    /**
     * Checks, that the response is correct JSON
     *
     * @Then the response should be in JSON
     */
    public function theResponseShouldBeInJson(): void
    {
        $this->getJson();
    }

    private function getJson(): Json
    {
        return new Json($this->response->getContent());
    }

    /**
     * Checks, that given JSON node is equal to given value
     *
     * @Then the JSON node :node should be equal to :text
     */
    public function theJsonNodeShouldBeEqualTo($node, $text): void
    {
        $json = $this->getJson();
        $actual = $this->inspector->evaluate($json, $node);

        if ($actual != $text) {
            throw new \Exception(\sprintf("The node value is '%s'", json_encode($actual)));
        }
    }

    /**
     * Checks, that given JSON nodes contains values
     *
     * @Then the JSON nodes should contain:
     */
    public function theJsonNodesShouldContain(TableNode $nodes): void
    {
        foreach ($nodes->getRowsHash() as $node => $text) {
            $this->theJsonNodeShouldContain($node, $text);
        }
    }

    /**
     * Checks, that given JSON node contains given value
     *
     * @Then the JSON node :node should contain :text
     */
    public function theJsonNodeShouldContain($node, $text): void
    {
        $json = $this->getJson();
        $actual = $this->inspector->evaluate($json, $node);

        Assert::assertStringContainsString($text, (string) $actual);
    }

    /**
     * Sends a JSON HTTP request
     *
     * @Given I send a :method JSON request to :url
     */
    public function sendJsonRequestTo($method, $url, PyStringNode $body = null, $files = []): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ];
        $this->response = $this->kernel->handle(
            Request::create($url, $method, [], [], $files, $server, $body?->getRaw())
        );
    }

    /**
     * Sends a JSON HTTP request with a body
     *
     * @Given I send a :method JSON request to :url with body:
     */
    public function sendJsonRequestToWithBody($method, $url, PyStringNode $body): void
    {
        $this->sendJsonRequestTo($method, $url, $body);
    }
}
