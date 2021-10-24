<?php

require_once  'Database.php';

require_once  'WorkZones.php';

require_once  'login.php';


class TasksHandler  {
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

		

	public function createTask($wzID,$task,$title,$content){
		global $actualUser;
	}

	public function countRequestsPerWorkzone(){
		$data = $this->db->query(
			"SELECT <workzone.objname> , COUNT(<part_requests.id>)  FROM <workzone> INNER JOIN  <logbook_parts> ON  <workzone.id> = <parts.workzone_id> INNER JOIN  <Logbook_part_requests> ON  <part_requests.part_id> = <parts.id> WHERE <part_requests.request_state> != :state GROUP BY <workzone.objname>" , [
				":state" => 1
			]
		);

		if ($data===false){
			var_dump($this->db->error());
			die('{"errorcode":1, "error": "DB Error 1"}');
		}else{
			$res=$data->fetchAll();
			$data=array();
			foreach($res as $wzResult){
				$data[]=["objname" => $wzResult["objname"] , "count" => $wzResult[1]];
			}
			return $data;
		}
	}
	
	public function getTaskOwnerInfo($task_id){
		$data = $this->db->get("part_tasks", [
			"[>]users" => ["user_id" => "id"]
		],
		[
			"users.firstname",
			"users.lastname",
			"users.id"
		], [
			"part_tasks.id" => $task_id
		]);
		return $data;
	}

	public function getRequestOwnerInfo($request_id){
		$data = $this->db->get("part_requests", [
			"[>]users" => ["user_id" => "id"]
		],
		[
			"users.firstname",
			"users.lastname",
			"users.id"
		], [
			"part_requests.id" => $request_id
		]);
		return $data;
	}

	public function acceptPredecessor($edgeID){
		$preTaskState = $this->db->select("edgelist", [
			"[>]tasklist" => ["fromtaskid" => "id"],
		],
		[
			"tasklist.state(taskstate)",
			"edgelist.totaskid",
			"edgelist.state",
			"edgelist.workzone_id",
		],
		[
			"edgelist.id[=]" => $edgeID
		]);
		$taskState=$preTaskState[0]["taskstate"];
		$edgeState=$preTaskState[0]["state"];
		$newStateTask=$preTaskState[0]["totaskid"];
		$workzone_id=$preTaskState[0]["workzone_id"];
		$data = $this->db->update("edgelist", [
			"state" => $taskState
		], [
			"id" => $edgeID
		]);
		$this->updateModelState($workzone_id,$newStateTask);
		return true;
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
	$th=TasksHandler::Instance();
	echo("get taskOwnerInfo");
	echo(var_dump($th->getTaskOwnerInfo(1))."\n");
	echo("get getRequestOwnerInfo");
	echo(var_dump($th->getRequestOwnerInfo(1))."\n");
	echo("get countRequestsPerWorkzone");
	echo(var_dump($th->countRequestsPerWorkzone(1))."\n");
	

}
?>
