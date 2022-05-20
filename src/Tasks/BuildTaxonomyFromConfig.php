<?php

namespace DNADesign\Tagurit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Config\Config;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use DNADesign\Tagurit\Model\TaxonomyType;
use SilverStripe\Control\Director;
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

        if ($terms && count($terms) > 0) {
            foreach ($terms as $typename => $terms) {
                $type = TaxonomyType::get()->find('Name', $typename);

                if (!$type) {
                    echo sprintf('Creating taxonomy Type "%s"... ', $typename) . $this->eol();
                    $type = TaxonomyType::create();
                    $type->Name = $typename;
                } else {
                    echo sprintf('Updating taxonomy Type "%s"... ', $typename) . $this->eol();
                }

                $type->Protected = true;
                $type->write();

                if (!$terms) {
                    continue;
                }

                $names = $type->Terms()->column('Name');
                $names = array_combine($names, $names);

                foreach ($terms as $term) {
                    if (is_array($term)) {
                        $k = array_key_first($term);
                        $protected = $term[$k];
                        $term = $k;
                    } else {
                        $protected = true;
                    }

                    if (isset($names[$term])) {
                        unset($names[$term]);
                    }

                    $termObj = TaxonomyTerm::get()->find('Name', $term);

                    if (!$termObj) {
                        echo sprintf('  + creating Term "%s" in Type "%s"... ', $term, $type->Name) . $this->eol();
                        $termObj = TaxonomyTerm::create();
                        $termObj->Name = $term;
                    } else {
                        echo sprintf('  - updating Term "%s" in Type "%s"... ', $term, $type->Name) . $this->eol();
                    }

                    $termObj->TypeID = $type->ID;
                    $termObj->Protected = $protected;

                    try {
                        $termObj->write();
                    } catch (ValidationException $e) {
                        echo $e->getMessage() . $this->eol();
                    }
                }

                // we don't have any more protected so make sure they are removed
                if ($names) {
                    $clear = $type->Terms()->filter('Name', $names);

                    foreach ($clear as $tag) {
                        $tag->Protected = false;

                        try {
                            $tag->write();
                        } catch (ValidationException $e) {
                            echo $e->getMessage() . $this->eol();
                        }
                    }
                }

                echo $this->eol();
            }
        } else {
            echo 'No protected Types defined' . $this->eol();
        }
    }

    public function eol()
    {
        if (Director::is_cli()) {
            echo PHP_EOL;
        } else {
            echo '<br />';
        }
    }
}
