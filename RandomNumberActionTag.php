<?php
/*
 * © 2021 Mass General Brigham (formerly Partners HealthCare). All Rights Reserved.
 * @author David Clark
 * Heavily influenced  by vork of Luke Stevcens
 *
 * Updates by David Heskett 2025 v1.0.3
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
 */

namespace MGB\RandomNumberActionTag;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


class RandomNumberActionTag extends AbstractExternalModule
{
    protected $is_survey = 0;

    protected static $Tags = array('@MGB_RANDOMNUMBER' => array('description' => ' Generates a random number between 2 values - defaults to 0,100.  Also can specify a range MGB_RANDOMNUMBER(101,110)') );

    /**
     *
     */
//    public function redcap_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $repeat_instance = 1)
    public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id = null, $repeat_instance = 1)
    {
        $taggedFields = $this->includeTagFunctions($instrument);
    }

    /**
     *
     */
//    public function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    public function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    {
        $this->is_survey = 1;
        $this->includeTagFunctions($instrument);
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
            
            $lastActionTagDesc = end(\Form::getActionTags());

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
    protected function includeTagFunctions($instrument)
    {
        $taggedFields = array();
        $tags = static::$Tags;
        
        $instrumentFields = \REDCap::getDataDictionary('array', false, true, $instrument);

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
            $this->includeJS($taggedFields);
        }
        
        return $taggedFields;
    }
 
    /**
     *
     */
    public function includeJS($taggedFields)
    {

        echo "<script type='text/javascript'>
$(document).ready(function() { 
";

        $errorFlag = false;

        if (is_countable($taggedFields)) {
            foreach ($taggedFields as $field => $details) {
                // TODO - form validation
                $params = \Form::getValueInParenthesesActionTag($details['field_annotation'],'@MGB_RANDOMNUMBER');
                $parts = explode(',', $params);
                
                if (sizeof($parts) < 2) {
                    // No parameters passed
                    $min = 1;
                    $max = 100;
                } else {
                    $min = $parts[0];
                    $max = $parts[1];

                    // remove parens and spaces
                    $min = trim($min, '(');
                    $max = trim($max, ')');
                    $min = trim($min, ' ');
                    $max = trim($max, ' ');
                    
                    // protect against cases missing params such as:  MGB_RANDOMNUMBER(,200) or MGB_RANDOMNUMBER(100,)
                    // and log it
                    // will skip entering a value in the field
                    //
                    if (empty($min)) {
                        $this->log('RandomNumberActionTag WARNING: MIN is EMPTY for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $this->projectLogging('RandomNumberActionTag WARNING: MIN is EMPTY for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $errorFlag = true;
                    }
                    if (empty($max)) {
                        $this->log('RandomNumberActionTag WARNING: MAX is EMPTY for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $this->projectLogging('RandomNumberActionTag WARNING: MAX is EMPTY for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $errorFlag = true;
                    }
                    
                    if (!is_numeric($min)) {
                        $this->log('RandomNumberActionTag WARNING: MIN is not a number for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $this->projectLogging('RandomNumberActionTag WARNING: MIN is not a number for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $errorFlag = true;
                    }
                    if (!is_numeric($max)) {
                        $this->log('RandomNumberActionTag WARNING: MAX is not a number for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $this->projectLogging('RandomNumberActionTag WARNING: MAX is not a number for FIELD: ' . $field . ' ' . $details['field_annotation']);
                        $errorFlag = true;
                    }
                }
                
                if (intval($min) <= intval($max)) {
                    if (!$errorFlag) {               
                        $out = random_int(intval($min), intval($max));
                    }
                } else {
                    // we could skip this message if we already have an error flag, however, it can be telling if we arrive here as well.
                    $this->log('RandomNumberActionTag ERROR: MIN is GREATER than MAX for FIELD: ' . $field . ' ' . $details['field_annotation']);
                    $this->projectLogging('RandomNumberActionTag ERROR: MIN is GREATER than MAX for FIELD: ' . $field . ' ' . $details['field_annotation']);
                    $errorFlag = true;
                }
                
                if ($errorFlag) {
                    continue;  // gracefully skip this field as we have bad values to work with.
                }
                
                // IF we have good values we can proceed
                
                echo "
                 if ( $(\"[name = '$field']\").val() != \"undefined\" && $(\"[name = '$field']\").val() ) {
                  } else { 
                   $(\"[name = '$field']\").val(\"$out\");
                 }";
                 
                if (strlen($out) <= 0) {
                  // show and enable regardless due to error
                   echo "$(\"#" . $field . "-tr\").show();";
                   echo "$(\"[name = '$field']\").prop('disabled','false');"; // enable the element
                } else {
                   echo "$(\"[name = '$field']\").prop('disabled','true');"; // disable the element
                }
            }
        }

        echo "});
</script>";

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
        
        return \RCView::tr(
            array(),
            \RCView::td(
                array('class' => 'nowrap', 'style' => 'text-align:center;background-color:#f5f5f5;color:#912B2B;padding:7px 15px 7px 12px;font-weight:bold;border:1px solid #ccc;border-bottom:0;border-right:0;'),
                ((!$isAjax || (isset($_POST['hideBtns']) && $_POST['hideBtns'] == '1')) ? '' :
                    \RCView::button(array('class' => 'btn btn-xs btn-rcred', 'style' => '', 'onclick' => "$('#field_annotation').val(trim('" . js_escape($tag) . " '+$('#field_annotation').val())); highlightTableRowOb($(this).parentsUntil('tr').parent(),2500);"), $lang['design_171']))
            ) .
                \RCView::td(
                    array('class' => 'nowrap', 'style' => 'background-color:#f5f5f5;color:#912B2B;padding:7px;font-weight:bold;border:1px solid #ccc;border-bottom:0;border-left:0;border-right:0;'),
                    $tag
                ) .
                \RCView::td(
                    array('style' => 'font-size:12px;background-color:#f5f5f5;padding:7px;border:1px solid #ccc;border-bottom:0;border-left:0;'),
                    '<i class="fas fa-cube mr-1"></i>' . $description
                )
        );
    }


    /**
     * projectLogging - log message in project logs.
     */
    public function projectLogging($logMsg)
    {
        $logSql         = '';
        $logTable       = '';
        $logEvent       = 'OTHER';  // 'event':  DATA_EXPORT, INSERT, UPDATE, MANAGE, OTHER
        $logRecord      = '';
        $logDescription = '';       // $logDescription is 'Action' in the logging View
        
        \Logging::logEvent($logSql, $logTable, $logEvent, $logRecord, $logMsg, $logDescription);
    }
     
}
