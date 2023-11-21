<?php

namespace DNADesign\Tagurit\Tasks;

use DNADesign\Tagurit\Model\TaxonomyTerm;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLUpdate;

/**
 * BuildTask to create the set of protected Types and Terms from config. 
 * The protected taxonomy is created on build, and missing records are recreated.
 */
class UnprotectTaxonomyTerms extends BuildTask
{
    protected $title = '[Taxonomy] Un-protect Taxonomy Terms';

    protected $description = "Set every Taxonomy Term as un-protected";

    private static $segment = "unprotect-taxonomy-terms";

    protected $enabled = true;

    public function run($request)
    {
        $table = DataObject::getSchema()->tableForField(TaxonomyTerm::class, 'Protected');
        if (!$table) {
            exit('Could not locate DB table to update!');
        }

        $query = SQLUpdate::create($table, ['Protected' => false]);
        $query->execute();

        echo 'Done.';
    }
}