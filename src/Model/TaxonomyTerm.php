<?php

namespace DNADesign\Tagurit\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents a single taxonomy term. Can be re-ordered in the CMS, and the default sorting is to use the order as
 * specified in the CMS.
 *
 * @property string $Name
 * @property int $Sort
 * @property int $TypeID
 */
class TaxonomyTerm extends DataObject implements PermissionProvider
{
    private static $singular_name = 'Term';

    private static $table_name = 'TaxonomyTerm';

    private static $db = array(
        'Name' => 'Varchar(255)',
        'Sort' => 'Int',
        'Protected' => 'Boolean'
    );

    private static $has_one = array(
        'Type' => TaxonomyType::class
    );

    private static $summary_fields = [
        'Title' => 'Title',
        'getIsProtected' => 'Protection'
    ];
    
    private static $default_sort = 'Sort';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Protected',
            'Sort',
            'TypeID'
        ]);

        if ($this->isInDB() && $this->Protected > 0) {
            $fields->dataFieldByName('Name')->setReadonly(true);
        }

        return $fields;
    }

    public function getIsProtected()
    {
        $label = '<span class="badge">Unprotected</p>';

        if ($this->Protected > 0) {
            $label = '<span class="ss-gridfield-badge badge status-modified">Protected</p>';
        }

        return DBField::create_field(DBHTMLText::class, $label);
    }

    public function validate()
    {
        $result = ValidationResult::create();
        
        $filters = [
            'Name' =>$this->Name,
            'TypeID' => $this->TypeID
        ];

        if ($this->isInDb()) {
            $filters['ID:not'] = $this->ID;
        }
        
        if (TaxonomyTerm::get()->filter($filters)->Count() > 0) {
            $result->addFieldError('Name', 'Name must be unique');
        }

        return $result;
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        if ($this->Protected > 0) {
            return false;
        }

        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('TAXONOMYTERM_EDIT');
    }

    public function canDelete($member = null)
    {
        if ($this->Protected > 0) {
            return false;
        }

        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('TAXONOMYTERM_DELETE');
    }

    public function canCreate($member = null, $context = array())
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('TAXONOMYTERM_CREATE');
    }

    public function providePermissions()
    {
        return array(
            'TAXONOMYTERM_EDIT' => array(
                'name' => _t(
                    __CLASS__ . '.EditPermissionLabel',
                    'Edit a taxonomy term'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Taxonomy terms'
                ),
            ),
            'TAXONOMYTERM_DELETE' => array(
                'name' => _t(
                    __CLASS__ . '.DeletePermissionLabel',
                    'Delete a taxonomy term and all nested terms'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Taxonomy terms'
                ),
            ),
            'TAXONOMYTERM_CREATE' => array(
                'name' => _t(
                    __CLASS__ . '.CreatePermissionLabel',
                    'Create a taxonomy term'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Taxonomy terms'
                ),
            )
        );
    }
}
