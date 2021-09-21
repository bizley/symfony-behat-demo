<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

final class DbContext implements Context
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @Then /^there is row in "([^"]*)" table:$/
     */
    public function thereShouldBeRowInDb(string $dbname, TableNode $table): void
    {
        $hash = $table->getRowsHash();
        $result = $this->countRowsFromDb($dbname, $hash);

        if ($result === 0) {
            throw new Exception(\sprintf('Not found in %s any rows like %s', $dbname, \print_r($hash, true)));
        }
    }

    /**
     * @param string $dbname
     * @param $hash
     * @return int
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function countRowsFromDb(string $dbname, $hash): int
    {
        $from = 'App\Entity\%s';
        $where = 'e.%s = :%s';
        $whereNull = 'e.%s IS NULL';
        $whereNotNull = 'e.%s IS NOT NULL';
        $whereDateTime = 'ABS(EXTRACT(EPOCH FROM CAST(e.%s AS TIMESTAMP)) - EXTRACT(EPOCH FROM CAST(:%s AS TIMESTAMP))) <= 60';
        $patternDate = '/NOW\+(\d+)/m';
        $patternPastDate = '/NOW-(\d+)/m';
        $patternDay = '/DAY\+(\d+) ([\d]{2}):([\d]{2})/m';

        $query = $this->entityManager->createQueryBuilder();
        $query->from(\sprintf($from, $dbname), 'e');
        $query->select('count(e)');

        $matches = [];
        foreach ($hash as $column => $value) {
            $value = $this->convertValueToMatchingType($value);

            if (\preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                //check if value is datetime and check one minute diff with this datetime
                $query->andWhere(\sprintf($whereDateTime, $column, \str_replace('.', '_', $column)));
            } elseif (\preg_match($patternDate, $value, $matches)) {
                $dateExpected = new DateTime(\sprintf('+%d minutes', $matches[1]));
                $value = $dateExpected->format('Y-m-d H:i:s');
                //check if value is datetime and check one minute diff with this datetime
                $query->andWhere(\sprintf($whereDateTime, $column, \str_replace('.', '_', $column)));
            } elseif (\preg_match($patternPastDate, $value, $matches)) {
                $dateExpected = new DateTime(\sprintf('-%d minutes', $matches[1]));
                $value = $dateExpected->format('Y-m-d H:i:s');
                //check if value is datetime and check one minute diff with this datetime
                $query->andWhere(\sprintf($whereDateTime, $column, \str_replace('.', '_', $column)));
            } elseif (\preg_match($patternDay, $value, $matches)) {
                $dateExpected = new DateTime(\sprintf('+%d day %s:%s', $matches[1], $matches[2], $matches[3]));
                $value = $dateExpected->format('Y-m-d H:i:s');
                //check if value is datetime and check one minute diff with this datetime
                $query->andWhere(\sprintf($whereDateTime, $column, \str_replace('.', '_', $column)));
            } elseif ($value === 'NULL') {
                $query->andWhere(\sprintf($whereNull, $column));
            } elseif ($value === 'NOT NULL') {
                $query->andWhere(\sprintf($whereNotNull, $column));
            } else {
                $query->andWhere(\sprintf($where, $column, \str_replace('.', '_', $column)));
            }
            if ($value !== 'NULL' && $value !== 'NOT NULL') {
                $query->setParameter(\str_replace('.', '_', $column), $value);
            }
        }

        return (int)$query->getQuery()->getSingleScalarResult();
    }

    private function convertValueToMatchingType(string $value): array|string|null
    {
        $patternDateFuture = '/NOW\+(\d+)/m';
        $patternDatePast = '/NOW-(\d+)/m';
        if (\preg_match($patternDateFuture, $value, $matches)) {
            $date = new DateTime(\sprintf('+%d minutes', $matches[1]));

            return \preg_replace($patternDateFuture, $date->format('Y-m-d H:i:s'), $value);
        }
        if (\preg_match($patternDatePast, $value, $matches)) {
            $date = new DateTime(\sprintf('-%d minutes', $matches[1]));

            return \preg_replace($patternDatePast, $date->format('Y-m-d H:i:s'), $value);
        }

        return $value;
    }

    /**
     * @Then /^there are no rows in "([^"]*)" table:$/
     */
    public function thereShouldNotBeRowsInDb(string $dbname, TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $hash) {
            $result = $this->countRowsFromDb($dbname, $hash);
            if ($result !== 0) {
                throw new Exception(\sprintf('Found in %s rows like %s', $dbname, \print_r($hash, true)));
            }
        }
    }
}
