<?php

require_once  'TasksHandler.php';




if (!debug_backtrace()) {
	// do useful stuff
	$jh=TasksHandler::Instance();
	$jh->updateModelState(2,1);
}
?>
