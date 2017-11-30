<?php
require_once (LIB_PATH.DS."database.php");

class User extends DatabaseObject{
	//attributes: every column in database has its attibute
	protected static $table_name='users';
	protected static $db_fields = array('id', 'username', 'password', 'first_name', 'last_name');
	public $id; 
	public $username; 
	public $password; 
	public $first_name; 
	public $last_name; 

	public function full_name(){
		if(isset($this->first_name) && isset($this->last_name)){
			return $this->first_name . " " . $this->last_name;
		} else {
			return "";
		}
	}

	public static function authenticate($username='', $password=''){
		global $database;
		$username = $database->escape_value($username);
		$password = $database->escape_value($password);
		$sql  = "SELECT * FROM users ";
		$sql .= "WHERE username = '{$username}' ";
		$sql .= "AND password = '{$password}' ";
		$sql .= "LIMIT 1";
		$result_array = self::find_by_sql($sql);
		return !empty($result_array) ? array_shift($result_array) : false;
	}
	
	//common database methods

	public static function find_all(){
		//static we dont neet to instantiate the class, we do with ::
		return static::find_by_sql("SELECT * FROM ".static::$table_name);
	}

	public static function find_by_id($id=0){
		global $database;
		$result_array = static::find_by_sql("SELECT * FROM ".static::$table_name." WHERE id = {$id} LIMIT 1");
		return !empty($result_array) ? array_shift($result_array) : false;
	}

	public static function find_by_sql($sql=""){
		global $database;
		$result_set = $database->query($sql);
		$object_array = array();
		while($row = $database->fetch_array($result_set)){
			$object_array[] = static::instantiate($row);
		}
		return $object_array;
	}

	public static function count_all(){
		global $database;
		$sql = "SELECT COUNT(*) FROM ".self::$table_name;
		$result_set = $database->query($sql);
		$row = $database->fetch_array($result_set);
		return array_shift($row);
	}

	private static function instantiate($record){
		$object = new static;//static instead of User()
		// $object->id 		= $record['id'];
		// $object->username 	= $record['username'];
		// $object->password 	= $record['password'];
		// $object->first_name = $record['first_name'];
		// $object->last_name 	= $record['last_name'];
		//suppose we have lots of attr is better follow below in a 
		//more dynamic, short-form approach:
		foreach ($record as $attribute => $value) {
			if($object->has_attribute($attribute)){
				$object->$attribute = $value;
			}
		}
		return $object;
	}

	private function has_attribute($attribute){
		//get_object_vars returns as assoc array with all attr - incl private ones - as the keys and their current values as the value
		$object_vars = get_object_vars($this);//$this instance
		//we dont care about the value, we just wanto to know id the key exists - will retur true or false
		return array_key_exists($attribute, $object_vars);
	}
	
	protected function attributes(){
		//return an array of attr keys and their values
		$attributes = array();
		foreach (self::$db_fields as $field) {
			if(property_exists($this, $field)){
				$attributes[$field] = $this->$field;
			}
			
		}
		return $attributes;

	}

	protected function sanitized_attributes(){
		global $database;
		$clean_attributes = array();
		//sanitize the values before submitting
		//note: does not lter the actual value of each attribute
		foreach ($this->attributes() as $key => $value) {
			$clean_attributes[$key] = $database->escape_value($value);
		}
		return $clean_attributes;
	}

	public function save(){
		//a new recoord wont have an id yet
		//use save() instead of directly using create or update
		return isset($this->id) ? $this->update() : $this->create();
	}

	public function create(){
		global $database;

		$attributes = $this->sanitized_attributes();

		$sql  = "INSERT INTO ".self::$table_name." (";
		$sql .= join(", ", array_keys($attributes));
		$sql .= ") VALUES ('";
		$sql .= join("', '", array_values($attributes));
		$sql .= "')";
		if($database->query($sql)){
			$this->id = $database->insert_id();//to get user id
			return true;
		} else {
			return false;
		}
	}

	public function update(){
		global $database;

		$attributes = $this->sanitized_attributes();
		$attributes_pairs = array();
		foreach ($attributes as $key => $value) {
			$attributes_pairs[] = "{$key}='{$value}'";
		}
		$sql  = "UPDATE ".self::$table_name." SET ";
		$sql .= join(", ", $attributes_pairs);
		$sql .= " WHERE id=". $database->escape_value($this->id);
		$database->query($sql);
		return ($database->affected_rows($sql) == 1) ? true : false;
		
	}

	public function delete(){
		global $database;
		$sql  = "DELETE FROM ".self::$table_name;
		$sql .= " WHERE id=". $database->escape_value($this->id);
		$sql .= " LIMIT 1";
		$database->query($sql);
		return ($database->affected_rows($sql) == 1) ? true : false;
	}
}