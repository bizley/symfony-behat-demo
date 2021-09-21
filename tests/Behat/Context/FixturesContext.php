<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use RuntimeException;

final class FixturesContext implements Context
{
    private const EXT = '.yml';
    private const PATH = '/tests/Behat/Fixtures/';

    public function __construct(
        private LoaderInterface $ORMLoader,
        private EntityManagerInterface $entityManager,
        private string $projectDir
    ) {
    }

    /**
     * @Given the following fixtures are loaded:
     * @Given /^the following fixtures are loaded using the (append|delete|truncate) purger:$/
     */
    public function thereAreSeveralFixtures(TableNode $fixtures, string $purgeMode = 'truncate'): void
    {
        $purgeMode = match ($purgeMode) {
            'append' => PurgeMode::createNoPurgeMode(),
            '', 'truncate' => PurgeMode::createTruncateMode(),
            'delete' => PurgeMode::createDeleteMode(),
            default => throw new RuntimeException('Invalid purge mode'),
        };

        $fixturesFiles = [];
        $fixturesFileRows = $fixtures->getRows();
        foreach ($fixturesFileRows as $fixturesFileRow) {
            $fixturesFiles[] = $this->projectDir . self::PATH . $fixturesFileRow[0] . self::EXT;
        }

        $this->ORMLoader->load($fixturesFiles, [], [], $purgeMode);
        $this->entityManager->clear();
    }
}
