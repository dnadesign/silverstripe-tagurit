<?php

namespace DNADesign\Tagurit\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\FieldType\DBField;
use DNADesign\Tagurit\Model\TaxonomyTerm;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * Represents a type of taxonomy, which can be configured in the CMS. 
 * This can be used to group similar taxonomy terms together.
 */
class TaxonomyType extends DataObject implements PermissionProvider
{
    private static $singular_name = 'Type';
    
    private static $table_name = 'TaxonomyType';

    private static $db = array(
        'Name' => 'Varchar(255)',
        'Protected' => 'Boolean'
    );

    private static $has_many = array(
        'Terms' => TaxonomyTerm::class
    );

    private static $cascade_deletes = [
        'Terms'
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'getIsProtected' => 'Protection'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Protected',
            'Terms'
        ]);

        if ($this->isInDB()) {
            $termsGrid = GridFieldConfig_RecordEditor::create();

            $termsGrid->removeComponentsByType([
                GridFieldAddExistingAutocompleter::class,
                GridFieldDeleteAction::class
            ]);

            $fields->addFieldToTab(
                'Root.Main',
                GridField::create(
                    'Terms',
                    'Terms',
                    $this->Terms(),
                    $termsGrid
                )
            );
    
            $termsGrid->addComponent(GridFieldOrderableRows::create('Sort'));
        
            if ($this->Protected > 0) {
                $fields->dataFieldByName('Name')->setReadonly(true);
            }
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

    public function canCreate($member = null, $context = array())
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('TAXONOMYTYPE_CREATE');
    }

    public function providePermissions()
    {
        return array(
            'TAXONOMYTYPE_EDIT' => array(
                'name' => _t(
                    __CLASS__ . '.EditPermissionLabel',
                    'Edit a taxonomy type'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Taxonomy types'
                ),
            ),
            'TAXONOMYTYPE_DELETE' => array(
                'name' => _t(
                    __CLASS__ . '.DeletePermissionLabel',
                    'Delete a taxonomy type'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Taxonomy types'
                ),
            ),
            'TAXONOMYTYPE_CREATE' => array(
                'name' => _t(
                    __CLASS__ . '.CreatePermissionLabel',
                    'Create a taxonomy type'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Taxonomy types'
                ),
            )
        );
    }
}
