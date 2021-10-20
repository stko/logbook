<?php

require_once  'Database.php';

require_once  'login.php';

require_once("Config.php");

class RolesHandler  {
	private static $instance;

	private $db;


	/**
	* Return an instance of the Class
	* @return Database The Database instance
	*/
	public static function instance() {
		if (is_null(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->db=Database::Instance();
	}	
	

	
	public function get_role_id($wz, $user_id){
		# returns actual users role in his actual workzone
		global $actualUser;
		$values=$this->db->select("roles_assignments", [
			
		],[
			"role_id"
		],
		[
			"workzone_id[=]" =>$actualUser["wz"],
			"user_id[=]" => $actualUser["id"]
		]);
		if (empty($values)){
			return False;
		}
		$result=array();
		foreach ($values as $key => $value) {
				$result[] = $value["id"];
		}
		return $result;
	}

	public function insert_role($wz, $user_id, $rolename_id){
		global $actualUser;
		$pdoStatement=$this->db->insert("roles_assignments", [
			"workzone_id" => $wz,
			"rolename_id" => $rolename_id,
			"user_id" => $actualUser["id"],
		]);
		error_log("insert_role ");
		var_dump($this->db->error());

	}

	public function delete_role($wz, $user_id, $rolename_id){
		global $actualUser;
		$pdoStatement=$this->db->delete("roles_assignments", [
			"workzone_id" => $wz,
			"rolename_id" => $rolename_id,
			"user_id" => $actualUser["id"],
		]);
		error_log("delete_role ");

	}


}

if (!debug_backtrace()) {
	// testing
	$rh=RolesHandler::Instance();
	// add a dummy role
	echo($rh->insert_role($actualUser["id"],99,99)."\n");
	// search for that new role
	echo($rh->get_role_id($actualUser["id"],99)."\n");
	// delete again
	echo(var_dump($rh->delete_role($actualUser["id"],99,99)));

}
?>
