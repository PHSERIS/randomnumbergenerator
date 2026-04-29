<?php
/*
 * © 2021 Mass General Brigham (formerly Partners HealthCare). All Rights Reserved.
 * @author David Clark
 * Heavily influenced  by vork of Luke Stevcens
 *
 * Updates by David Heskett 2025 v1.0.3
 * Updates by David Heskett 2026 v1.1.0
 * 
 * This is an external module that hides the submit button on surveys
 * 
 * The trigger is similar to REDCap "action tags".
 * @MGB_RANDOMNUMBER  generates a random number between  2 values
 * USAGE:
 *
 *  @RANDOMNUMBER
 *  @RANDOMNUMBER={}
 *  @RANDOMNUMBER={MIN,MAX}
 *
 * Features 20260128
 *  TODO: add unique number
 *        and variants with pre and post fix.  alpha, alpha numeric, UUID
 * 
 *  TODO: possible new tag name for the unique:  MGB_RANDOMUNIQUE
 * 
 * 
 * 
 * 
 */

namespace MGB\RandomNumberActionTag;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

use \REDCap;
use \Form;
use \RCView;

// TODO: possible new tag name for the unique:  MGB_RANDOMUNIQUE

class RandomNumberActionTag extends AbstractExternalModule
{
    
    protected $is_survey = 0;

    protected static $Tags = array('@MGB_RANDOMNUMBER' => array('description' => ' Generates a random number between 2 values - defaults to 1,100.  Also can specify a range MGB_RANDOMNUMBER(101,110)  Add U to the list for UNIQUE random number for the project MGB_RANDOMNUMBER(1,100000000,U)') );

    const NAME_IDENTIFIER = 'RandomNumberActionTag';
    const MAX_ATTEMPTS = 20;

    // **********************************************************************   
    // **********************************************************************   
    // **********************************************************************   

    /**
     *
     */
    public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id = null, $repeat_instance = 1)
    {
        $taggedFields = $this->includeTagFunctions($instrument, $project_id);
    }

    /**
     *
     */
    public function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    {
        $this->is_survey = 1;
        $this->includeTagFunctions($instrument, $project_id);
    }
    
    /**
     * Augment the action_tag_explain content on project Design pages by 
     * adding some additional tr following the last built-in action tag.
     * @param type $project_id
     */
    public function redcap_every_page_before_render($project_id)
    {
        if (PAGE === 'Design/action_tag_explain.php') {
            global $lang;
            
            $lastActionTagDesc = end(Form::getActionTags());

            // which $lang element is this?
            $langElement = array_search($lastActionTagDesc, $lang);
            $config = $this->getConfig();

            foreach (static::$Tags as $tag => $tagAttr) {
                $lastActionTagDesc .= "</td></tr>";
                $lastActionTagDesc .= $this->makeTagTR($tag, $config['description']);
            }
            
            $lang[$langElement] = rtrim(rtrim(rtrim(trim($lastActionTagDesc), '</tr>')), '</td>');
        }
    }

    /**
     *
     */
    protected function includeTagFunctions($instrument, $project_id)
    {
        $taggedFields = array();
        $tags = static::$Tags;
        
        $instrumentFields = REDCap::getDataDictionary('array', false, true, $instrument);

        // we could just use a string literal or variation with a define
        // pattern to hunt for our TAG in the field annotations
        $pattern = '/(' . implode('|', array_keys($tags)) . ')/'; //  '/(@MGB_RANDOMNUMBER)/'
        
        if (is_countable($instrumentFields)) {
            foreach ($instrumentFields as $fieldName => $fieldDetails) {
                $matches = array();

                if (preg_match($pattern, $fieldDetails['field_annotation'], $matches)) {
                    $taggedFields[$fieldName] = $fieldDetails;
                }
            }
        }
        
        if (is_countable($taggedFields) && count($taggedFields) > 0) {
            echo $this->includeJS($taggedFields, $project_id);
        }
        
        return $taggedFields;
    }
 
    /**
     *
     */
    public function includeJS($taggedFields, $project_id)
    {
        $js = '';
        $msg = '';
        $randomNumber = '';
        $errorFlag = false;
        $flagUseUnique = false;

        $js .= "<script type='text/javascript'>$(document).ready(function() { ";
        
        if (is_countable($taggedFields)) {
            foreach ($taggedFields as $field => $details) {
                //$this->log('DETAILS:' . print_r($details, true));
                $errorFlag = false;
                
                // TODO - form validation
                $params = Form::getValueInParenthesesActionTag($details['field_annotation'],'@MGB_RANDOMNUMBER');
                $parts = explode(',', $params);
                
                $randFieldName = $details['field_name'];
                
                if (sizeof($parts) < 2) {
                    // check if default should be 1 to 100 or 0 to 100
                    $flagUseZeroRange = $this->isZeroRange();
                    
                    // No parameters passed
                    $min = 1;
                    $max = 100;
                    
                    if ($flagUseZeroRange) {
                        $min = 0;
                    }
                } else {
                    $min = $parts[0];
                    $max = $parts[1];
                    
                    // Check for our use UNIQUE Random number feature
                    $flagUseUnique = false;
                    if (isset($parts[2])) {  // check for Unique Random
                        $cmdUnique = $parts[2];
                        $cmdUnique = trim($cmdUnique, ')');
                        $cmdUnique = trim($cmdUnique, ' ');
                        
                        if ($cmdUnique == 'U') {
                            $flagUseUnique = true;  // USE **Unique Random**
                        }
                    }

                    // remove parens and spaces
                    $min = trim($min, '(');
                    $max = trim($max, ')');
                    $min = trim($min, ' ');
                    $max = trim($max, ' ');
                    
                   // TODO: consider using to allow use of zero
                   //   if (!($min || $min === 0 || $min === "0")) {  
                   // instead of
                   //   if (empty($min)) {
                   //
                   // also for max
                   //
                    // protect against cases missing params such as:  MGB_RANDOMNUMBER(,200) or MGB_RANDOMNUMBER(100,)
                    // and log it
                    // will skip entering a value in the field
                    //
                    //if (empty($min)) {
                    if (!($min || $min === 0 || $min === "0")) { 
                        $msg = 'RandomNumberActionTag WARNING: MIN is EMPTY for FIELD: ' . $field . ' ' . $details['field_annotation'];
                        $this->log($msg);
                        $this->projectLogging($msg);
                        $errorFlag = true;
                    }
                    //if (empty($max)) {
                    if (!($max || $max === 0 || $max === "0")) { 
                        $msg = 'RandomNumberActionTag WARNING: MAX is EMPTY for FIELD: ' . $field . ' ' . $details['field_annotation'];
                        $this->log($msg);
                        $this->projectLogging($msg);
                        $errorFlag = true;
                    }
                    
                    if (!is_numeric($min)) {
                        $msg = 'RandomNumberActionTag WARNING: MIN is not a number for FIELD: ' . $field . ' ' . $details['field_annotation'];
                        $this->log($msg);
                        $this->projectLogging($msg);
                        $errorFlag = true;
                    }
                    if (!is_numeric($max)) {
                        $msg = 'RandomNumberActionTag WARNING: MAX is not a number for FIELD: ' . $field . ' ' . $details['field_annotation'];
                        $this->log($msg);
                        $this->projectLogging($msg);
                        $errorFlag = true;
                    }
                }
                
//                if (intval($min) <= intval($max)) {
                if (intval($min) < intval($max)) {
                    if (!$errorFlag) {
                        try {
                            $randomNumber = random_int(intval($min), intval($max));
                        } catch (\Exception $e) {
                           $errorFlag = true;
                           $msg = 'RandomNumberActionTag Error: Exception with random number generation';
                           $this->log($msg);
                           continue;
                        }
                        
                        // Begin UNIQUE Random number handling 
                        if($flagUseUnique == true) {
                            $fieldName = $randFieldName; // get the random field
                            $attempt = 0;
                            $maxAttempts = RandomNumberActionTag::MAX_ATTEMPTS;
                            
                            do {
                                $isUnique = $this->isUnique($project_id, $fieldName, $randomNumber);
                                
                                if ($isUnique) {
                                    //$msg = 'Unique: ' . $randomNumber . ' : ' . $attempt;
                                    //$this->log($msg);
                                    $attempt = $maxAttempts + 1;  // end the loop
                                } else {
                                    // retry
                                    $randomNumber = random_int(intval($min), intval($max));
                                    $attempt++;  // eventually end the loop stop trying
                                    
                                    if ($attempt >= $maxAttempts) {
                                        $msg = 'RandomNumberActionTag Error: exhausted number of reroll attempts.';
                                        $this->log($msg);
                                        $this->projectLogging($msg);
                                        $randomNumber = '';
                                        //$errorFlag = true;
                                    }
                                }
                                
                            } while ($attempt < $maxAttempts);
                        }
                        // End UNIQUE Random number handling 
                    }
                } else {
                    // we could skip this message if we already have an error flag, however, it can be telling if we arrive here as well.
                    
                    $rangeErr = 'GREATER than';
                    
                    if (intval($min) == intval($max)) {
                        $rangeErr = 'EQUAL to';
                    }
                    
                    $msg = 'RandomNumberActionTag ERROR: MIN is ' . $rangeErr . ' MAX for FIELD: ' . $field . ' ' . $details['field_annotation'];
                    $this->log($msg);
                    $this->projectLogging($msg);
                    $errorFlag = true;
                }
                
                if ($errorFlag) {
                    // gracefully skip this field as we have bad values to work with.
                    $randomNumber = '';
                    
                    // by doing this, continue, will allow EDIT of the empty field
                    // TODO: Question do we disable edit of the field or allow it to be edited if empty?
                    //continue;  
                    // TODO: if field can not generate a value add a note on screen next to field to indicate issue
                }
                
                // IF we have good values we can proceed
                
                $js .=  "
                 if ( $(\"[name = '$field']\").val() != \"undefined\" && $(\"[name = '$field']\").val() ) {
                  } else { 
                   $(\"[name = '$field']\").val(\"$randomNumber\");
                 }";
                 
                if (strlen($randomNumber) <= 0) {
                  // show and enable regardless due to error
                   $js .=  "$(\"#" . $field . "-tr\").show();";
                   $js .=  "$(\"[name = '$field']\").prop('disabled','false');"; // enable the element
                } else {
                   $js .=  "$(\"[name = '$field']\").prop('disabled','true');"; // disable the element
                }
            }
        }

        $js .=  "});</script>";

        return $js;
    }

    /**
     * Make a table row for an action tag copied from 
     * v8.5.0/Design/action_tag_explain.php
     * @global type $isAjax
     * @param type $tag
     * @param type $description
     * @return type
     */
    protected function makeTagTR($tag, $description)
    {
        global $isAjax, $lang;
        
        return RCView::tr(
            array(),
            RCView::td(
                array('class' => 'nowrap', 'style' => 'text-align:center;background-color:#f5f5f5;color:#912B2B;padding:7px 15px 7px 12px;font-weight:bold;border:1px solid #ccc;border-bottom:0;border-right:0;'),
                ((!$isAjax || (isset($_POST['hideBtns']) && $_POST['hideBtns'] == '1')) ? '' :
                    RCView::button(array('class' => 'btn btn-xs btn-rcred', 'style' => '', 'onclick' => "$('#field_annotation').val(trim('" . js_escape($tag) . " '+$('#field_annotation').val())); highlightTableRowOb($(this).parentsUntil('tr').parent(),2500);"), $lang['design_171']))
            ) .
                RCView::td(
                    array('class' => 'nowrap', 'style' => 'background-color:#f5f5f5;color:#912B2B;padding:7px;font-weight:bold;border:1px solid #ccc;border-bottom:0;border-left:0;border-right:0;'),
                    $tag
                ) .
                RCView::td(
                    array('style' => 'font-size:12px;background-color:#f5f5f5;padding:7px;border:1px solid #ccc;border-bottom:0;border-left:0;'),
                    '<i class="fas fa-cube mr-1"></i>' . $description
                )
        );
    }


    /**
     * projectLogging - log message in project logs.
     */
    public function projectLogging($logMsg, $logDescription = self::NAME_IDENTIFIER)
    {
        // $logDescription is 'Action' in the logging View
        
        REDCap::logEvent($logDescription, $logMsg);
    }

    /**
     * isZeroRange - defaultrange: Default range: 1 to 100 or 0 to 100 
     *  FALSE = 1 to 100
     *  TRUE  = 0 to 100
     */
    public function isZeroRange() {
        $flag = FALSE;
        
        $rangeSetting = $this->getProjectSetting('defaultrange');
        
        $flag = ( $rangeSetting ? TRUE : FALSE );
        
        return $flag;
    }
     
    /**
     * isUnique - .
     */
    public function isUnique($project_id, $fieldName, $randomNumber) {
        $flag = $this->isRandomNumberInField($project_id, $fieldName, $randomNumber);
        
        return !$flag;  // flip the bit
    }
    
    /**
     * isRandomNumberInField - .
     * 
     * Compare a given random number with the values in a specific field for all records in a REDCap project.
     *
     * @param int $project_id The REDCap project ID
     * @param string $fieldName The field name to compare against
     * @param mixed $randomNumber The random number to compare
     * @return bool Returns true if the random number exists in the field, false otherwise
     */
    public function isRandomNumberInField($project_id, $fieldName, $randomNumber) {
        
        $records = REDCap::getData($project_id, 'array', null, [$fieldName]);
        
        foreach ($records as $record) {
            foreach ($record as $event => $fields) {
                if (isset($fields[$fieldName]) && $fields[$fieldName] == $randomNumber) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * getFieldValuesForAllRecords - .
     * 
     * Get values from a specific field for all records in a REDCap project.
     *
     * @param int $project_id The REDCap project ID
     * @param string $fieldName The field name to extract
     * @param bool $flagAllEvents True = collect all events, False = Only take first event if longitudinal, Defaults to False: only take first event if longitudinal
     * @return array Returns an associative array [record_id => value] for the given field
     */
    public function getFieldValuesForAllRecords($project_id, $fieldName, $flagAllEvents = false) {
        $result = [];
        $records = REDCap::getData($project_id, 'array', null, [$fieldName]);
        
        foreach ($records as $record_id => $events) {
            foreach ($events as $event_data) {
                if (isset($event_data[$fieldName])) {
                    $result[$record_id] = $event_data[$fieldName];
                    
                    if (!$flagAllEvents) {
                        break; // Only take first event if longitudinal; remove if you want all events
                    }
                }
            }
        }
        
        return $result;
    }

}
