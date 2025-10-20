<?php

namespace DNADesign\Tagurit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLUpdate;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildTask to create the set of protected Types and Terms from config. 
 * The protected taxonomy is created on build, and missing records are recreated.
 */
class UnprotectTaxonomyTerms extends BuildTask
{
    protected string $title = '[Taxonomy] Un-protect Taxonomy Terms';

    protected static string $description = "Set every Taxonomy Term as un-protected";

    private static $segment = "unprotect-taxonomy-terms";

    protected $enabled = true;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = DataObject::getSchema()->tableForField(TaxonomyTerm::class, 'Protected');
        if (!$table) {
            exit('Could not locate DB table to update!');
        }

        $query = SQLUpdate::create($table, ['Protected' => false]);
        $query->execute();

        echo 'Done.';
        
        return Command::SUCCESS;
    }
}