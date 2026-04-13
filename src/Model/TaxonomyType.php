<?php

namespace DNADesign\Tagurit\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\FieldType\DBField;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Represents a type of taxonomy, which can be configured in the CMS. 
 * This can be used to group similar taxonomy terms together.
 */
class TaxonomyType extends DataObject implements PermissionProvider
{
    private static $singular_name = 'Type';
    
    private static $table_name = 'TaxonomyType';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Protected' => 'Boolean'
    ];

    private static $has_many = [
        'Terms' => TaxonomyTerm::class
    ];

    private static $cascade_deletes = [
        'Terms'
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'getIsProtected' => 'Protection'
    ];

    private static array $scaffold_cms_fields_settings = [
        'ignoreFields' => [
            'Sort',
            'Type',
            'Protected',
        ],
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields): void {
            if ($this->isInDB() && $this->Protected > 0) {
                $fields->dataFieldByName('Name')?->setReadonly(true);
            }
        });

        return parent::getCMSFields();
    }

    public function getIsProtected()
    {
        $label = '<span class="badge">Unprotected</p>';

        if ($this->Protected > 0) {
            $label = '<span class="ss-gridfield-badge badge status-modified">Protected</p>';
        }

        return DBField::create_field(DBHTMLText::class, $label);
    }

    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        
        $filters = [
            'Name' =>$this->owner->Name,
        ];

        if ($this->owner->isInDb()) {
            $filters['ID:not'] = $this->owner->ID;
        }
        
        if (TaxonomyType::get()->filter($filters)->Count() > 0) {
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
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('TAXONOMYTYPE_EDIT');
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

        return Permission::check('TAXONOMYTYPE_DELETE');
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('TAXONOMYTYPE_CREATE');
    }

    public function providePermissions()
    {
        return [
            'TAXONOMYTYPE_EDIT' => [
                'name' => _t(
                    self::class . '.EditPermissionLabel',
                    'Edit a taxonomy type'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Taxonomy types'
                ),
            ],
            'TAXONOMYTYPE_DELETE' => [
                'name' => _t(
                    self::class . '.DeletePermissionLabel',
                    'Delete a taxonomy type'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Taxonomy types'
                ),
            ],
            'TAXONOMYTYPE_CREATE' => [
                'name' => _t(
                    self::class . '.CreatePermissionLabel',
                    'Create a taxonomy type'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Taxonomy types'
                ),
            ]
        ];
    }
}
