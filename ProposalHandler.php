<?php

require_once  'Database.php';

require_once  'login.php';

require_once("Config.php");

class ProposalHandler  {
/*
	handles the possible requests, the possible tasks and supports the creation of a new request

*/

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
	

	public function get_request_templates($query){
		$values = $this->db->select("request_templates", [
			"objname"
			], [

		]);
		$result=array();
			foreach ($values as $key => $value) {
				if (stripos($value["objname"], $query) !== false  || !($query)) {
					if (! in_array($value["objname"],$result)){
						$result[] = $value["objname"];
					}
				}
			}
			sort($result);
		return $result;	
	}

	public function get_task_proposals($request_template_id){
		$values = $this->db->select("task_proposals", [
			"task_template_id"
			], [
				"request_template_id[=]" =>$request_template_id
	
		]);
		$result=array();
			foreach ($values as $key => $value) {
				$result[] = $value["task_template_id"];
			}
		return $result;
	}

	


	bis hier war ich gekommen....
	
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
	$ph=ProposalHandler::Instance();

	echo("get all requests");
	echo(var_dump($ph->get_request_templates(""))."\n");
	echo("get all requests containing a string");
	echo(var_dump($ph->get_request_templates("teil"))."\n");
	echo("get all task proposal for a request template");
	echo(var_dump($ph->get_task_proposals(1)));

}
?>
