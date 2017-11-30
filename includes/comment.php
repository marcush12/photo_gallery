<?php
require_once(LIB_PATH.DS.'database.php');
class Comment extends DatabaseObject{
	//attributes
	protected static $table_name='comments';
	protected static $db_fields=array('id', 'photograph_id', 'created', 'author', 'body');
	public $id;
	public $photograph_id;
	public $created;
	public $author;
	public $body;

	//new is a reserved word so we use make or build
	public static function make($photo_id, $author="Anonymous", $body=''){
		if(!empty($photo_id) && !empty($author) && !empty($body)){
			$comment = new Comment();
			$comment->photograph_id =  (int)$photo_id;
			$comment->created =  strftime("%Y-%m-%d %H:%M:%S", time());
			$comment->author =  $author;
			$comment->body =  $body;
			return $comment;
		} else {
			return false;
		}
	}

	public static function find_comments_on($photo_id=0){
		global $database;
		$sql  = "SELECT * FROM " . self::$table_name;
		$sql .= " WHERE photograph_id=" .$database->escape_value($photo_id);
		$sql .= " ORDER BY created ASC";
		return self::find_by_sql($sql);
	}

	public function try_to_send_notification(){
		$mail = new PHPMailer();

		$mail->IsSMTP();
		$mail->Host = "gmail.com";
		$mail->Port = 25;
		$mail->SMTPAuth =false;
		$mail->Username = "marcos012santos";
		$mail->Password = "valeria1221";

		$mail->FromName = "Photo Gallery";
		$mail->From     = "marcos012santos@gmail.com";
		$mail->AddAddress("marcos012santos@gmail.com", "Photo Gallery Admin");
		$mail->Subject  = "New Photo Gallery Comment";
		$created = datetime_to_text($this->created);
		$mail->Body     =<<<EMAILBODY
A new comment has been received in the Photo Gallery.
 At {$this->created}, {$this->author} wrote:
{$this->body}		
EMAILBODY;

		$result = $mail->Send();
		return $result;
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
