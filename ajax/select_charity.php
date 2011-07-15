<?php

require_once("../lib/controller_start.php");

$workflow_name = "select_charity";

$request = $_GET["request"];

if($request == "add"){
  $result = true;
  $controller->start_workflow($workflow_name);
  $charities = $controller->get_workflow($workflow_name);

  if(!in_array($_GET["charity"] , $charities)){
   $charities[] = $_GET["charity"];
   $controller->update_worflow($workflow_name,$charities);
  } 
  else
   $result = false;
    
  echo json_encode(array("added" => $result));
  exit;
}
else{////remove charity
  $result = true;
  $charities = $controller->get_workflow($workflow_name);

  if(in_array($_GET["charity"] , $charities)){
   $new_list = array();
   foreach($charities as $charity)
    if($charity != $_GET["charity"])
       $new_list[] = $charity;
   $controller->update_worflow($workflow_name,$new_list);
  } 
  else
   $result = false;
    
  echo json_encode(array("removed" => $result));
  exit;   
    
}    
?>