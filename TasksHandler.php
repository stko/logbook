<?php

require_once  'Database.php';

require_once  'WorkZones.php';

require_once  'TaskTemplates.php';

require_once  'login.php';

require_once("Config.php");

class TasksHandler  {
	private static $instance;

	private $db;
	private $wz;
	private $jt;

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
		$this->wz=WorkZones::Instance();
		$this->jt=TaskTemplates::Instance(Config::taskPath);
	}	
	
	public function getTaskNameID($task) {
		$values = $this->db->select("tasknames", [
			"id",
			"name"
			], [
			"name[=]" => $task
		]);
		if (empty($values)){
			return False;
		}
		return $values[0]["id"];
	}
	

	public function getTaskSchema($taskID) {
		$values = $this->db->select("tasklist", [
			"content",
			"workzone_id"
			], [
			"id[=]" => $taskID
		]);
		if (empty($values)){
			return "{}";
		}else{

			$this->dumpModel($this->getModel($values[0]["workzone_id"]));


			return json_decode($values[0]["content"]);
		}
	}
	

	
	public function createTaskName($task){
		$id=$this->getTaskNameID($task);
		if ($id===false){
			$pdoStatement=$this->db->insert("tasknames", [
				"name" => $task
			]);
			return $this->db->id();
		}else{
			return $id;
		}
	}

	public function createTask($wzID,$task,$title,$content){
		global $actualUser;
		$taskID=$this->createTaskName($task);
		$values = $this->db->select("tasklist", [
			"id",
			], [
			"workzone_id[=]" => $wzID,
			"tasknameid[=]" => $taskID
		]);
		if (empty($values)){
			$json=json_decode($content);
			$data= [
				"workzone_id" => $wzID,
				"tasknameid" => $taskID,
				"ownerid" => $actualUser["id"],
				"title" => $title,
				"content" => $content,
				"validated" => 0,
				"startdate" => time(),
				"enddate" => time()+3600*24*$json->duration,
				"plannedenddate" => time()+3600*24*$json->duration,
				"fulfillrate" => 0,
				"duration" => $json->duration,
				"ismilestone" => $json->isMileStone ? 1 : 0 ,
				"state" => 0
			];
			ob_start();
			var_dump($data);
			$result = ob_get_clean();
			$this->db->insert("tasklist", $data);
			return $this->db->id();
		}else{
			return $values[0]["id"];
		}
	}

	
	public function createOrModifyEdge($wzID,$fromTaskID,$toTaskID,$state){
		$values = $this->db->update("edgelist", [
				"state" => $state
			], [
			"workzone_id[=]" => $wzID,
			"fromtaskid[=]" => $fromTaskID,
			"toTaskID[=]" => $toTaskID
		]);
		if ($values->rowCount()==0){
			$this->db->insert("edgelist", [
				"workzone_id" => $wzID,
				"fromtaskid" => $fromTaskID,
				"totaskid" => $toTaskID,
				"state" => $state
			]);
			return true;
		}else{
			return false;
		}
	}

	public function setTaskValues($data,$user_id){
		if (!isset($data["taskID"]) 
		or !isset($data["predecessorState"])
		or !isset($data["validated"])
		or !isset($data["comment"])
		or !isset($data["content"])
		or !isset($data["state"])
		){
			die('{"errorcode":1, "error": "Variable Error"}');
		}
		$values = $this->db->select("tasklist", [
			"workzone_id",
			"state"
			], [
			"id[=]" => $data["taskID"]
		]);
		$wzID=$values[0]["workzone_id"];
		$oldState=$values[0]["state"];
		$newState=$data["state"];
		$newEdgeState=$data["state"];
		if ($data["validated"]){
			$newState=1;
		}
		if (($oldState==$newState and $oldState==1) or $oldState!=$newState){
			if ($oldState==1){// was finished, but isn't anymore
				if ($oldState==$newState){ //it's an update, which triggers a rework
				$newEdgeState=3; //reworked
				}else{ // even more worse: Task is not valid anymore
				$newEdgeState=5; //faulty
				$newState=5; // faulty
				}
			}
			$values = $this->db->update("edgelist", [
					"state" => $newEdgeState
				], [
				"workzone_id[=]" => $wzID,
				"fromtaskid[=]" => $data["taskID"]
			]);

			
			$values = $this->db->update("tasklist", [
					"state" => $newState
				], [
				"id[=]" => $data["taskID"]
			]);

		}

		$values = $this->db->update("tasklist", [
			"startdate" => $data["content"]["endDate"]-$data["content"]["duration"]*24*3600,
			//"enddate" => $data["content"]["endDate"],
			"plannedenddate" => $data["content"]["endDate"],
			"duration" => $data["content"]["duration"],
			//"fulfillrate" => $data["content"]["fulfillrate"],
			"fulfillrate" => 50, //dummy, until the GUI provides also the fulfillrate
			"ismilestone" => $data["content"]["isMileStone"] ? 1 : 0 
			], [
			"id[=]" => $data["taskID"]
		]);

	
		$userInfo=$this->getTaskOwnerInfo($data["taskID"]);
		if ($userInfo!=NULL) {
			$owner=$userInfo["id"];
			ob_start();
			var_dump($userInfo);
			$result = ob_get_clean();
		} else {
			$owner=$user_id; 
		}
		

		$pdoStatement=$this->db->insert("changelog", [
			"taskid" => $data["taskID"],
			"timestamp" => time(),
			"changetype" => 0,
			"user_id" => $user_id,
			"taskowner" => $owner,
			"predecessorState" => $data["predecessorState"],
			"validated" => $data["validated"],
			"comment" => json_encode($data["comment"]),
			"content" => json_encode($data["content"]),
			"state" => $data["state"]
		]);
	}

	
	public function getWorkZoneOverview($wzName){
		$data = $this->db->query(
			"SELECT <workzone.name> , COUNT(<tasklist.id>)  FROM <workzone> INNER JOIN  <logbook_tasklist> ON  <workzone.id> = <tasklist.workzone_id> WHERE (lower(<workzone.name>) LIKE lower( :workzonename ) ) AND <tasklist.state> != :state GROUP BY <workzone.name>" , [
				":workzonename" => "%".$wzName."%",
				":state" => 1
			]
		);

		if ($data===false){
			die('{"errorcode":1, "error": "DB Error 1"}');
		}else{
			$res=$data->fetchAll();
			$data=array();
			foreach($res as $wzResult){
				$data[]=["name" => $wzResult["name"] , "count" => $wzResult[1]];
			}
			return $data;
		}
	}
	
	public function getTaskOwnerInfo($taskID){
		$data = $this->db->get("changelog", [
			"[>]users" => ["taskowner" => "id"]
		],
		[
			"users.firstname",
			"users.lastname",
			"users.id"
		], [
			"taskid" => $taskID,
			"ORDER" => ["timestamp" => "DESC"],
		]);
		return $data;
	}
	
	public function writeChangeLog($taskID,$text,$taskOwner){
		global $actualUser;
		$pdoStatement=$this->db->insert("changelog", [
			"taskid" => $taskID,
			"timestamp" => time(),
			"changetype" => 1,
			"user_id" => $actualUser["id"],
			"taskowner" => $taskOwner,
			"predecessorState" => 0,
			"validated" => 0,
			"comment" => $text,
			"content" => "{}",
			"state" => 0
		]);
		error_log("Wrote Changelog on $taskID with $text");

	}
	
	public function getTaskValues($taskID){
		global $actualUser;

		$taskValues = $this->db->get("changelog", [
			"[>]tasklist" => ["taskid" => "id"]
		],
		[
			"tasklist.title",
			"changelog.content"
		], [
			"taskid" => $taskID,
			"changetype" => 0,
			"ORDER" => ["timestamp" => "DESC"],
		]);
		$taskTitle= $this->db->get("tasklist", [
			"title",
			"startdate",
			"enddate",
			"plannedenddate",
			"ismilestone"
		], [
			"id" => $taskID
		]);
		if ($taskValues!=NULL){
			$userInfo=$this->getTaskOwnerInfo($taskID);
			$res=json_decode($taskValues["content"]);
			if ($userInfo!==FALSE){
				$res->taskName=$taskTitle["title"];
				$res->startDate=$taskTitle["startdate"];
				//$res->endDate=$taskTitle["enddate"];
				$res->endDate=$taskTitle["plannedenddate"];
				$res->isMileStone=$taskTitle["ismilestone"]== 1 ? true : false ;
				$res->owner=$userInfo["firstname"]." ".$userInfo["lastname"];
				$res->notmine=$userInfo["id"]!=$actualUser["id"];
				return $res;
			}
		}
		$res=[
			"owner"=>"Nobody",
			"taskName"=>$taskTitle["title"],
			"notmine"=>true
		];
		return $res;
	}
	
	
	public function showWorkZoneByName($wzName){
		ob_start();
		$tasks = $this->db->select("tasklist", [
			"[>]workzone" => ["workzone_id" => "id"],
			"[>]tasknames" => ["tasknameid" => "id"],
			"[>]users" => ["ownerid" => "id"],
			"[>]statecodes" => ["state" => "state"],
		],
		[
			"tasklist.id(key)",
			"tasknames.name(text)",
			"statecodes.statecolorcode(color)",
			"users.firstname",
			"users.lastname",
			"tasklist.ownerid",
			"tasklist.title",
			"tasklist.state"
		],
		[
			"workzone.name[=]" => $wzName
		]);
		var_dump($this->db->error());
		$result = ob_get_clean();
		error_log("------showWorkZoneByName ".$result);

		$edges = $this->db->select("edgelist", [
			"[>]workzone" => ["workzone_id" => "id"]
		],
		[
			"edgelist.id",
			"edgelist.fromtaskid(from)",
			"edgelist.totaskid(to)",
			"edgelist.state"
		],
		[
			"workzone.name[=]" => $wzName
		]);
		if ($tasks!==FALSE){
			foreach($tasks as $key => $task){
				$tasks[$key]["text"]=$task["title"]."\n".$task["firstname"]." ".$task["lastname"]."\n[".$task["text"]."]";
			}
		}else{
			$tasks=[];
		}
		$res=[ "nodes" => $tasks , "links" => $edges];
		return $res;
	}
	
	public function getTaskHistory($taskID){
		$changelog = $this->db->select("changelog", [
			"[>]users" => ["taskowner" => "id"]
		],[
			"users.firstname",
			"users.lastname",
			"changelog.taskid",
			"changelog.timestamp",
			"changelog.changetype",
			"changelog.user_id",
			"changelog.taskowner",
			"changelog.predecessorState",
			"changelog.validated",
			"changelog.comment",
			"changelog.content",
			"changelog.state"
		],
		[
			"taskid[=]" => $taskID,
			"ORDER" => ["timestamp" => "ASC"],
		]);
		$history=["comments"=>[], "values"=>[]];
		foreach ($changelog as $change){
			array_push($history["comments"], [
				"user"=>$change["firstname"]." ".$change["lastname"],
				"user_id"=>$change["user_id"],
				"comment"=>$change["comment"],
				"timestamp"=>date("r",$change["timestamp"])
			]);
			if ($change["changetype"]==0) {
				$json=json_decode($change["content"]);
				foreach ($json as $name => $value) {
					if(strpos(strtolower($name),"date")!==false){
						$value=date("m/d/Y",$value);
					}
					if(!isset($history["values"][$name]) || $history["values"][$name]["value"]!=$value ){
						$history["values"][$name]=[
							"user"=>$change["firstname"]." ".$change["lastname"],
							"user_id"=>$change["user_id"],
							"comment"=>$change["comment"],
							"name"=>$name,
							"timestamp"=>date("m/d/Y H:i:s",$change["timestamp"]),
							"value"=>$value
						];
					}
				}
			}
		}
		return $history;
	}

	public function getStateNames($thisState){
		global $stateNameTable;
		if (!isset($stateNameTable)){
			$stateNameTable=[];
			$states = $this->db->select("statecodes", [
				"state",
				"statename"
			],
			[
			]);
			foreach ($states as $key =>$state) {
				$stateNameTable[$state["state"]]=$state["statename"];
			}
		}
		return $stateNameTable[$thisState]."(".$thisState.")";
	}

	public function getTaskPredecessorStates($taskID){
		$edges = $this->db->select("edgelist", [
			"[>]statecodes" => "state",
			"[>]tasklist" => ["fromtaskid" => "id"],
			"[>]tasknames" => ["tasklist.tasknameid" => "id"]
		],
		[
			"edgelist.id",
			"edgelist.fromtaskid(taskid)",
			"tasknames.name(taskname)",
			"tasklist.title",
			"tasklist.state(taskstate)",
			"edgelist.state",
			"statecodes.statecolorcode(color)"
		],
		[
			"totaskid[=]" => $taskID
		]);
		foreach ($edges as $key =>$edge) {
			$edges[$key]["history"]=$this->getTaskHistory($edge["taskid"]);
		}
		$res=[ "taskPredecessorStateTable" => $edges];
		return $res;
	}
	
	public function takeoverOwnership($taskID){
		global $actualUser;
		$this->writeChangeLog($taskID,"Took Ownership",$actualUser["id"]);
		$edges = $this->db->update("tasklist", [
			"ownerid" => $actualUser=["id"]
		],
		[
			"id[=]" => $taskID
		]);
		return true;
	}
	
	


	/*
	INSERT INTO logbook_statecodes VALUES(1,'Requested',"Gainsboro","#DCDCDC",0);
INSERT INTO logbook_statecodes VALUES(2,'Done',"Lime","#00FF00",1);
INSERT INTO logbook_statecodes VALUES(3,'In Work',"Aqua","#00FFFF",2);
INSERT INTO logbook_statecodes VALUES(4,'Reworked',"Gold","#FFD700",3);
INSERT INTO logbook_statecodes VALUES(5,'Unclear',"Orange","#FFA500",4);
INSERT INTO logbook_statecodes VALUES(6,'Faulty',"OrangeRed","	#FF4500",5);
INSERT INTO logbook_statecodes VALUES(7,'Ignore',"NavajoWhite","#FFDEAD",6);


digraph G {

node [shape=box style=filled];

s0 [label="Requested (0)" color="#DCDCDC"];
s1 [label="Done (1)"  fillcolor="#00FF00" shape="Msquare"];
s2 [label="In Work (2)"  color="#00FFFF"];
s3 [label="Reworked (3)"  color="#FFD700"];
s4 [label="Unclear (4)" color="#FFA500"];
s5 [label="Faulty (5)" color="#FF4500"];
s6 [label="Ignore (6)"  color="#FFDEAD"];

start [shape=Mdiamond];

start -> s0 ;
start -> s6 [label = "when upper task ignors"];
s0 -> s2
s2 -> s1
s1 -> s5 [label = "late change"];
s5 -> s3 [label = "set to done again"];

s1 -> s4 [label = "as long Subtask is faulty"];

s3 -> s1 [label = "Accepted"];

}

	*/
	
	public function  calculateNewTaskState($old,$new){
		$lookup=[
			// Requested
			0 => [
				0 => 0,
				1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0
			],
			// Done
			1 => [
				0 => 1,
				1 => 1,
				2 => 1,
				3 => 4,
				4 => 4,
				5 => 4,
				6 => 1
			],
			// in Work
			2 => [
				0 => 2,
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 2,
				6 => 2
			],
			// Reworked
			3 => [
				0 => 3,
				1 => 3,
				2 => 3,
				3 => 3,
				4 => 4,
				5 => 4,
				6 => 3
			],
			// Unclear
			4 => [
				0 => 0,
				1 => 4,
				2 => 2,
				3 => 4,
				4 => 4,
				5 => 4,
				6 => 4
			],
			//Faulty
			5 => [
				0 => 5,
				1 => 5,
				2 => 5,
				3 => 5,
				4 => 5,
				5 => 5,
				6 => 5
			],
			// Ignore
			6 => [
				0 => 6,
				1 => 6,
				2 => 6,
				3 => 6,
				4 => 6,
				5 => 6,
				6 => 6
			],
		];
		return $lookup[$old][$new];
	}
	
	public function updateTaskTree(&$model,$taskArray){
		foreach ($taskArray as $taskID){
			$oldTaskState=$model["tasks"][$taskID]["state"];
			$newTaskState=$model["tasks"][$taskID]["state"];
			foreach($model["edges"] as $edge){
				if ($edge["totaskid"]==$taskID){
					$newTaskState=$this->calculateNewTaskState($newTaskState,$edge["totaskid"]);
				}
			}
			if ($oldTaskState!=$newTaskState){
				error_log("task ".$taskID." changes from state ".$oldTaskState." to ".$newTaskState);
				$model["tasks"][$taskID]["state"]=$newTaskState;
				$model["tasks"][$taskID]["new"]=true;
				$affectedTasks=[];
				foreach($model["edges"] as $id => $edge){
					if ($edge["fromtaskid"]==$taskID){
						$oldEgdeState=$edge["state"];
						$newEgdeState=$this->calculateNewTaskState($oldEgdeState,$newTaskState);
						if ($oldEgdeState!=$newEgdeState){
							error_log("edge  ".$id." changes from state ".$oldEgdeState." to ".$newEgdeState);
							if (! in_array($edge["fromtaskid"],$affectedTasks)){
								$affectedTasks[]=$edge["fromtaskid"];
							}
							$model["edges"][$id]["state"]=$newEgdeState;
							$model["edges"][$id]["new"]=true;
						}
					}
				}
				$this->updateTaskTree($model,$affectedTasks);
			}else{
				error_log("task ".$taskID." does not change from state ".$oldTaskState." to ".$newTaskState);

			}
		}
	}

	public function dumpModel($model){
		foreach ($model["tasks"] as $taskID => $task){
			error_log("--------");
			error_log("id             :".$task["id"]);
			error_log("title          :".$task["title"]);
			error_log("state          :".$this->getStateNames($task["state"]));
			error_log("startdate      :".date("m.d.Y H:i:s",$task["startdate"]));
			error_log("enddate        :".date("m.d.Y H:i:s",$task["enddate"]));
			error_log("plannedenddate :".date("m.d.Y H:i:s",$task["plannedenddate"]));
			error_log("duration       :".$task["duration"]);
			error_log("fulfillrate    :".$task["fulfillrate"]);
			error_log("ismilestone    :".$task["ismilestone"]);
			if (isset($task["new"])){
				error_log("CHANGED !");
			}
		}
	}

	public function printTasksOnly(&$model,$taskID,$taskDescends){
		foreach ($taskDescends as $subLevelTaskID => $subLevelTask){
			error_log("task:".$model["tasks"][$taskID]["title"]. " has decent ".$model["tasks"][$subLevelTaskID]["title"]);
		}
	}

	public function calculateTaskStates(&$model,$taskID,$taskDescends){
		$thisTask=$model["tasks"][$taskID];
		/*
		if ($thisTask["state"]==1){//task finished, no calculation needed
			return;
		}
		*/
		// calculate new state based on the descants
		$newTaskState=$thisTask["state"];
		error_log("calculateTaskStates for task: ".$taskID." with actual state ".$newTaskState);
		// in case the task is done internally, we might need to adjust this
		if ($thisTask["validated"]) {
			$newTaskState=1;
		}
		foreach ($taskDescends as $subLevelTaskID => $subLevelTask){
			$thisEdgeID=$subLevelTask["edge"];
			$thisEdgeState=$model["edges"][$thisEdgeID]["state"];
			if ($thisEdgeState!=6){ // if previous task is not ignored
				error_log("evaluate state change from : ".$newTaskState." by : ".$model["tasks"][$subLevelTaskID]["state"]);
				$newTaskState=$this->calculateNewTaskState($newTaskState,$model["tasks"][$subLevelTaskID]["state"]);
				error_log("to state: ".$newTaskState);
			}
		}
		//does this change the task?
		if ($thisTask["state"]!=$newTaskState){
			$model["tasks"][$taskID]["state"]=$newTaskState;
			$model["tasks"][$taskID]["new"]=true;
		}
	}


	public function calculateTaskEndDates(&$model,$taskID,$taskDescends){
		$thisTask=$model["tasks"][$taskID];
		if ($thisTask["state"]==1){//task finished, no calculation needed
			return;
		}
		// find the latest end date of the descants
		$date=time();
		foreach ($taskDescends as $subLevelTaskID => $subLevelTask){
			$thisEdgeID=$subLevelTask["edge"];
			$thisEdgeState=$model["edges"][$thisEdgeID]["state"];
			if ($thisEdgeState!=6){ // if previous task is not ignored
				$desValue=$model["tasks"][$subLevelTaskID]["enddate"];
				if ($desValue>$date){
					$date=$desValue;
				}
			}
		}
		$newEndTime=$date+3600*24*$thisTask["duration"]*(100-$thisTask["fulfillrate"]);
		//does this change the task?
		if ($thisTask["enddate"]!=$date){
			$model["tasks"][$taskID]["enddate"]=$date;
			$model["tasks"][$taskID]["new"]=true;
		}
	}

	public function calculateTaskIgnores(&$model,$taskID,$taskDescends){
		$thisTask=$model["tasks"][$taskID];
		if ($thisTask["state"]==1){//task finished, no calculation needed
			return;
		}
		$newState=$thisTask["state"];
		if ($thisTask["fulfillrate"]>0){//bloody trick to recover just requested / work tasks after have the status overwritten
			$newState=2;
		}
		else{
			$newState=0;
		}
		if(count($taskDescends)>0){ // any predecessors at all?
			// go through the edges
			$isIgnored=true;
			foreach ($taskDescends as $subLevelTaskID => $subLevelTask){
				$thisEdgeID=$subLevelTask["edge"];
				$thisEdgeState=$model["edges"][$thisEdgeID]["state"];
				$prevTaskState=$model["tasks"][$subLevelTaskID]["state"];
				error_log("evaluate Ignore state from sub task  :".$subLevelTaskID.": ".$prevTaskState);
				error_log("evaluate Ignore state of edge :".$thisEdgeID.": ".$thisEdgeState);
				if (!($thisEdgeState== 6 || $prevTaskState==6 )){ // if state = ignored
					$isIgnored=false;
				}
			}
			if ($isIgnored){
				$newState=6;
			}
		}
		error_log("New Ignore state:".$newState." for task ID ".$taskID);
		//does this change the task?
		if ($thisTask["state"]!=$newState){
			$model["tasks"][$taskID]["state"]=$newState;
			$model["tasks"][$taskID]["new"]=true;
		}
	}

	public function iterateThroughDependency(&$model , $dependency, $functionToCall){
		foreach ($dependency as $levelID => $level){
			error_log("iterateThroughDependency level:".$levelID);
			foreach ($level as $taskID => $taskDescends){
				$functionToCall($model,$taskID,$taskDescends);
			}
		}
	}

	public function calculateModelData(&$model){
		// building a array containing the different dependency levels
		$stepUpArray=[];
		$tempStepArray=[];
		$actLevel=0;
		$stepUpArray[$actLevel]=[];
		$tempStepArray[$actLevel]=[];
		// at first we fill level 0 with all tasks which do not have a predecessors, so the starting tasks
		foreach($model["tasks"] as $taskID => $task){
			if (count($task["preTasks"])==0){
				$stepUpArray[$actLevel][$taskID]=$task["preTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
				$tempStepArray[$actLevel][$taskID]=$task["sucTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
			}
		}
		# now we repeat this with the tasks found, until there's no more precessor found
		$moreTasksFound=true;
		while($moreTasksFound){
			$moreTasksFound=false;
			$actLevel++;
			$stepUpArray[$actLevel]=[];
			$tempStepArray[$actLevel]=[];
			foreach($tempStepArray[$actLevel-1] as $taskID => $taskSuccessors){
				if (count($taskSuccessors)>0){
					$moreTasksFound=true;
					foreach($taskSuccessors as $nextTaskID =>$nextTask){
						$stepUpArray[$actLevel][$nextTaskID]=$model["tasks"][$nextTaskID]["preTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
						$tempStepArray[$actLevel][$nextTaskID]=$model["tasks"][$nextTaskID]["sucTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
					}
				}
			}
			if (!$moreTasksFound){
				unset($stepUpArray[$actLevel]);
				unset($tempStepArray[$actLevel]);
			}
		}


		// building a array containing the different dependency levels
		// and building a temporary array in parallel, as the construction process needs the
		//predecessors, but in the final array we'll need the successors...
		$stepDownArray=[];
		$tempStepArray=[];
		$actLevel=0;
		$stepDownArray[$actLevel]=[];
		$tempStepArray[$actLevel]=[];
		// at first we fill level 0 with all tasks which do not have a sucessor, so the ending tasks
		foreach($model["tasks"] as $taskID => $task){
			if (count($task["sucTasks"])==0){
				$stepDownArray[$actLevel][$taskID]=$task["sucTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
				$tempStepArray[$actLevel][$taskID]=$task["preTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
			}
		}
		# now we repeat this with the tasks found, until there's no more successor found
		$moreTasksFound=true;
		while($moreTasksFound){
			$moreTasksFound=false;
			$actLevel++;
			$stepDownArray[$actLevel]=[];
			$tempStepArray[$actLevel]=[];
			foreach($tempStepArray[$actLevel-1] as $taskID => $taskSuccessors){
				if (count($taskSuccessors)>0){
					$moreTasksFound=true;
					foreach($taskSuccessors as $nextTaskID =>$nextTask){
						$stepDownArray[$actLevel][$nextTaskID]=$model["tasks"][$nextTaskID]["sucTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
						$tempStepArray[$actLevel][$nextTaskID]=$model["tasks"][$nextTaskID]["preTasks"]; # by this $taskID => $task trick we make sure that each taskID is stored only once
					}
				}
			}
			if (!$moreTasksFound){
				unset($stepDownArray[$actLevel]);
				unset($tempStepArray[$actLevel]);
			}
		}

		error_log("Aufsteigende Tasks");
		$this->iterateThroughDependency($model , $stepUpArray, array($this, 'printTasksOnly'));

		error_log("absteigende Tasks");
		$this->iterateThroughDependency($model , $stepDownArray, array($this, 'printTasksOnly'));

		error_log("calculateTaskEndDates");
		$this->iterateThroughDependency($model , $stepDownArray, array($this, 'calculateTaskEndDates'));

		error_log("Model BEFORE calculateTaskIgnores:");
		$this->dumpModel($model);

		ob_start();
		var_dump($model["edges"]);
		$result = ob_get_clean();
		error_log($result);


		error_log("calculateTaskIgnores");
		$this->iterateThroughDependency($model , $stepDownArray, array($this, 'calculateTaskIgnores'));

		error_log("Model AFTER calculateTaskIgnores:");
		$this->dumpModel($model);


		error_log("calculateTaskStates");
		$this->iterateThroughDependency($model , $stepUpArray, array($this, 'calculateTaskStates'));


	}

	
	public function getModel($workzone_id){
		$tasks = $this->db->select("tasklist", 
			[
				"tasklist.id",
				"tasklist.title",
				"tasklist.state",
				"tasklist.startdate",
				"tasklist.enddate",
				"tasklist.plannedenddate",
				"tasklist.duration",
				"tasklist.fulfillrate",
				"tasklist.validated",
				"tasklist.ismilestone"
				],
			[
				"workzone_id" => $workzone_id
			]
		);
		$edges = $this->db->select("edgelist", 
			[
				"edgelist.id",
				"edgelist.fromtaskid",
				"edgelist.totaskid",
				"edgelist.state"
			],
			[
				"workzone_id" => $workzone_id
			]
		);
		$sortedTasks=[];
		foreach($tasks as $task){//sort by index for better processing
			$taskID=$task["id"];
			$sortedTasks[$taskID]=$task;
			/*
			$sortedTasks[$taskID]=[];
			$sortedTasks[$taskID]["id"]=$task["id"];
			$sortedTasks[$taskID]["state"]=$task["state"];
			$sortedTasks[$taskID]["title"]=$task["title"];
			$sortedTasks[$taskID]["startdate"]=$task["startdate"];
			$sortedTasks[$taskID]["enddate"]=$task["enddate"];
			$sortedTasks[$taskID]["plannedenddate"]=$task["plannedenddate"];
			$sortedTasks[$taskID]["duration"]=$task["duration"];
			$sortedTasks[$taskID]["fulfillrate"]=$task["fulfillrate"];
			$sortedTasks[$taskID]["ismilestone"]=$task["ismilestone"];
			*/
			$sortedTasks[$taskID]["preTasks"]=[];
			$sortedTasks[$taskID]["sucTasks"]=[];
			foreach ($edges as $edgeID => $edge){
				if ($edge["fromtaskid"]==$taskID){
					$sortedTasks[$taskID]["sucTasks"][$edge["totaskid"]]=["taskid" => $edge["totaskid"], "edge"=> $edgeID];
				}
				if ($edge["totaskid"]==$taskID){
					$sortedTasks[$taskID]["preTasks"][$edge["fromtaskid"]]=["taskid" => $edge["fromtaskid"], "edge"=> $edgeID];
				}
			}
		}
		return [ "tasks" => $sortedTasks , "edges" => $edges];

	}


	public function updateModelState($workzone_id,$newStateTask){
		$model=$this->getModel($workzone_id);
		$this->updateTaskTree($model,[$newStateTask]);
		$this->calculateModelData($model);


		$this->dumpModel($model);

		foreach($model["tasks"] as $key=>$taskID){
			if (isset($model["tasks"][$key]["new"])){
				error_log("task ".$key." state changed to ".$model["tasks"][$key]["state"]);
				// remove fields, which either should not be touched or do not exist at all in the DB
				unset($model["tasks"][$key]["id"]);
				unset($model["tasks"][$key]["title"]);
				unset($model["tasks"][$key]["duration"]);
				unset($model["tasks"][$key]["ismilestone"]);
				unset($model["tasks"][$key]["preTasks"]);
				unset($model["tasks"][$key]["sucTasks"]);
				unset($model["tasks"][$key]["new"]);

				ob_start();
				print_r($model["tasks"][$key]);
				var_dump($model["tasks"][$key]);
				$result = ob_get_clean();
				error_log($result);
		
				$data = $this->db->update("tasklist", 
				$model["tasks"][$key]
				, [
					"id[=]" => $key
				]);
				$arr = $data->errorInfo();
			}
		}
		foreach($model["edges"] as $edgeID => $edge){
			if (isset($model["edges"][$edgeID]["new"])){
				error_log("edge ".$edgeID." state changed to ".$model["edges"][$edgeID]["state"]);
			}
		}
		return true;
	}

	
	public function toggleTaskPredecessorIgnoreState($edgeID){
		$preTaskState = $this->db->select("edgelist", [
			"[>]tasklist" => ["fromtaskid" => "id"],
		],
		[
			"tasklist.fulfillrate",
			"edgelist.totaskid",
			"edgelist.workzone_id",
			"edgelist.state",
		],
		[
			"edgelist.id[=]" => $edgeID
		]);
		$taskfulfillrate=$preTaskState[0]["fulfillrate"];
		$edgeState=$preTaskState[0]["state"];
		$newStateTask=$preTaskState[0]["totaskid"];
		$workzone_id=$preTaskState[0]["workzone_id"];
		
		if ($edgeState==6){ //if ignore
			$newState= 3; // marked as "reworked", so the user has to accept the data 
		}else{
			$newState= 6; // ignored
		}
		$data = $this->db->update("edgelist", [
			"state" => $newState
		], [
			"id" => $edgeID
		]);
		$this->updateModelState($workzone_id,$newStateTask);
		return true;
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
			if (isset($post['wzName'])){
				$wzName = strtolower($post['wzName']);
			}
			if (isset($post['taskName'])){
				$taskName =$post['taskName'];
			}
			if ($action==1){ //ok to create?
				error_log("Ok to create Workzone?");
				if (!isset($wzName) || !isset($taskName)){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				if (!(preg_match("/^(\w+\.)+\w+$/",$wzName)===1)){
					die('{"errorcode":0, "data": false, "error": "Work Zone Invalid syntax"}');
				}
				if (!$this->jt->taskExists($taskName)){
					die('{"errorcode":0, "data": false, "error": "Task not exists"}');
				}
				die('{"errorcode":0, "data": true}');
			}
			if ($action==2){ //create
				error_log("Create Workzone");
				if (!isset($wzName) || !isset($taskName)){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				if (!(preg_match("/^(\w+\.)+\w+$/",$wzName)===1)){
					die('{"errorcode":0, "data": false, "error": "Work Zone Invalid syntax"}');
				}
				if (!$this->jt->taskExists($taskName)){
					die('{"errorcode":0, "data": false, "error": "Task not exists"}');
				}
				$wzID=$this->wz->createWorkZone($wzName);
				$taskIDs=array();
				foreach ($toDo as $successorTaskName => $childs){
					$toTaskID=$this->createTask($wzID,$successorTaskName,$this->jt->getTaskTitle($successorTaskName),json_encode($this->jt->getTaskContent($successorTaskName)));
					foreach ($childs  as $predecessorTaskName =>$child){
						$fromTaskID=$this->createTask($wzID,$predecessorTaskName,$this->jt->getTaskTitle($predecessorTaskName),json_encode($this->jt->getTaskContent($predecessorTaskName)));
						// create the edges here
						$this->createOrModifyEdge($wzID,$fromTaskID,$toTaskID,0);
					}
				}
				die('{"errorcode":0, "data": { "workzone_id" :'.$wzID.', "workzonename": "'.$wzName.'" } }');
			}
			if ($action==3){ //request Work Zone overview
				error_log("request Work Zone overview");
				if (!isset($wzName) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				die('{"errorcode":0, "data": '.json_encode(array_values($this->getWorkZoneOverview($wzName))).'}');

			}
			if ($action==4){ //show Work Zone by Name
				error_log("show Work Zone by Name");
				if (!isset($wzName) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				die('{"errorcode":0, "data": '.json_encode($this->showWorkZoneByName($wzName)).'}');

			}
			if ($action==5){ //request Task schema
				error_log("request Task schema");
				if (!isset($post['taskID']) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$taskID = $post['taskID'];
				die('{"errorcode":0, "data": { "content" : '.json_encode($this->getTaskSchema($taskID)).', "startval" : {} }}');

			}
			if ($action==6){ //store Task Values
				error_log("store Task Values");
				if (!isset($post['input'])) {
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$this->setTaskValues($post['input'],1);
				die('{"errorcode":0, "data": true}');

			}
			if ($action==7){ //get Task Values
				error_log("get Task Values");
				if (!isset($post['taskID'])) {
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				die('{"errorcode":0, "data": '.json_encode($this->getTaskValues($post['taskID'])).'}');
			}
			if ($action==8){ //request Predecessor status list
				error_log("request Predecessor status list");
				if (!isset($post['taskID']) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$taskID = $post['taskID'];
				die('{"errorcode":0, "data": '.json_encode($this->getTaskPredecessorStates($taskID)).'}');

			}
			if ($action==9){ //toggle ignore Predecessor task
				error_log("toggle ignore Predecessor task");
				if (!isset($post['edgeID']) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$edgeID = $post['edgeID'];
				die('{"errorcode":0, "data": '.json_encode($this->toggleTaskPredecessorIgnoreState($edgeID)).'}');
			}
			
			if ($action==10){ //Accept Predecessor task
				error_log("Accept Predecessor task");
				if (!isset($post['edgeID']) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$edgeID = $post['edgeID'];
				die('{"errorcode":0, "data": '.json_encode($this->acceptPredecessor($edgeID)).'}');

			}
			if ($action==11){ //take over ownership
				error_log("take over ownership");
				if (!isset($post['taskID']) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$taskID = $post['taskID'];
				die('{"errorcode":0, "data": '.json_encode($this->takeoverOwnership($taskID)).'}');

			}
			
		}else{
			die('{"errorcode":1, "error": "Variable Error"}');
		}
	}
	
}

if (!debug_backtrace()) {
	// do useful stuff
	$jh=TasksHandler::Instance();
	$jh->doRequest($_POST);
}
?>
