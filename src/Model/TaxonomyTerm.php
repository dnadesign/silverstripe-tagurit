<?php

namespace DNADesign\Tagurit\Model;

use DNADesign\Tagurit\Traits\TaxonomyTrait;
use Override;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Core\Validation\ValidationResult;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * Represents a single taxonomy term. Can be re-ordered in the CMS, and the default sorting is to use the order as
 * specified in the CMS.
 *
 * @property ?string $Name
 * @property int $Sort
 * @property bool $Protected
 * @method TaxonomyType Type()
 * @property int $TypeID
 */
class TaxonomyTerm extends DataObject implements PermissionProvider
{
    use TaxonomyTrait;

    private static $singular_name = 'Term';

    private static $table_name = 'TaxonomyTerm';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Sort' => 'Int',
        'Protected' => 'Boolean'
    ];

    private static $has_one = [
        'Type' => TaxonomyType::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'getIsProtected' => 'Protection'
    ];
    
    private static $default_sort = 'Sort';

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

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return Permission::check('TAXONOMYTERM_CREATE');
    }

    public function providePermissions()
    {
        return [
            'TAXONOMYTERM_EDIT' => [
                'name' => _t(
                    self::class . '.EditPermissionLabel',
                    'Edit a taxonomy term'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Taxonomy terms'
                ),
            ],
            'TAXONOMYTERM_DELETE' => [
                'name' => _t(
                    self::class . '.DeletePermissionLabel',
                    'Delete a taxonomy term and all nested terms'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Taxonomy terms'
                ),
            ],
            'TAXONOMYTERM_CREATE' => [
                'name' => _t(
                    self::class . '.CreatePermissionLabel',
                    'Create a taxonomy term'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Taxonomy terms'
                ),
            ]
        ];
    }

    #[Override]
    public function scaffoldFormFieldForHasOne(string $fieldName, ?string $fieldTitle, string $relationName, DataObject $ownerRecord): FormField
    {
        return DropdownField::create(
            $relationName,
            $fieldTitle,
            $this->getTermsForType($fieldTitle ?? $fieldName)->map()
        );
    }

    #[Override]
    public function scaffoldFormFieldForHasMany(string $relationName, ?string $fieldTitle, DataObject $ownerRecord, bool &$includeInOwnTab): FormField
    {
        $includeInOwnTab = true;

        return GridField::create(
            $relationName,
            $fieldTitle,
            $ownerRecord->$relationName(),
            GridFieldConfig_RecordEditor::create()
                ->addComponent(GridFieldOrderableRows::create('Sort'))
                ->removeComponentsByType([
                    GridFieldDeleteAction::class,
                    GridFieldAddExistingAutocompleter::class
                ])
        );
    }

    #[Override]
    public function scaffoldFormFieldForManyMany(string $relationName, ?string $fieldTitle, DataObject $ownerRecord, bool &$includeInOwnTab): FormField
    {
        $includeInOwnTab = true;

        // Term names are usually singular forms of the relation name
        $termName = (new EnglishInflector())->singularize($fieldTitle ?? $relationName)[0];

        return ListboxField::create(
            $relationName,
            $fieldTitle,
            $this->getTermsForType($termName) // @phpstan-ignore argument.type
        );
    }
}
