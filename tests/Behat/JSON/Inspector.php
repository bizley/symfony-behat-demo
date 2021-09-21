<?php

declare(strict_types=1);

namespace App\Tests\Behat\JSON;

use Exception;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class Inspector
{
    private PropertyAccessor $accessor;

    public function __construct(private string $evaluationMode = 'javascript')
    {
        $this->accessor = new PropertyAccessor(false, true);
    }

    public function evaluate(Json $json, $expression)
    {
        if ($this->evaluationMode === 'javascript') {
            $expression = str_replace('->', '.', $expression);
        }

        try {
            return $json->read($expression, $this->accessor);
        } catch (Exception) {
            throw new Exception("Failed to evaluate expression '$expression'");
        }
    }

    public function validate(Json $json, JsonSchema $schema): bool
    {
        $validator = new Validator();

        $resolver = new SchemaStorage(new UriRetriever(), new UriResolver());
        $schema->resolve($resolver);

        return $schema->validate($json, $validator);
    }
}
