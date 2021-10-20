<?php


require_once  'Database.php';

require_once  'login.php';


class WorkZones  {
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
	
	public function getWorkzoneID($wz) {
		$values = $this->db->select("workzone", [
			"id",
			"objname"
			], [
			"objname[=]" => $wz
		]);
		if (empty($values)){
			return False;
		}
		return $values[0]["id"];
	}
	
	public function get_WorkZones($query){
		$values = $this->db->select("workzone", [
			"objname"
			], [
			#"user_id[>]" => 100
		]);
		$result=array();
		//if ($query) {
			foreach ($values as $key => $value) {
				if (stripos($value["objname"], $query) !== false  || !($query)) {
					if (! in_array($value["objname"],$result)){
						$result[] = $value["objname"];
					}
				}
			}
			sort($result);
		//}
		return $result;
	}
	
	public function createWorkZone($wz,$desc){
		$id=$this->getWorkzoneID($wz);
		if ($id===false){
			$this->db->insert("workzone", [
				"objname" => $wz,
				"description"=> $desc
			]);
			return $this->db->id();
		}else{
			return $id;
		}
	}

	public function doRequest($post){
		$query = $post['query'];
		die('{"errorcode":0, "data": '.json_encode(array_values($this->get_WorkZones($query))).'}');
	}

}

if (!debug_backtrace()) {
    // testing

	$wz=WorkZones::Instance();
	// add a workzone
	echo($wz->createWorkZone('BluePower','Blue Text')."\n");
	// search for that new workzone
	echo($wz->getWorkzoneID('BluePower')."\n");
	// search for a not existing workzone
	echo(var_dump($wz->getWorkzoneID('GreenPower')));
	$wz->doRequest(['query' => 'blue']);
}
?>
