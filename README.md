
# MGB Random Number Generator
External module that adds an action-tag to this project for generating random numbers on text fields.


## Version

Version 1.0.5

********************************************************************************

## Release notes

1.0.5 Minor update: standardize branding, emails and doc updates.

1.0.4 Bug fix: fixes min and max values to be robust. Also fixes problem with leading paren making value be zero for MIN.

1.0.3 Updated PHP8 fixes and other minor update changes.

1.0.2 Initial Released version.

********************************************************************************

## Operation

### Example 1: 
Generate a random number in a text field using default configuration from 1 to 100:

@MGB_RANDOMNUMBER()
<br>Or
<br>@MGB_RANDOMNUMBER

### Example 2: 
Generate a random number from a specified range (i.e. between 101 and 110) in a text field:

@MGB_RANDOMNUMBER(101,110)

### NOTE

* Avoid spaces or hidden characters.  Use ONLY numbers.  MIN value MUST be less or equal to MAX value.  (min,max)

* IF there are errors with the values the process will SKIP adding a value and leave the field BLANK.  The error will be logged in the EM error logs and the project logs.

********************************************************************************

### License

This project is licensed under the MIT License - see the [LICENSE](?prefix=mgb_randomnumber_actiontag&page=LICENSE.md) file for details

********************************************************************************

### Acknowledgments

* Inspired by Mass General Brigham REDCap Team.
    - Original code version 1.0.2 David Clark

