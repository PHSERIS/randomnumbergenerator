<?php
/*
 * Â© 2021 Mass General Brigham (formerly Partners HealthCare). All Rights Reserved.
 * @original author David Clark
 * modified for 3 letter code function by Devin Foley
 * 2025 McMaster University
 * Heavily influenced  by vork of Luke Stevcens
 * 
 * This is an external module that hides the submit button on surveys
 * 
 * The trigger is similar to REDCap "action tags".
 * @RANDOMCODE  generates a random alpha or alphanumeric code up to 12 characters long
 * USAGE:
 *	@RANDOMCODE
 *  @RANDOMCODE()
 *  @RANDOMCODE(LENGTH,TYPE)
 */
 namespace MGB\RandomAlphaNumActionTagRepo;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


class RandomAlphaNumActionTagRepo extends AbstractExternalModule
{
    protected $is_survey = 0;

    protected static $Tags = array(
    '@RANDOMCODE' => array('description' => 'Generates a unique random code between 1-12 characters long. Format: @RANDOMCODE(LENGTH,TYPE) â€” TYPE is alpha or alnum. Default is 3,alpha')
	);

    public function redcap_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $repeat_instance = 1)
    {
        $taggedFields = $this->includeTagFunctions($instrument, $project_id, $record, $event_id);
    }

    public function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    {
        $this->is_survey = 1;
        $this->includeTagFunctions($instrument, $project_id, $record, $event_id);
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
            $lastActionTagDesc = end(\Form::getActionTags()) ;

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


    protected function includeTagFunctions($instrument, $project_id, $record, $event_id)
    {
        $taggedFields = array();
        $tags = static::$Tags;
        $instrumentFields = \REDCap::getDataDictionary('array', false, true, $instrument);

        $pattern = '/(' . implode('|', array_keys($tags)) . ')/';        
        foreach ($instrumentFields as $fieldName => $fieldDetails) {
            $matches = array();
            if (preg_match($pattern, $fieldDetails['field_annotation'], $matches)) {
                      $taggedFields[$fieldName] = $fieldDetails;
            }
        }
        if (count($taggedFields) > 0) {
            $this->includeJS($taggedFields, $project_id, $record, $event_id);
        }
        return $taggedFields;
    }
	
protected function generateUniqueCode($field, $project_id, $record, $event_id, $length = 3, $type = 'alpha')
{
    // Check if this record already has a value
    $existingData = \REDCap::getData([
        'project_id' => $project_id,
        'records' => [$record],
        'fields' => [$field],
        'return_format' => 'array'
    ]);

    if (!empty($existingData[$record][$event_id][$field])) {
        return $existingData[$record][$event_id][$field]; // Use this record's existing code
    }

    // Get used codes globally for uniqueness
    $usedCodes = [];
    $allData = \REDCap::getData([
        'project_id' => $project_id,
        'fields' => [$field],
        'return_format' => 'array'
    ]);
    foreach ($allData as $record => $recordData) {
        foreach ($recordData as $eventData) {
            if (!empty($eventData[$field])) {
                $usedCodes[] = strtoupper($eventData[$field]);
            }
        }
    }

							   
    $charset = ($type === 'alnum') ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxAttempts = 100;
    $attempts = 0;
	if ($length <= 0) {$length = 1;}
	if ($length > 12) {$length = 12;}


    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $charset[random_int(0, strlen($charset) - 1)];
        }
        $attempts++;
    } while (in_array($code, $usedCodes) && $attempts < $maxAttempts);
	
	if ($attempts >= $maxAttempts) {
	error_log("REDCap Module: Failed to generate unique code for field $field after $attempts attempts.");
	}
	$code = strtoupper($code);
    return $code;
}
		 
		public function includeJS($taggedFields, $project_id, $record_id, $event_id)
			{

				echo "<script type='text/javascript'>
		$(document).ready(function() { 
		";

        foreach ($taggedFields as $field => $details) {
            // TODO - form validation
            $params = \Form::getValueInParenthesesActionTag($details['field_annotation'], '@RANDOMCODE');
			$parts = array_map('trim', explode(',', $params));

			$length = isset($parts[0]) && is_numeric($parts[0]) ? intval($parts[0]) : 3;
			$type = isset($parts[1]) && strtolower($parts[1]) === 'alnum' ? 'alnum' : 'alpha';

			$out = htmlspecialchars($this->generateUniqueCode($field, $project_id, $record_id, $event_id, $length, $type));

		echo "
			if ($('[name=\"$field\"]').val()) {
				// Do nothing if value exists
			} else {
				$('[name=\"$field\"]').val(\"$out\");
			}
		";
            if (strlen($out) <= 0) {
              // show and enable regardless due to error
               echo "$(\"#" . $field . "-tr\").show();";
               echo "$(\"[name = '$field']\").prop('disabled','false');"; // enable the element
            } else {
               echo "$(\"[name = '$field']\").prop('disabled','true');"; // disable the element
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
 
   
}