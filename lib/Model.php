<?php

/**
 * (c)2011 The Giving Network Pty Ltd
 * mycause.com.au
 *
 * @author Amir Sadrinia
 * @date 2011-05-16
 * @package MyCause MVC Framework
 */

include_once "models.php";

class Model {
	
    private $db;
    private $model_name;
    
  /*
   * This function is used to get a new, empty Model object
   * @param $modelName reperesents the name of the model as it is defined in models.php
   * @return it returns a empty Model Object 
   */ 
   public static function get_model_for($modelName){
      global $_models;
      if(!array_key_exists($modelName , $_models))
        throw new Exception("Can't find the requested model -> ".$modelName);  
      $details = $_models[$modelName]; 
      $name = $details['table'];
      $map = $details['fields'];
      if(!$name || !$map) throw new Exception("Make sure you have defined the table details properly"); 
      
     return new Model($modelName);         
    }
    
    public static function run_query($customQuery){
     ///// method for custom query execution?  
    }
    
   /*
    * cannot be instantiated externally
    */
	protected function __construct($name){
        global $db;
        $this->db = $db; 
        $this->model_name = $name;
	}
    
        
  /*
   * This function is used to find records by one of thier fields
   * @param $field_name mapped field name as defined in models.php
   * @param $value the value to search against
   * @param $sortBy valid augument : array("id" => "ASC" , "name" => "DESC");
   * @return it returns an array containing all models which fulfill the criteria 
   */    
	public function findBy($field_name , $value , $sortBy = array() , $limit = null){
      if(!$field_name) 
        return false;
      $query = "SELECT * FROM ".$this->table_name()." WHERE ";
      $real_name = $this->get_real_name($field_name);
      if($real_name == null) throw new Exception("'$field_name' is not a valid field name!");
      else $query .= $real_name."=". (is_string($value) ? "'$value'" : $value);
      
      $query .= $this->build_order_clause($sortBy);
      
     return $this->fetch_models($query , $limit); 
	}
	
   /*
    * this function returns all records in the database for this model
    * @param $sortBy valid augument : array("id" => "ASC" , "name" => "DESC"); 
    */  
	public function findAll($sortBy = array() , $limit = null){
      $query = "SELECT * FROM ".$this->table_name().$this->build_order_clause($sortBy);
     return $this->fetch_models($query , $limit);    	
	}
    
    
  /*
   * This function allows clients to run queries with multiple AND conditions
   * @param $filters must be an string containing all conditions, for instance
   *   $test->findWhere("id = 2 AND ");
   * @return it returns a empty Model Object 
   */ 
    public function findWhere($filters , $sortBy = array() , $limit = null){
       $where = ""; 
       if($filters) $where = $this->build_where_clause($filters); 
       $query = "SELECT * FROM ".$this->table_name().$where.$this->build_order_clause($sortBy);
       
      return $this->fetch_models($query , $limit);  
    }
    
  /*
   * This function is used to get other models that are somehow related to this model (one-to-one both directions, one-to-many , many-to-one)
   * @param $modelName the name of the linked model as defined in models.php
   * @param $foreignKey is a field that links two models, it could be a field of the current model or the linked model
   *         Which is determined based on the value of $reverse argument 
   * @param $reverse if TRUE it means that $foreignKey is a field of this table refering to the ID of $modelName, if False
   *         it means $foreignKey is a field of $modelName refering to the ID of the current model.
   * @return it returns an array containing all models which fulfill the criteria 
   */ 
    public function get_linked_models($modelName , $foreignKey , $reverse){
       $model = Model::get_model_for($modelName); 
       $result = array();
       if(!$reverse){ 
        if($this->get_real_name($foreignKey) == null) throw new Exception("'$foreignKey' is not a valid field name!");
        $result = $model->findBy("id" , $this->$foreignKey);
      }
      else{
        $fields = (array)$this;
        if(!in_array("id",array_keys($fields))) 
         throw new Exception ("'ID' has not been set for the current object and consequently linked models cannot be fetched.");
        $result = $model->findBy($foreignKey , $this->id);
      }  
      if(sizeof($result) == 0) 
        return null; 
        
     return $result; 
    }
	
   /*
    *
    */ //////TODO hasn't been tested yet
    public function save_or_update(){
     $fields = array_keys($this->map_current_fields()); 
     if(in_array("id",$fields))
       $this->update();
     else   
       $this->save(); 
    }
    
   /*
    *
    */ 
	public function save(){
      $fields = $this->map_current_fields(); 
      if(sizeof($fields) == 0) return null; ////there is nothing to save
      $keys = array_keys($fields);
	  $query = "INSERT INTO ".$this->table_name()." (";
      foreach($keys as $key)
       if($fields[$key])
         $query .= "$key ,";
   
      $query = substr($query , 0 , strlen($query)-1); ///take off the last ',' 
	
      $query .= ") VALUES (";
      foreach($keys as $key){
       $temp = $fields[$key]; 
       if($temp)
         $query .= (is_string($temp) ? "'$temp'" : "$temp")." ,";
      } 
      $query = substr($query , 0 , strlen($query)-1); ///take off the last ',' 
      $query .= ")";
      
      $result = $this->db->query($query);
      if(!$result) throw new Exception($this->db->error); 
      $id = $this->db->insert_id;
      //$this->id = $id;
      
     return $id;  
    }
	
   /*
    *
    */
	public function update(){
      $fields = $this->map_current_fields(); 
      if(sizeof($fields) == 0) return null; ////there is nothing to save
      $keys = array_keys($fields);
      $props   = array_keys((array)$this);
      
      if(!in_array("id",$props)) throw new Exception("Object cannot be updated, 'id' is not available.");
      $query = "UPDATE ".$this->table_name()." SET ";
      foreach($keys as $key){
       $temp = $fields[$key];
       if($temp)
        $query .= "$key = ".(is_string($temp) ? "'$temp'" : "$temp")." ,";
	  }
      $query  = substr($query , 0 , strlen($query)-1); ///take off the last ',' 
      $query .= "WHERE ".$this->get_real_name("id")."=".$this->id; 
      
      $result = $this->db->query($query);
      if(!$result) throw new Exception($this->db->error); 
      
     return $this;  
      	
    }
	
  /*
   *
   */ 
	public function delete($filters){
	  $where = "";
      if($filters) $where = $this->build_where_clause($filters); 
	  return $this->db->query("DELETE FROM ".$this->table_name().$where);	
	}
    
  /*
   * Delete a record whose id equals current object's ID 
   */ 
	public function _delete(){
     $where = $this->build_where_clause(array("id" => $this->id)); 
 	 if(!$this->db->query("DELETE FROM ".$this->table_name().$where))
       return false;
     $this->id = null;
     return $this;  	
	}
    
    /*
   * This function is used to to map a mysql result set to the equivalent model Objects. 
   * @param $result the actual resultset to be mapped to an array of model objects
   *
   */        
    public function map_result_set($result){
      $objects = array();
      while(($row = $result->fetch_assoc())){
        $temp = new Model($this->model_name);
        foreach($row as $field => $value){
          $field = $this->get_mapped_name($field);
          if($field != null) 
            $temp->$field = $value;
        }
        $objects[] = $temp;  
      }
     return $objects;    
    }    
    
   /*
    *
    */ 
    private function map_current_fields(){
        
      $props   = (array)$this;
      $fields  = array();
      foreach($props as $field => $s){
        if(strpos($field , "Model")) continue; ///predefined fields don't count!!
        $key = $this->get_real_name($field);
        $value = $this->$field;
        if($key == null) throw new Exception("'$field' is not a valid field name!");
        $fields[$key] = $value; 
      } 
     return $fields; 
    }
    
    
    /*
     * valid augument : array("id" => "ASC" , "name" => "DESC");
     *
     */
    private function build_order_clause($filters){
      $clause = "";
      if(sizeof($filters) > 0){
        $clause = " ORDER BY";
        foreach($filters as $key => $value){
         $temp = $this->get_real_name($key);
         if($temp == null) throw new Exception("'$key' is not a valid field name!");
         $clause .= " ".$temp." $value,";
        }
        $clause  = substr($clause , 0 , strlen($clause)-1);  
       } 
      return $clause;    
    }
    
   /*
    *
    */  
    private function build_where_clause($filters){
       $clause = "";
       if(sizeof($filters) > 0){
        $clause = " WHERE";
        foreach($filters as $key => $value){
         $temp = $this->get_real_name($key);
         if($temp == null) throw new Exception("'$key' is not a valid field name!");
         $clause .= " ".$temp."=".(is_string($value) && $key != "id" ? "'$value'" : $value)." AND";
        }
        $clause  = substr($clause , 0 , strlen($clause)-3); ////remove that last 'AND' 
       } 
      return $clause; 
    } 
    
    
   /*
    *
    */ 
    private function fetch_models($query , $limit = null){
      $result = $this->db->query($query.($limit == null ? "" : " LIMIT {$limit['offset']},{$limit['count']}"));
      if(!$result) throw new Exception($this->db->error." <----> ".$this->model_name." MODEL >> ".$query);
      else {
        $models = $this->map_result_set($result);
        $result->free();
       return $models; 
      } 	  
    }
    
   /*
    *
    */  
    private function table_name(){
      global $_models;  
      return $_models[$this->model_name]['table'];   
    }
    
   /*
    *
    */  
    private function fields_map(){
       global $_models; 
       return $_models[$this->model_name]['fields']; 
    }
    
   /*
    *
    */  
    private function get_real_name($fieldName , $guess = true){
       $map = $this->fields_map(); 
       $realName = null;
       if(isset($map[$fieldName])) 
        $realName = $map[$fieldName]; 
       else if($guess === true)
        $realName = $fieldName;  
        
      return $realName;  
    }
    
   /*
    *
    */  
    private function get_mapped_name($fieldName , $guess = true){
       $map = $this->fields_map();  
       $mappedName = null;
       $temp = array_search($fieldName , $map);
       if($temp) 
        $mappedName = $temp;
       else if($guess === true)
        $mappedName = $fieldName; 
      
     return $mappedName;   
    }
	 		
}

?>