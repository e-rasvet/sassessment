<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of sassessment
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_sassessment
 * @copyright  2014 Igor Nikulin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace sassessment with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id    = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a     = optional_param('a', 'list', PARAM_TEXT);  
$act   = optional_param('act', NULL, PARAM_TEXT);  
$n     = optional_param('n', 0, PARAM_INT);  
$upid  = optional_param('upid', 0, PARAM_INT); 

if ($id) {
    $cm         = get_coursemodule_from_id('sassessment', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $sassessment  = $DB->get_record('sassessment', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $sassessment  = $DB->get_record('sassessment', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $sassessment->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('sassessment', $sassessment->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

//add_to_log($course->id, 'sassessment', 'view', "view.php?id={$cm->id}", $sassessment->name, $cm->id);

$frm = data_submitted();

/*
* Adding mew item
*/
if ($a == "add" && is_array($frm->useranswer)){
  if ($sassessment->grademethod == "default") 
    if (!$catdata  = $DB->get_record("grade_items", array("courseid" => $sassessment->course, "iteminstance"=> $sassessment->id, "itemmodule" => 'sassessment')))
      sassessment_grade_item_update($sassessment);

  $add          = new stdClass;
  $add->aid     = $sassessment->id;
  $add->uid     = $USER->id;
  $add->summary = $frm->summary;
  
  $text = "";
  
  foreach($frm->useranswer as $k=> $v){
    $add->{'var'.$k}    = $v;
    $text .= $v.". ";
  }
  
  $add->timecreated = time();
  
  $add->analize = json_encode(sassessment_printanalizeform($text));
  
  if (empty($frm->sid)) {
    $DB->insert_record("sassessment_studdent_answers", $add);
  } else {
    $add->id = $frm->sid;
    $DB->update_record("sassessment_studdent_answers", $add);
  }
  
  redirect("view.php?id={$id}", get_string('postsubmited', 'sassessment'));
}


/*
* Adding mew item
*/
if ($a == "add" && is_array($frm->filewav)){
  $add          = new stdClass;
  $add->aid     = $sassessment->id;
  $add->uid     = $USER->id;
  $add->summary = $frm->summary;
  
  $text = "";
  
  foreach($frm->filewav as $k=> $v){
    $add->{'file'.$k}    = $v;
  }
  
  if (is_array($frm->filetext))
    foreach($frm->filetext as $k=> $v){
      $add->{'var'.$k}     = $v;
      $text .= $v.". ";
    }
  
  $add->analize = json_encode(sassessment_printanalizeform($text));
  
  $add->timecreated = time();
  
  if (empty($frm->sid)) {
    $DB->insert_record("sassessment_studdent_answers", $add);
  } else {
    $add->id = $frm->sid;
    $DB->update_record("sassessment_studdent_answers", $add);
  }
  
  redirect("view.php?id={$id}", get_string('postsubmited', 'sassessment'));
}


/*
* Delete item
*/
if ($act == "deleteentry" && !empty($upid)) {
  if (has_capability('mod/sassessment:teacher', $context)) 
    $DB->delete_records("sassessment_studdent_answers", array("id" => $upid));
  else
    $DB->delete_records("sassessment_studdent_answers", array("id" => $upid, "userid" => $USER->id));
}


/// Print the page header

$PAGE->set_url('/mod/sassessment/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($sassessment->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/sassessment/css/style.css');
$PAGE->requires->js('/mod/sassessment/js/jquery.min.js', true);

$PAGE->requires->js('/mod/sassessment/js/flowplayer.min.js', true);
$PAGE->requires->js('/mod/sassessment/js/swfobject.js', true);

$PAGE->requires->js('/mod/sassessment/js/mediaelement-and-player.min.js', true);
$PAGE->requires->css('/mod/sassessment/css/mediaelementplayer.css');

$PAGE->requires->js('/mod/sassessment/js/video.js', true);
$PAGE->requires->css('/mod/sassessment/css/video-js.css');

//if ($sassessment->audio == 1 && $a == "add") {
if ($a == "add") {
  $PAGE->requires->js('/mod/sassessment/js/recorder.js', true);
  $PAGE->requires->js('/mod/sassessment/js/record_wav.js', true);
  $PAGE->requires->js('/mod/sassessment/js/main.js', true);
}

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('sassessment-'.$somevar);

// Output starts here
echo $OUTPUT->header();

require_once ('tabs.php');

$levelst = array("-");
for ($i=1; $i <= $sassessment->grade; $i++) {
    $levelst[] = $i;
}


if ($a == "list") {
    if ($sassessment->grademethod == "rubric" && $sassessment->humanevaluation == 1) {
      echo html_writer::start_tag('div');
      echo html_writer::link(new moodle_url('/mod/sassessment/submissions.php', array("id" => $id)), get_string("rubrics", "sassessment"));
      echo html_writer::end_tag('div');
    }

    if ($sassessment->intro) { // Conditions to show the intro can change to look for own settings or whatever
      echo $OUTPUT->box(format_module_intro('sassessment', $sassessment, $cm->id), 'generalbox mod_introbox', 'sassessmentintro');
    }

  
    $table = new html_table();
    $table->width = "100%";
    
    $table->head  = array(get_string("cell1::student", "sassessment"), get_string("cell2::answer", "sassessment"));
    $table->align = array ("left", "left");
    
    $table->size  = array ("150px", "300px");
    
    if ($sassessment->textcomparison == 1) {
      $table->head[] = get_string("cell4::textcomparison", "sassessment");
      $table->align[] = "left";
      $table->size[] = "200px";
    }
    
    if ($sassessment->textanalysis == 1) {
      $table->head[] = get_string("cell3::textanalysis", "sassessment");
      $table->align[] = "left";
      $table->size[] = "200px";
    }
    
    if ($sassessment->humanevaluation == 1 && $sassessment->grademethod == "default") {
      $table->head[] = get_string("cell5::humanevaluation", "sassessment");
      $table->align[] = "center";
      $table->size[] = "50px";
    }
    
    
    $lists = $DB->get_records ("sassessment_studdent_answers", array("aid" => $sassessment->id), 'timecreated DESC');
            
    foreach ($lists as $list) {
      if ($list->uid == $USER->id || has_capability('mod/sassessment:teacher', $context)) { // Only for Teachers and owners
        $userdata  = $DB->get_record("user", array("id" => $list->uid));
        $picture   = $OUTPUT->user_picture($userdata, array('popup' => true));
                
        //1-cell
        $o = "";
        $o .= html_writer::start_tag('div', array("style" => "text-align:left;margin:10px 0;"));
        $o .= html_writer::tag('span', $picture);
        $o .= html_writer::start_tag('span', array("style" => "margin: 8px;position: absolute;"));
        $o .= html_writer::link(new moodle_url('/user/view.php', array("id" => $userdata->id, "course" => $cm->course)), fullname($userdata));
        $o .= html_writer::end_tag('span');
        $o .= html_writer::end_tag('div');
        
        $o .= html_writer::tag('div', "", array("style"=>"clear:both"));
        
        $o .= html_writer::start_tag('div');
        $o .= $list->summary;
        $o .= html_writer::end_tag('div');
        
        
        $o .= html_writer::tag('div', html_writer::tag('small', date("F d, Y @ H:i", $list->timecreated), array("style" => "margin: 2px 0 0 10px;")));
        
       
        if ($list->uid == $USER->id || has_capability('mod/sassessment:teacher', $context)) {
          if ($list->uid == $USER->id)
            $editlink   = html_writer::link(new moodle_url('/mod/sassessment/view.php', array("id" => $id, "a" => "add", "upid" => $list->id)), get_string("editlink", "sassessment"))." ";
          else
            $editlink   = "";
            
          $deletelink = html_writer::link(new moodle_url('/mod/sassessment/view.php', array("id" => $id, "a" => "list", "act" => "deleteentry", "upid" => $list->id)), get_string("delete", "sassessment"), array("onclick"=>"return confirm('".get_string("confim", "sassessment")."')"));
           
          $o .= html_writer::tag('div', html_writer::tag('small', $editlink.$deletelink, array("style" => "margin: 2px 0 0 10px;")));
        }
        
        $cell1 = new html_table_cell($o);
        
        $o = "";
        
        $comparetext_orig    = "";
        $comparetext_current = "";
        $comparecurrent      = "";
        
        for($i=1;$i<=10;$i++){
          if (!empty($list->{'var'.$i}) || !empty($list->{'file'.$i})){
            $o .= html_writer::start_tag('div', array("style" => "margin:10px 0;"));
            
            $o .= $i.". ";
            
            if (!empty($list->{'file'.$i}))
              $o .= sassessment_player($list->{'file'.$i});
            
            
            if (!empty($list->{'var'.$i}) && $sassessment->textcomparison == 1){
              $o .= $list->{'var'.$i};
            
              $maxp    = 0;
              $maxi    = 1;
              $maxtext = "";
              if ($sampleresponses = $DB->get_records("sassessment_responses", array("aid"=>$sassessment->id, "iid"=>$i))){
                foreach ($sampleresponses as $sampleresponse) {
                  $percent = sassessment_similar_text($sampleresponse->text, $list->{'var'.$i}); 
                  if ($maxp < $percent) { $maxi = $i; $maxp = $percent; $maxtext = $sampleresponse->text; }
                }
              }

              $comparetext_orig    .= $maxtext." ";
              $comparetext_current .= $list->{'var'.$i}." ";

              $comparecurrent      .= "<div>{$i}. <b>".round($maxp)."%</b> "."{$maxtext}</div>";
            }
            
            
            $o .= html_writer::end_tag('div');
          }
        }
        
        $cell2 = new html_table_cell($o);
        
        $catdata = $DB->get_record("grade_items", array("courseid" => $cm->course, "iteminstance" => $cm->instance, "itemmodule" => 'sassessment'));
        $o = "";
        if ($grid = $DB->get_record("grade_grades", array("itemid" => $catdata->id, "userid" => $list->uid))) {
          $rateteacher = round($grid->finalgrade, 1);
          $o .= html_writer::tag('small', $rateteacher)." ";
        }
        
        $o .= html_writer::select($levelst, 'rating', '', true, array("class" => "sassessment_rate_box", "data-url" => $id.":".$list->id));
        
        $cell5 = new html_table_cell($o);
        
        $cells = array($cell1, $cell2);
        
        if ($sassessment->textcomparison == 1) {
          $percent = sassessment_similar_text($comparetext_orig, $comparetext_current); 
          //similar_text($comparetext_orig, $comparetext_current, $percent); 
          $cell3 = new html_table_cell("<div>Total: <b>".round($percent)."%</b></div>".$comparecurrent);
          $cells[] = $cell3;
        }
        
        if ($sassessment->textanalysis == 1) {
          $cell4 = new html_table_cell("<small>".sassessment_analizereport(json_decode($list->analize, true))."</small>");
          $cells[] = $cell4;
        }
        
        if ($sassessment->grademethod == "default" && $sassessment->humanevaluation == 1)
          $cells[] = $cell5;


        $row = new html_table_row($cells);
          
        $table->data[] = $row;
      }
    }

    echo html_writer::table($table);
  

  // Replace the following lines with you own code
  //echo $OUTPUT->heading('Yay! It works!');
}


if ($a == "add") {
  class sassessment_comment_form extends moodleform {
    function definition() {
      global $CFG, $USER, $DB, $sassessment, $upid;
                
      $time = time();
      $filename = str_replace(" ", "_", $USER->username)."_".date("Ymd_Hi", $time);
                
      $mform    =& $this->_form;
                
      $mform->disable_form_change_checker();
      
      //$mform->addElement('static', 'description', '', '<script type="text/javascript" src="/moodle/mod/sassessment/js/main.js?'.time().'"></script>');
      
      $mform->addElement('static', 'description', '', $sassessment->instructions);
      
      if (!empty($upid)) {
        $data = $DB->get_record("sassessment_studdent_answers", array("id" => $upid));
        $mform->addElement('hidden', 'sid', $upid);
      }
      
      
      for($i=1;$i<=10;$i++){
        if (!empty($sassessment->{'var'.$i}) || !empty($sassessment->{'file'.$i})) {
          if (!empty($data->{'var'.$i}))
            $val = $data->{'var'.$i};
          else
            $val = "";
          
          $mform->addElement('static', 'description', ''.$i.".", $sassessment->{'var'.$i});
          
          if (!empty($sassessment->{'file'.$i})){
            $mform->addElement('static', 'player', ''.$i.".", sassessment_player($sassessment->{'file'.$i}));
          }
          
          //if ($sassessment->audio == 1) {
            $o = '<div id="answerbox_'.$i.'"><div class="fitem femptylabel"><div class="fitemtitle"><div class="fstaticlabel"><label>Your answer</label></div></div><div class="felement fstatic">
            <div style="float:left;width: 220px;">
            <div id="speech-content-mic_'.$i.'" class="speech-mic" style="float:left;width: 45px;height: 45px;margin-top: -8px;"></div>
            <button onclick="startRecording(this, '.$i.');" data-url="speech-content-mic_'.$i.'">record</button>
            <button onclick="stopRecording(this, '.$i.');" data-url="speech-content-mic_'.$i.'" disabled>stop</button>
            <img src="img/ajax-loader.gif" id="loader_'.$i.'" style="margin-top: -10px;display:none;"/>
            <input type="hidden" name="filewav['.$i.']" value="" id="filewav_'.$i.'"/>';
            
            if ($sassessment->transcribe == 1)
              $o .= '
              <textarea name="filetext['.$i.']" id="answer_'.$i.'" style="width:500px;height:70px;"></textarea>
            </div>
            <div id="recording_'.$i.'" style="float:left;"></div><div style="clear:both;"></div>
            <div id="recording_text_'.$i.'" style="margin-left: 200px;"></div>
            </div></div></div>';
            else
              $o .= '</div><div id="recording_'.$i.'" style="float:left;"></div><div style="clear:both;"></div><div style="clear:both;"></div></div></div></div>';
            
            $mform->addElement('html', $o);
          /*} else {
          
            $mform->addElement('html', '<div id="answerbox_'.$i.'"><div class="fitem femptylabel"><div class="fitemtitle"><div class="fstaticlabel"><label>Your answer</label></div></div><div class="felement fstatic">
            <input type="text" name="useranswer['.$i.']" value="'.$val.'" style="width:400px;float:left;" onclick="return false;" placeholder="Click record button and speak. Click again and stop recording." id="answer_'.$i.'">
            <a href="#" onclick="recordSTT('.$i.'); return false;"><div id="speech-content-mic_'.$i.'" class="speech-mic" style="float:left;width: 45px;height: 45px;cursor: pointer;"></div></a>
            </div></div></div>');
          
          }*/
          
        }
      }
      
      $mform->addElement('textarea', 'summary', 'Comment (optional)', 'style="width:600px;height:100px;"');
      
      if (!empty($data->summary))
        $mform->setDefault('summary', $data->summary);
      
      $this->add_action_buttons(false, $submitlabel = get_string("saverecording", "voiceshadow"));
    }
  }
        
  $mform = new sassessment_comment_form('view.php?a='.$a.'&id='.$id);
        
  $mform->display();
}
// Finish the page
echo $OUTPUT->footer();
