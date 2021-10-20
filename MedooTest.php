<?php

require  'Medoo.php';
require  'Database.php';

# sudo apt install php-sqlite3

use Medoo\Medoo;

  
// Singleton request
$database = Database::instance();

/*
$data = $database->query(
	"SELECT <taskname.name> FROM <taskname>,<tasklist> WHERE <tasklist.workzone_id> = :workzone_id AND <tasklist.state> != :state AND <taskname.id> = <tasklist.tasknameid>" , [
		":workzone_id" => 2,
		":state" => 1
	]
)->fetchAll();
*/

if (true){
		$data = $database->select("tasklist", 
			[
				"tasklist.id",
				"tasklist.state"
			],
			[
				"workzone_id" => 3
			]
		);
}else{


$data = $database->select("post", [
	// Here is the table relativity argument that tells the relativity between the table you want to join.
 
	// The row author_id from table post is equal the row user_id from table account
	"[>]account" => ["author_id" => "user_id"],
 
	// The row user_id from table post is equal the row user_id from table album.
	// This is a shortcut to declare the relativity if the row name are the same in both table.
	"[>]album" => "user_id",
 
	// [post.user_id is equal photo.user_id and post.avatar_id is equal photo.avatar_id]
	// Like above, there are two row or more are the same in both table.
	"[>]photo" => ["user_id", "avatar_id"],
 
	// If you want to join the same table with different value,
	// you have to assign the table with alias.
	"[>]account (replyer)" => ["replyer_id" => "user_id"],
 
	// You can refer the previous joined table by adding the table name before the column.
	"[>]account" => ["author_id" => "user_id"],
	"[>]album" => ["account.user_id" => "user_id"],
 
	// Multiple condition
	"[>]account" => [
		"author_id" => "user_id",
		"album.user_id" => "user_id"
	]
], [
	"post.post_id",
	"post.title",
	"account.user_id",
	"account.city",
	"replyer.user_id",
	"replyer.city"
], [
	"post.user_id" => 100,
	"ORDER" => ["post.post_id" => "DESC"],
	"LIMIT" => 50
]);
}


if ($data===false){
	var_dump( $database->error() );
}else{
	print_r($data);
}
var_dump($database->log());

//	"SELECT <workzone.name> , COUNT (<tasklist.id>)  FROM <workzone> INNER JOIN  <logbook_tasklist> ON  <workzone.id> = <tasklist.workzone_id> WHERE (lower(<workzone.name>) LIKE lower( :workzonename ) ) AND <tasklist.state> != :state "


/*"SELECT <workzone.name> ,COUNT (<tasklist.id>) FROM <workzone> , <logbook_tasklist> WHERE (lower(<workzone.name>) LIKE :workzonename ) AND <tasklist.state> != :state AND <workzone.id> = <tasklist.workzone_id>" , [
		":workzonename" => "%customer%",
		":state" => 1
*/
?>
