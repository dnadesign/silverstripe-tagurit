<?php

namespace DNADesign\Tagurit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Config\Config;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use DNADesign\Tagurit\Model\TaxonomyType;
use SilverStripe\ORM\ValidationException;

/**
 * BuildTask to create the set of protected Types and Terms from config. 
 * The protected taxonomy is created on build, and missing records are recreated.
 */
class BuildTaxonomyFromConfig extends BuildTask
{
    protected $title = '[Taxonomy] Build configured Taxonomy';

    protected $description = "Used to create proteted taxonomy Types and Terms from config";

    private static $segment = "build-taxonomy";

    protected $enabled = true;

    public function run($request)
    {
        $terms = Config::inst()->get('tagurit_protected_taxonomy');

        if (count($terms) > 0) {

            foreach ($terms as $typename => $terms) {

                // Check whether the type already exists,
                $existingType = TaxonomyType::get()->find('Name', $typename);
                $type = $existingType ? $existingType : TaxonomyType::create();

                // if it doesn't, create it. 
                if (is_null($existingType)) {
                    echo sprintf('Creating taxonomy Type "%s"... ', $typename);
                    $type->Name = $typename;
                    $type->Protected = true;

                    try {
                        $type->write();
                        echo 'done. <br>';
                    } catch (ValidationException $e) {
                        echo 'failed. <br>';
                    }
                } else {
                    // if it does, proceed to the terms.
                    echo sprintf('Found taxonomy Type "%s". <br>', $typename);
                }

                if (!$terms) {
                    continue;
                }
                
                foreach ($terms as $term) {
                    // Check whether the term already exists                    
                    if (is_null(TaxonomyTerm::get()->find('Name', $term))) {
                        echo sprintf('Creating Term "%s" in Type "%s"... ', $term, $type->Name);
                        $newTerm = TaxonomyTerm::create();
                        $newTerm->Name = $term;
                        $newTerm->TypeID = $type->ID;
                        $newTerm->Protected = true;

                        try {
                            $newTerm->write();
                            echo 'done. <br>';
                        } catch (ValidationException $e) {
                            echo 'failed. <br>';
                        }
                    } else {
                        echo sprintf('Found Term "%s". <br>', $typename);
                    }

                }
                echo '<br>';
            }
            
        } else {
            echo 'No protected Types defined<br>';
        }
    }
}
