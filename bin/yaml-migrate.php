<?php

declare(strict_types=1);

use Bolt\YamlMigrations\Migrate;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require_once 'vendor/autoload.php';

function addDefaultOptions(Command $command): void
{
    $command
        ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Use this configuration file for migrations', 'config.yaml');
}

$application = new Application();

// Available command
addDefaultOptions($application
    ->register('available') // Cannot use list, as it clashes with the Symfony default
    ->setDescription('Lists the available YAML migrations')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        (new Migrate($output, $input->getOption('config')))->list();

        return Command::SUCCESS;
    }));

// Process command
addDefaultOptions($application
    ->register('process')
    ->setDescription('Processes the available YAML migrations')
    ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Run only on a specific file')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        (new Migrate($output, $input->getOption('config')))->process($input->getOption('file'));

        return Command::SUCCESS;
    }));

$application->run();
