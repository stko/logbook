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
		// returns a list of template names matches to string $query
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
		// list of all task proposals assigned to a request template
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

	function create_request($part_id, $request_template_id, $objvalue){
		// creates a real request
		$pdoStatement=$this->db->insert("part_requests", [
			"part_id" => $part_id,
			"start_time" => time(),
			"request_template_id" => $request_template_id,
			"objvalue" => $objvalue
		]);
		$request_id = $this->db->id();
		$task_proposals = $this->get_task_proposals($request_template_id);
		$proposals_array=array();
		$result=array();
		foreach($task_proposals as $task_proposal){
			$result[] = [
				"request_id" => $request_id,
				"task_template_id" => $task_proposal,
				"task_execution_state" => 0
			];
		}
		if ($result){
			$pdoStatement=$this->db->insert("request_tasks", 
			$result
			);
		}
	}

	function update_request_template($request_template_id, $objname, $scheme){
		/*
		 todo : must create a new entry and set the old one to historic,
		 if it was used anywhere and so it cant be changed without potentionally
		 destroy the old object
		 */
		if ($this->db->has("request_templates",[
				"id" => $request_template_id
			])){
				$this->db->update("request_templates",[
					"objname" => $objname ,
				"scheme" => $scheme
			],[
				"id[=]" => $request_template_id
			]);
		} else {
			$this->db->insert("request_templates",[
				"objname" => $objname ,
				"scheme" => $scheme ,
				"id" => $request_template_id
			]);
		}
	}

	function update_task_template($task_template_id, $objname, $scheme){
		if ($this->db->has("task_templates",[
			"id" => $task_template_id
		])){
			$this->db->update("task_templates",[
				"objname" => $objname ,
				"scheme" => $scheme
			],[
				"id[=]" => $task_template_id
			]);
		} else {
			$this->db->insert("task_templates",[
				"objname" => $objname ,
				"scheme" => $scheme ,
				"id" => $task_template_id
			]);
		}
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
	// testing
	$ph=ProposalHandler::Instance();

	echo("get all requests");
	echo(var_dump($ph->get_request_templates(""))."\n");
	echo("get all requests containing a string");
	echo(var_dump($ph->get_request_templates("ein"))."\n");
	echo("get_task_proposals(3) - get all task proposal for a request template");
	echo(var_dump($ph->get_task_proposals(3)));
	echo("create a request");
	echo(var_dump($ph->create_request(1,1,"Bla")));
	echo("update the  request template");
	echo(var_dump($ph->update_request_template(1,"Bla2","{}")));
	echo("create a new request template");
	echo(var_dump($ph->create_request(2,"Bla3","{}")));

}
?>
