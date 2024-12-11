<?php

namespace DNADesign\Tagurit\Traits;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use DNADesign\Tagurit\Model\TaxonomyType;

trait TaxonomyTrait
{
    /**
     * Helper to find a taxonomy type either by ID or name
     *
     * @param int|string $type
     * @return ?TaxonomyType
     */
    public static function findTaxonomyType($type)
    {
        if (is_numeric($type)) {
            return TaxonomyType::get()->find('ID', $type);
        }

        return TaxonomyType::get()->find('Name:nocase', $type);
    }

    /**
     * Return taxonomy terms for a type specified as a string
     *
     * @param int|string $type
     * @return ArrayList<TaxonomyTerm>|DataList<TaxonomyTerm>
     */
    public static function getTermsForType($type)
    {
        $type = self::findTaxonomyType($type);

        if ($type && $type->exists()) {
            return TaxonomyTerm::get()->filter('TypeID', $type->ID);
        }

        return new ArrayList();
    }
}
