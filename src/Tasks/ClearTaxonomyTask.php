<?php

namespace DNADesign\Tagurit\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildTask to create the set of protected Types and Terms from config. 
 * The protected taxonomy is created on build, and missing records are recreated.
 */
class ClearTaxonomyTask extends BuildTask
{
    protected string $title = '[Taxonomy] Clear the Taxonomy';

    protected static string $description = "Used to truncate the TaxonomyType and TaxonomyTerm tables.";

    private static $segment = "clear-taxonomy";

    protected $enabled = true;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Prevent the user from accidentally clearing the taxonomy Prod
        if (Director::isLive()) {
            echo '<p>This task cannot be run on the production environment.</p>';
            exit;
        }

        // Give the user a confirmation prompt
        if (!$request->getVar('confirm')) {
            echo '<p>WARNING: This tasks will clear the taxonomy.</p>';
            echo '<p>Are you sure?</p>';
            echo '<a href="/dev/tasks/clear-taxonomy?confirm=1">Yes, I\'m sure</a>';
            exit;
        }
        
        DB::query('TRUNCATE TaxonomyType');
        DB::query('TRUNCATE TaxonomyTerm');

        echo '<p>Deleted all records from the TaxonomyType and TaxonomyTerm tables.</p>';

        return Command::SUCCESS;
    }
}
