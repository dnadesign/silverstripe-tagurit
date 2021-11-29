# Tag ur it!
An alternative taxonomy module for SilverStripe

## Introduction

This module inverts the term/type relationship to type/term and allows setting default/protected types/terms

## Requirements

* SilverStripe 4.4
* GridFieldExtensions 3.2

## Installation

Include the following in your composer.json and run composer update:

```bash
"require": {
    dnadesign/silverstripe-tagurit
}
```

```bash
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/dnadesign/silverstripe-tagurit.git"
        }
    ],
```

Configure the yml:

```bash
tagurit_protected_taxonomy:
  Type one:
    - Term
    - Second Term
  Second type:
    - Another Term
```

On your page, use the trait 
```bash

    use TaxonomyTrait;

     private static $many_many = [
        'CustomTerms' => TaxonomyTerm::class
    ];
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.Terms',
            [
                ListboxField::create(
                    'CustomTerms',
                    'Custom Terms',
                    $this->getTermsForType('Second type')
                ),
            ]
        );

        return $fields;
    }
```

Run the build task:

```bash
vendor/silverstripe/framework/sake dev/build flush=1
```
