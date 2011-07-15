<?php

/**
 * (c)2011 The Giving Network Pty Ltd
 * mycause.com.au
 *
 * @author Amir Sadrinia
 * @date 2011-05-16
 * @package MyCause MVC Framework
 */


class Controller {
    
  private $stylesheets = array();
  private $js_libs     = array();
  private $meta_tags   = array();
  private $workflows_ref = "_managed_workflows";
  private $page_title  = "TITLE";
  private $page_template = "standard.tpl.php";
  private $savant = NULL; 
    
    
  public function __construct($savantInstance){
    $this->savant = $savantInstance;
    $this->stylesheets[] = "core.css";
	$this->js_libs[] = "jquery.min.js";
    $this->js_libs[] = "core.js";
    $this->meta_tags[] = array("name" => "keywords" , "content" => "TEST TEST");
    
    if(!isset($_SESSION[$this->workflows_ref]))
      $_SESSION[$this->workflows_ref] = array(); 
  }  
  
  public function add_style_sheet($css){
    $this->stylesheets[] = $css;
  }

  public function add_js_lib($js){
    $this->js_libs[] = $js;
  }

  public function add_meta_tag($tag){
    $this->meta_tags[] = $tag;
  }

  public function set_title($title){
    $this->page_title = $title;
  }

  public function set_template($template){
   $this->page_template = $template;
  }  
    
  public function start_workflow($name , $initialState = array()){
    $workflows = $_SESSION[$this->workflows_ref];
    if(array_key_exists($name , $workflows)) 
      return false;
    $workflows[$name] = $initialState; 
    $_SESSION[$this->workflows_ref] = $workflows;
   return true; 
  }
  
  public function end_workflow($name){
    if(array_key_exists($name , $_SESSION[$this->workflows_ref])){  
      unset($_SESSION[$this->workflows_ref][$name]);
    }  
  } 
  
  public function update_worflow($name , $values){
    $workflows = $_SESSION[$this->workflows_ref];
    if(!array_key_exists($name , $workflows)) throw new Exception("There is no live workflow named ".$name);
    $_SESSION[$this->workflows_ref][$name] = $values;
  }
  
  public function get_workflow($name){
    $workflows = $_SESSION[$this->workflows_ref];
    if(!array_key_exists($name , $workflows))
      return null;
   return $workflows[$name]; 
  } 
    
  public function get_all_workflows(){
    $workflows = $_SESSION[$this->workflows_ref];
    if(!array_key_exists($name , $workflows)) throw new Exception("There is no live workflow named ".$name);
   return $workflows; 
  }   
    
  public function display_page($target){
    $this->savant->header_options = array("css" => $this->stylesheets ,
                                          "js" => $this->js_libs ,
                                          "meta" => $this->meta_tags ,
                                          "title" => $this->page_title);
                                                                                
    $this->savant->content = $this->savant->fetch("view/".$target);
    global $db;
    $db->close();
    
    $this->savant->display("templates/".$this->page_template);
  }
  
}

?>