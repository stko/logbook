<?php

require_once  'Database.php';

require_once  'WorkZones.php';

require_once  'login.php';

require_once("Config.php");

class RequestsHandler  {
	private static $instance;

	private $db;
	private $wz;


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
	}	
	
	public function getRequestNameID($request) {
		$values = $this->db->select("requestnames", [
			"id",
			"objname"
			], [
			"objname[=]" => $request
		]);
		if (empty($values)){
			return False;
		}
		return $values[0]["id"];
	}
	
	public function getRequestSchema($requestID) {
		$values = $this->db->select("requestlist", [
			"content",
			"workzoneid"
			], [
			"id[=]" => $requestID
		]);
		if (empty($values)){
			return "{}";
		}else{

			$this->dumpModel($this->getModel($values[0]["workzoneid"]));


			return json_decode($values[0]["content"]);
		}
	}
	
	public function createRequest($wzID,$request,$title,$content){

		die routine fehlt noch 
		global $actualUser;
		$requestID=$this->createRequestName($request);
		$values = $this->db->select("requestlist", [
			"id",
			], [
			"workzoneid[=]" => $wzID,
			"requestnameid[=]" => $requestID
		]);
		if (empty($values)){
			$json=json_decode($content);
			$data= [
				"workzoneid" => $wzID,
				"requestnameid" => $requestID,
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
			$this->db->insert("requestlist", $data);
			return $this->db->id();
		}else{
			return $values[0]["id"];
		}
	}

	public function setRequestValues($data,$userID){
		if (!isset($data["requestID"]) 
		or !isset($data["predecessorState"])
		or !isset($data["validated"])
		or !isset($data["comment"])
		or !isset($data["content"])
		or !isset($data["state"])
		){
			die('{"errorcode":1, "error": "Variable Error"}');
		}
		$values = $this->db->select("requestlist", [
			"workzoneid",
			"state"
			], [
			"id[=]" => $data["requestID"]
		]);
		$wzID=$values[0]["workzoneid"];
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
				}else{ // even more worse: Request is not valid anymore
				$newEdgeState=5; //faulty
				$newState=5; // faulty
				}
			}
			$values = $this->db->update("edgelist", [
					"state" => $newEdgeState
				], [
				"workzoneid[=]" => $wzID,
				"fromrequestid[=]" => $data["requestID"]
			]);

			
			$values = $this->db->update("requestlist", [
					"state" => $newState
				], [
				"id[=]" => $data["requestID"]
			]);

		}

		$values = $this->db->update("requestlist", [
			"startdate" => $data["content"]["endDate"]-$data["content"]["duration"]*24*3600,
			//"enddate" => $data["content"]["endDate"],
			"plannedenddate" => $data["content"]["endDate"],
			"duration" => $data["content"]["duration"],
			//"fulfillrate" => $data["content"]["fulfillrate"],
			"fulfillrate" => 50, //dummy, until the GUI provides also the fulfillrate
			"ismilestone" => $data["content"]["isMileStone"] ? 1 : 0 
			], [
			"id[=]" => $data["requestID"]
		]);

	
		$userInfo=$this->getRequestOwnerInfo($data["requestID"]);
		if ($userInfo!=NULL) {
			$owner=$userInfo["id"];
			ob_start();
			var_dump($userInfo);
			$result = ob_get_clean();
		} else {
			$owner=$userID; 
		}
		

		$pdoStatement=$this->db->insert("changelog", [
			"requestid" => $data["requestID"],
			"timestamp" => time(),
			"changetype" => 0,
			"userid" => $userID,
			"requestowner" => $owner,
			"predecessorState" => $data["predecessorState"],
			"validated" => $data["validated"],
			"comment" => json_encode($data["comment"]),
			"content" => json_encode($data["content"]),
			"state" => $data["state"]
		]);
	}

	public function getWorkZoneOverview($wzName){
		$data = $this->db->query(
			"SELECT <workzone.objname> , COUNT(<requestlist.id>)  FROM <workzone> INNER JOIN  <logbook_requestlist> ON  <workzone.id> = <requestlist.workzoneid> WHERE (lower(<workzone.objname>) LIKE lower( :workzonename ) ) AND <requestlist.state> != :state GROUP BY <workzone.objname>" , [
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
				$data[]=["objname" => $wzResult["objname"] , "count" => $wzResult[1]];
			}
			return $data;
		}
	}
	
	public function getRequestOwnerInfo($requestID){
		$data = $this->db->get("changelog", [
			"[>]users" => ["requestowner" => "id"]
		],
		[
			"users.firstname",
			"users.lastname",
			"users.id"
		], [
			"requestid" => $requestID,
			"ORDER" => ["timestamp" => "DESC"],
		]);
		return $data;
	}
	

	public function getRequestValues($requestID){
		global $actualUser;

		$requestValues = $this->db->get("changelog", [
			"[>]requestlist" => ["requestid" => "id"]
		],
		[
			"requestlist.title",
			"changelog.content"
		], [
			"requestid" => $requestID,
			"changetype" => 0,
			"ORDER" => ["timestamp" => "DESC"],
		]);
		$requestTitle= $this->db->get("requestlist", [
			"title",
			"startdate",
			"enddate",
			"plannedenddate",
			"ismilestone"
		], [
			"id" => $requestID
		]);
		if ($requestValues!=NULL){
			$userInfo=$this->getRequestOwnerInfo($requestID);
			$res=json_decode($requestValues["content"]);
			if ($userInfo!==FALSE){
				$res->requestName=$requestTitle["title"];
				$res->startDate=$requestTitle["startdate"];
				//$res->endDate=$requestTitle["enddate"];
				$res->endDate=$requestTitle["plannedenddate"];
				$res->isMileStone=$requestTitle["ismilestone"]== 1 ? true : false ;
				$res->owner=$userInfo["firstname"]." ".$userInfo["lastname"];
				$res->notmine=$userInfo["id"]!=$actualUser["id"];
				return $res;
			}
		}
		$res=[
			"owner"=>"Nobody",
			"requestName"=>$requestTitle["title"],
			"notmine"=>true
		];
		return $res;
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
			if (isset($post['requestName'])){
				$requestName =$post['requestName'];
			}
			if ($action==1){ //ok to create?
				error_log("Ok to create Workzone?");
				if (!isset($wzName) || !isset($requestName)){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				if (!(preg_match("/^(\w+\.)+\w+$/",$wzName)===1)){
					die('{"errorcode":0, "data": false, "error": "Work Zone Invalid syntax"}');
				}
				if (!$this->jt->requestExists($requestName)){
					die('{"errorcode":0, "data": false, "error": "Request not exists"}');
				}
				die('{"errorcode":0, "data": true}');
			}
			if ($action==2){ //create
				error_log("Create Workzone");
				if (!isset($wzName) || !isset($requestName)){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				if (!(preg_match("/^(\w+\.)+\w+$/",$wzName)===1)){
					die('{"errorcode":0, "data": false, "error": "Work Zone Invalid syntax"}');
				}
				if (!$this->jt->requestExists($requestName)){
					die('{"errorcode":0, "data": false, "error": "Request not exists"}');
				}
				$wzID=$this->wz->createWorkZone($wzName);
				$requestIDs=array();

				die('{"errorcode":0, "data": { "workzoneid" :'.$wzID.', "workzonename": "'.$wzName.'" } }');
			}
			if ($action==3){ //request Work Zone overview
				error_log("request Work Zone overview");
				if (!isset($wzName) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				die('{"errorcode":0, "data": '.json_encode(array_values($this->getWorkZoneOverview($wzName))).'}');

			}

			
			if ($action==5){ //request Request schema
				error_log("request Request schema");
				if (!isset($post['requestID']) ){
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$requestID = $post['requestID'];
				die('{"errorcode":0, "data": { "content" : '.json_encode($this->getRequestSchema($requestID)).', "startval" : {} }}');

			}
			if ($action==6){ //store Request Values
				error_log("store Request Values");
				if (!isset($post['input'])) {
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				$this->setRequestValues($post['input'],1);
				die('{"errorcode":0, "data": true}');

			}
			if ($action==7){ //get Request Values
				error_log("get Request Values");
				if (!isset($post['requestID'])) {
					die('{"errorcode":1, "error": "Variable Error"}');
				}
				die('{"errorcode":0, "data": '.json_encode($this->getRequestValues($post['requestID'])).'}');
			}
			
		}else{
			die('{"errorcode":1, "error": "Variable Error"}');
		}
	}
	
}

if (!debug_backtrace()) {
	// do useful stuff
	$jh=RequestsHandler::Instance();
	$jh->doRequest($_POST);
}
?>
