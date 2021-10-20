<?php

require_once  'Database.php';

require_once  'login.php';

require_once("Config.php");

class ChangelogHandler  {
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
	

	
	public function writeChangeLog($requestID,$text,$requestOwner){
		global $actualUser;
		$pdoStatement=$this->db->insert("changelog", [
			"requestid" => $requestID,
			"timestamp" => time(),
			"changetype" => 1,
			"userid" => $actualUser["id"],
			"requestowner" => $requestOwner,
			"predecessorState" => 0,
			"validated" => 0,
			"comment" => $text,
			"content" => "{}",
			"state" => 0
		]);
		error_log("Wrote Changelog on $requestID with $text");

	}

	public function getRequestHistory($requestID){
		$changelog = $this->db->select("changelog", [
			"[>]users" => ["requestowner" => "id"]
		],[
			"users.firstname",
			"users.lastname",
			"changelog.requestid",
			"changelog.timestamp",
			"changelog.changetype",
			"changelog.userid",
			"changelog.requestowner",
			"changelog.predecessorState",
			"changelog.validated",
			"changelog.comment",
			"changelog.content",
			"changelog.state"
		],
		[
			"requestid[=]" => $requestID,
			"ORDER" => ["timestamp" => "ASC"],
		]);
		$history=["comments"=>[], "values"=>[]];
		foreach ($changelog as $change){
			array_push($history["comments"], [
				"user"=>$change["firstname"]." ".$change["lastname"],
				"userid"=>$change["userid"],
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
							"userid"=>$change["userid"],
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

}

if (!debug_backtrace()) {
	// do useful stuff
	$jh=ChangelogHandler::Instance();
	$jh->doRequest($_POST);
}
?>
