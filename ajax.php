<?php

  require_once '../../config.php';
  require_once 'lib.php';


  $data                        = optional_param('data', 0, PARAM_TEXT); 
  $value                     = optional_param('value', 0, PARAM_INT); 

  list($id,$aid) = explode(":", $data);

  $sassessmentfiles = $DB->get_record("sassessment_studdent_answers", array("id" => $aid));
  $cm  = get_coursemodule_from_id('sassessment', $id);
  $ids = $cm->instance;

  if (!$sassessmentid = $DB->get_record("sassessment_ratings", array("aid" => $ids, "userid" => $USER->id))) {
    $add                = new stdClass;
    $add->aid           = $ids;
    $add->userid        = $USER->id;
    $add->rating        = $value;
    $add->time          = time();
    
    $DB->insert_record("sassessment_ratings", $add);
  } else {
    $DB->set_field("sassessment_ratings", "rating", $value, array("aid" => $ids, "userid" => $USER->id));
  }
  
  echo $value;
  
  $sassessmentid = $DB->get_record("sassessment_ratings", array("aid" => $ids, "userid" => $USER->id));
  $context = get_context_instance(CONTEXT_MODULE, $id);
  $sassessment = $DB->get_record("sassessment", array("id"=>$ids));
  
      
  //-----Set grade----//
      
  if (has_capability('mod/sassessment:teacher', $context)) {
      $catdata  = $DB->get_record("grade_items", array("courseid" => $sassessment->course, "iteminstance"=> $sassessment->id, "itemmodule" => 'sassessment'));
      
      $gradesdata               = new object;
      $gradesdata->itemid       = $catdata->id;
      $gradesdata->userid       = $sassessmentfiles->uid;
      $gradesdata->rawgrade     = $value;
      $gradesdata->finalgrade   = $value;
      $gradesdata->rawgrademax  = $catdata->grademax;
      $gradesdata->usermodified = $sassessmentfiles->uid;
      $gradesdata->timecreated  = time();
      $gradesdata->time         = time();
      
      
      if (!$grid = $DB->get_record("grade_grades", array("itemid" => $gradesdata->itemid, "userid" => $gradesdata->userid))) {
          $grid = $DB->insert_record("grade_grades", $gradesdata);
      } else {
          $gradesdata->id = $grid->id;
          $DB->update_record("grade_grades", $gradesdata);
      }
      
          
      //Count all grades
          /*
      $filesincourse = $DB->get_records("sassessment_studdent_answers", array("aid" => $ids, "userid" => $sassessmentfiles->userid), 'id', 'id');
          
      $filessql = '';
          
      foreach($filesincourse as $filesincourse_){
        $filessql .= $filesincourse_->id.",";
      }
          
      $filessql = substr($filessql, 0, -1);
          
      $allvoites = $DB->get_records_sql("SELECT `id`, `rating`, `userid` FROM {sassessment_ratings} WHERE `aid` IN ({$filessql})");
          
      $rate = 0;
      $c = 0;
      foreach ($allvoites as $allvoite) {
          if (has_capability('mod/sassessment:teacher', $context, $allvoite->userid) && !empty($allvoite->rating)) {
            $rate += $allvoite->rating;
            $c++;
          }
      }

      if ($c > 0)
        $rate = round ($rate/$c,1);
          
      $gradesdata->rawgrade   = $rate;
      $gradesdata->finalgrade = $rate;
          
      if(empty($gradesdata->id)) 
        $gradesdata->id = $grid;
          
      $DB->update_record("grade_grades", $gradesdata);
      */
    }
    
    