# ACF VTM Field

Field for selection of a [Dictionary of Medicines and Devices (dm+d)](https://www.nhsbsa.nhs.uk/pharmacies-gp-practices-and-appliance-contractors/nhs-dictionary-medicines-and-devices-dmd) VTM.

### Structure

* `/assets`:  folder for all asset files.
* `/assets/css`:  folder for .css files.
* `/assets/images`: folder for image files
* `/assets/js`: folder for .js files
* `/fields`:  folder for all field class files.
* `/fields/acf-vtm-v5.php`: Field class compatible with ACF version 5 
* `/fields/acf-vtm-v4.php`: Field class compatible with ACF version 4
* `/lang`: folder for .pot, .po and .mo files
* `acf-vtm.php`: Main plugin file that includes the correct field file based on the ACF version
* `readme.txt`: WordPress readme file to be used by the WordPress repository

-----------------------

# ACF VTM Field

VTM selection from the [Dictionary of Medicines and Devices (dm+d)](https://www.nhsbsa.nhs.uk/pharmacies-gp-practices-and-appliance-contractors/nhs-dictionary-medicines-and-devices-dmd)

-----------------------

### Compatibility

This ACF field type is compatible with:
* ACF 5
* ACF 4

### Installation

Include via composer

e.g. 

```
"repositories": {
    {
      "type": "git",
      "url": "https://github.com/makeandship/acf-vtm.git"
    },
    ...
}
```

and

```
"require": {
  "makeandship/acf-vtm": "v1.0.0",
  ...
}
```

## Development

### Formatting

Uses `prettier` and `@prettier/plugin-php` for code formatting

Install dependencies using `npm i` 