<?php

declare(strict_types=1);

namespace App\Tests\Behat\JSON;

use Exception;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

final class JsonSchema extends Json
{
    public function __construct(string $content, private ?string $uri = null)
    {
        parent::__construct($content);
    }

    public function resolve(SchemaStorage $resolver): self
    {
        if (!$this->hasUri()) {
            return $this;
        }

        $this->content = $resolver->resolveRef($this->uri);

        return $this;
    }

    public function validate(Json $json, Validator $validator): bool
    {
        $validator->check($json->getContent(), $this->getContent());

        if (!$validator->isValid()) {
            $msg = "JSON does not validate. Violations:".PHP_EOL;
            foreach ($validator->getErrors() as $error) {
                $msg .= \sprintf("  - [%s] %s".PHP_EOL, $error['property'], $error['message']);
            }
            throw new Exception($msg);
        }

        return true;
    }

    private function hasUri(): bool
    {
        return null !== $this->uri;
    }
}
