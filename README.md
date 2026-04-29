
# MGB Random Number Generator
External module that adds an action-tag to this project for generating random numbers on text fields.


## Version

Version 1.1.0

********************************************************************************

## Release notes

1.1.0 Feature: add unique number within a project. Code improvements and clean up. 

1.0.5 Minor update: standardize branding, emails and doc updates.

1.0.4 Bug fix: fixes min and max values to be robust. Also fixes problem with leading paren making value be zero for MIN.

1.0.3 Updated PHP8 fixes and other minor update changes.

1.0.2 Initial Released version.

********************************************************************************

## Operation

### NOTE:

* Range values 
    - Use only integer numbers.  
    - Zero is allowed.
    - Negative numbers are allowed.
    - Upper limit would be size of a signed 32 bit int: 2,147,483,647

* @MGB_RANDOMNUMBER()
    - If given no parameters for min and max range, then 1 to 100 is the default.
    - Config setting ( Project Level ) choice can set the range as 0 to 100 for default range.

* @MGB_RANDOMNUMBER(min,max)
    - MUST fill both values.  No blanks or empty values. 
    - When using a range the min and max ( first and second numbers ), the max MUST be larger than the min.
 
* The random number generation used is PHP: 
    - random_int - **Get a cryptographically secure, uniformly selected integer.**
    - [random_int(int $min, int $max): int](https://www.php.net/manual/en/function.random-int.php)

* Will work on Radio buttons
    - display will not show until data is saved.

* Will work on Sliders
    - display will not show until data is saved.

* Will work on Matrice
    - display will not show until data is saved.
    
* IF there are errors with the values the process will SKIP adding a value and leave the field BLANK.
    - also logging will contain some note of the error.

### Example 1: 

Generate a random number in a text field using default configuration from 1 to 100:

* The Default range for @MGB_RANDOMNUMBER() is 1 to 100. 
    - Config setting ( Project Level ) choice can set the range as 0 to 100 for default range.

@MGB_RANDOMNUMBER()
<br>Or
<br>@MGB_RANDOMNUMBER

### Example 2: 

Generate a random number from a specified range (i.e. between 101 and 110) in a text field:

@MGB_RANDOMNUMBER(101,110)

### Example 3: FOR UNIQUE NUMBER WITHIN THE PROJECT 

* Generate a UNIQUE random number from a specified range (i.e. between 10 and 9999999) in a text field:
    - @MGB_RANDOMNUMBER(10,9999999,U)
    - If the process cannot make a unique number it will leave the field empty.
    - Remember the process will check the generated values uniqueness for the field against previously **SAVED data**.

# WARNING CAUTION ADVICE

* The random unique feature needs consideration to be practical.
    - Your pool of random numbers needs to be larger than your potential total number of records.
        - For example, you plan to have 10,000 records, you will want your random number range to be many times the size of the records.
        - Possibly as an example 10,000 records and random numbers in the 100,000 range or larger.
        - The process currently uses your range of numbers, compares to the numbers in the records to make sure not repeating any of the numbers,
            - then if it generated an existing number it will reroll and check again.  The process will recheck only so many times before giving up.
            - having a large pool of numbers significantly greater than your set of records will be key to avoiding exhausting the collision check.
        - The method used here is dynamically run, meaning the pool or deck of numbers is handled in process and not predetermined or stored.
        - The unique value check looks at current SAVED data for that field.
        - Keep in mind the size of your deck and the size of amount of records.
            - If you make a range of 1 to 100 for random numbers and your number of records will be 200, you WILL run out of unique random numbers.
            - The attempt to make a unique number will compare the existing data to the generated number
                - If it generates a number that exists, it will try again, for a fixed amount of tries.
                - If all the attempts do not produce a new random number it will leave the field blank.

* Keep in mind there are also practical limits.
    - The random number can be only so large.  Currently the size of a 32 integer which is 2,147,483,647, some two billion.
    
* IF a field can not generate a value, the field will be left blank.  
    - Fields are locked for editing normally once a value is generated and filled in.
        - some fields may not lock such as: radio buttons, sliders, matrices
    - You should review and correct the MGB_RANDOMNUMBER settings for problem fields.
    - If the min and max values somehow are incorrect or some other breaking logic 
        - such as @MGB_RANDOMNUMBER(5,a), the 'a' here is incorrect and will not generate a value.

* Use of @DEFAULT action tag in unison with @MGB_RANDOMNUMBER are conflicting use.  DEFAULT wins out.

#### Future Potential Features

* Other sorts of options may involve 
    - using prefix and postfix values, 
    - using alpha and alpha numeric values beyond simply integer numbers.  
    - and other features such as an initated shuffled deck to pull from to utilize.

### NOTE

* Avoid spaces or hidden characters.  Use ONLY numbers.  MIN value MUST be less or equal to MAX value.  (min,max)

* IF there are errors with the values the process will SKIP adding a value and leave the field BLANK.  The error will be logged in the EM error logs and the project logs.

* See an included testing module.  [Download Regression Test XML project file](?prefix=mgb_randomnumber_actiontag&page=download.php)

********************************************************************************

### License

This project is licensed under the MIT License - see the [LICENSE](?prefix=mgb_randomnumber_actiontag&page=LICENSE.md) file for details

********************************************************************************

### Acknowledgments

* Inspired by Mass General Brigham REDCap Team.
    - Original code version 1.0.2 David Clark

