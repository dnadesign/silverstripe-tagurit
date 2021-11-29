<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\Taxonomy\TaxonomyAdmin;

// Remove 
CMSMenu::remove_menu_class(TaxonomyAdmin::class);