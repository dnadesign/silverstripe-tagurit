<?php

namespace DNADesign\Tagurit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Config\Config;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use DNADesign\Tagurit\Model\TaxonomyType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\Core\Validation\ValidationException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildTask to create the set of protected Types and Terms from config.
 * The protected taxonomy is created on build, and missing records are recreated.
 */
class BuildTaxonomyFromConfig extends BuildTask
{
    protected string $title = '[Taxonomy] Build configured Taxonomy';

    protected static string $description = "Used to create proteted taxonomy Types and Terms from config";

    protected static string $commandName = "build-taxonomy";

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $protectedTaxonomy = Config::inst()->get('tagurit_protected_taxonomy');

        if ($protectedTaxonomy && count($protectedTaxonomy) > 0) {
            $protectedTypeIds = [];

            foreach ($protectedTaxonomy as $typename => $terms) {
                $type = TaxonomyType::get()->find('Name', $typename);

                if (!$type) {
                    $output->writeln(sprintf('Creating taxonomy Type "%s"... ', $typename));
                    $type = TaxonomyType::create();
                    $type->Name = $typename;
                } else {
                    $output->writeln(sprintf('Updating taxonomy Type "%s"... ', $typename));
                }

                $type->Protected = true;
                $type->write();

                $protectedTypeIds[] = $type->ID;
            }

            foreach ($protectedTaxonomy as $typename => $terms) {
                $type = TaxonomyType::get()->find('Name', $typename);

                $names = $type->Terms()->column('Name');
                $names = array_combine($names, $names);


                if (!$terms) {
                    continue;
                }

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

                    $termObj = TaxonomyTerm::get()->filter([
                        'Name' => $term,
                        'TypeID' => $type->ID
                    ])->first();

                    if (!$termObj) {
                        $termObjs =  TaxonomyTerm::get()->filter([
                            'Name' => $term
                        ]);

                        if (!$termObjs->exists()) {
                            $output->writeln(sprintf('  + creating Term "%s" in Type "%s"... ', $term, $type->Name));
                            $termObj = TaxonomyTerm::create();
                            $termObj->Name = $term;
                        } else {
                            // one or more matches by main, check to make sure we don't have a duplicate
                            // tag
                            $termObj = null;

                            foreach ($termObjs as $candidate) {
                                if (in_array($candidate->TypeID, $protectedTypeIds)) {
                                    continue;
                                } else {
                                    $termObj = $candidate;
                                }
                            }

                            if (!$termObj) {
                                $termObj = TaxonomyTerm::create();
                                $termObj->Name = $term;
                            } else {
                                $output->writeln(sprintf('  - updating Term "%s" in Type "%s"... ', $term, $type->Name));
                            }
                        }
                    } else {
                        $output->writeln(sprintf('  - updating Term "%s" in Type "%s"... ', $term, $type->Name));
                    }

                    $termObj->TypeID = $type->ID;
                    $termObj->Protected = $protected;

                    try {
                        $termObj->write();
                    } catch (ValidationException $e) {
                        $output->writeln($e->getMessage());
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
                            $output->writeln($e->getMessage());
                        }
                    }
                }

                $output->writeln('');
            }
        } else {
            $output->writeln('No protected Types defined');
        }

        return Command::SUCCESS;
    }
}
