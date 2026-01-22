<?php

namespace DNADesign\Tagurit\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildTask to create the set of protected Types and Terms from config. 
 * The protected taxonomy is created on build, and missing records are recreated.
 */
class ClearTaxonomyTask extends BuildTask
{
    protected string $title = '[Taxonomy] Clear the Taxonomy';

    protected static string $description = "Used to truncate the TaxonomyType and TaxonomyTerm tables.";

    protected static string $commandName = "clear-taxonomy";

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Prevent the user from accidentally clearing the taxonomy Prod
        if (Director::isLive()) {
            $output->writeln('This task cannot be run on the production environment.');

            return Command::INVALID;
        }

        // Give the user a confirmation prompt
        if ($input->getOption('confirm') !== true) {
            $output->writeln('WARNING: This tasks will clear the taxonomy.');
            $output->writeln('Are you sure?');
            $output->writeln('If you are sure, please re-run this command with the --confirm option.');

            return Command::INVALID;
        }
        
        DB::query('TRUNCATE TaxonomyType');
        DB::query('TRUNCATE TaxonomyTerm');

        $output->writeln('Deleted all records from the TaxonomyType and TaxonomyTerm tables.');

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('confirm', null, InputOption::VALUE_NONE, 'Confirm that you want to clear the taxonomy tables.'),
        ];
    }
}
