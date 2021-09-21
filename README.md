Symfony Skeleton Project
========================

This project is a demonstration of Behat testing framework usage with Symfony 5 and API Platform.

Requirements
------------

- PHP 8 (not provided with Docker here)
- Docker-Compose

How to run
----------

1. Install dependencies.

   ```shell
   composer u
   ```

2. Start database (MySQL uses port 3306, change the docker-compose file if necessary).

   ```shell
   docker-compose -f docker/docker-compose.yml up -d
   ```

3. Remove file `config/packages/test/doctrine.yaml`.

4. Update database schema for test environment.

   ```shell
   bin/console d:s:update --force -etest
   ```

5. Run Behat tests.

   ```shell
   vendor/bin/behat -f progress
   ```
