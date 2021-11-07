<?php

require_once  'Database.php';

require_once  'login.php';


class UserHandler  {
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

	public function addUser($username, $firstname,$lastname){
		/*
		adds a user or sets him back to active, in case he's set to dead
		*/
		$username=strtolower($username);
		$hasuser = $this->db->has("users", [
			"username" => $username
		]);
		if ($hasuser){
			$data = $this->db->update("users", [
				"username" => $username
			],[
				"firstname"=> $firstame,
				"lastname" => $lastname,
				"state" => 1 // this reactivates a user
			]);
		}else{
			$data = $this->db->insert("users", [
				"username" => $username,
				"firstname"=> $firstname,
				"lastname" => $lastname,
				"state" => 1 // active user
			]);

		}
	}
	
	public function deactivateUser($username){
		/*
		sets a users state to inactive, but does not delete him,
		as he might be referenced in the history
		*/
		$username=strtolower($username);
		$data = $this->db->update("users", [
			"state" => 0 // this deactivates a user
		],[
			"username" => $username
		]);
	}
	

	public function doRequest($post){
		ob_start();
		var_dump($post);
		$result = ob_get_clean();
		error_log($result);
		$action = $post['action'];
		if ($action) {
			if ($action=='create_request'){
				error_log("toggle ignore Predecessor task");
				if (
					!isset($post['part_id']) ||
					!isset($post['request_template_id']) ||
					!isset($post['objvalue'])
				){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$part_id = $post['part_id'];
				$request_template_id = $post['request_template_id'];
				$objvalue = $post['objvalue'];
				die('{"errorcode":0, "data": '.json_encode($this->create_request($part_id, $request_template_id, $objvalue)).'}');
			}
		}else{
			die('{"errorcode":1, "error": "Variable Error"}');
		}
	}
}

if (!debug_backtrace()) {
	$uh=UserHandler::Instance();
	echo("addUser");
	echo(var_dump($uh->addUser("klamu","Klaus", "Mustermann"))."\n");
	echo("deactivateUser");
	echo(var_dump($uh->deactivateUser("klamu"))."\n");
	

}
?>
