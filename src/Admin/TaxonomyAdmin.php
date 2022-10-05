<?php

namespace DNADesign\Tagurit\Admin;

use SilverStripe\Admin\ModelAdmin;
use DNADesign\Tagurit\Model\TaxonomyType;

/**
* Admin interface for TaxonomyTypes
*/
class TaxonomyAdmin extends ModelAdmin
{
    private static $menu_title = 'Taxonomy';

    private static $url_segment = 'taxonomy';

    private static $managed_models = [TaxonomyType::class];

    private static $menu_icon_class = 'font-icon-tags';
    
    private static $url_priority = 51;
}
