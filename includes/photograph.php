<?php
require_once (LIB_PATH.DS."database.php");

class Photograph extends DatabaseObject{

	protected static $table_name='photographs';
	protected static $db_fields = array('id', 'filename', 'type', 'size', 'caption');
	public $id; 
	public $filename; 
	public $type; 
	public $size; 
	public $caption; 

	private $temp_path;
	protected $upload_dir = "images";
	public $errors=array();

	protected $upload_errors = array(
		UPLOAD_ERR_OK          =>"No errors.",
		UPLOAD_ERR_INI_SIZE    =>"Larger than upload_max_filesize.",
		UPLOAD_ERR_FORM_SIZE   =>"Largeer than form MAX_FILE_SIZE.",
		UPLOAD_ERR_PARTIAL     =>"Partial upload_errors.",
		UPLOAD_ERR_NO_FILE     =>"No file.",
		UPLOAD_ERR_NO_TMP_DIR  =>"No temporary directory.",
		UPLOAD_ERR_CANT_WRITE  =>"Can't write to disk.",
		UPLOAD_ERR_EXTENSION   =>"File upload stopped by extension."
	);

	//pass in $_FILE(['uploaded_file']) as an argument
	public function attach_file($file){
		//perform error checking on the form param
		if(!$file || empty($file) || !is_array($file)){
		//error:nothing uploaded or wrong argument usage
			$this->errors[] = "No file was uploaded";
			return false;
		} elseif($file['error'] !=0){
			//error:report what php says went wrong
			$this->errors[] = $this->uloaded_errors[$file['error']];
			return false;
		} else {	
			//set object attr to the form param
			$this->temp_path = $file['tmp_name'];
			$this->filename  = basename($file['name']);
			$this->type      = $file['type'];
			$this->size      = $file['size'];
			//dont worry about saving anything to the database yet.
		}
		return true;
	}

	public function save(){
		//a new record wont have an id yet
		if(isset($this->id)){
			//really just to update the caption
			$this->update();
		} else {
			//make sure there are no errors

			//cant save if there are pre-existing errors
			if(!empty($this->errors)){ return false; }

			//make sure the caption is not too long for the DB
			if(strlen($this->caption) > 255){
				$this->errors[] = "The caption can only be 255 characters long";
				return false;
			}

			//determine the target_path
			$target_path = SITE_ROOT . DS . 'public'.DS. $this->upload_dir .DS. $this->filename;

			//make sure a file doesnt already exist in the target location
			if(file_exists($target_path)){
				$this->errors[] = "The file {$this->filename} already exists.";
				return false;
			}
			
			//attempt to move the file
			if(move_uploaded_file($this->temp_path, $target_path)){
				//success
				//save a corresponding entry to the database
				if($this->create()){
					//we are done w temp_path, the file isn't there anymore
					unset($this->temp_path);
					return true;
				}
			} else {
				//file was not moved
				$this->errors[]="The file upload failed, possibly due to incorrect permissions on the upload folder";
				return false;
			}
			
		}
	}

	public function destroy(){
		//first remove the database entry
		if($this->delete()){
			//then remove the file
			$target_path = SITE_ROOT.DS.'public'.DS.$this->image_path();
			return unlink($target_path) ? true : false;
		} else {
			//database delete failed
			return false;
		}
		

	}

	public function image_path(){
		return $this->upload_dir.DS.$this->filename;
	}

	public function size_as_text(){
		if($this->size < 1024){
			return "{$this->size} bytes";
		} elseif($this->size <1048576) {
			$size_kb = round($this->size/1024);
			return "{$size_kb} KB";
		} else {
			$size_mb = round($this->size/1048576,1);//1 uma casa decimal
			return "{$size_mb} MB";
		}
	}

	public function comments(){
		return Comment::find_comments_on($this->id);
	}

	//common database methods

	public static function find_all(){
		//static we dont neet to instantiate the class, we do with ::
		return static::find_by_sql("SELECT * FROM ".static::$table_name);
	}

	public static function find_by_id($id=0){
		global $database;
		$result_array = static::find_by_sql("SELECT * FROM ".static::$table_name." WHERE id =".$database->escape_value($id)." LIMIT 1");
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
		$object_vars = $this->attributes();//$this instance
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

	//replaced with a custom save()
	// public function save(){
	// 	//a new recoord wont have an id yet
	// 	//use save() instead of directly using create or update
	// 	return isset($this->id) ? $this->update() : $this->create();
	// }

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


