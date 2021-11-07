-- alle fehlenden Ideen sind im Quelltext mit todo gekennzeichnet

## ChangeLoghandler

## ProposalHandler
	
    get all requests : get_request_templates("")

    get all requests containing a string : get_request_templates("ein")

	get all task proposal for a request template : get_task_proposals(3)

	create a request : create_request(1,1,"Bla")

	update the  request template : update_request_template(1,"Bla2","{}")


	create a new request template : create_request(2,"Bla3","{}")

## RequestsHandler
    assignTaskToRequest ()

    toggleFinishState
    
    removeTaskFromRequest


## RolesHandler

	add a dummy role : insert_role($actualUser["id"],99,99)

    search for that new role : get_role_id($actualUser["id"],99)

	delete again : delete_role($actualUser["id"],99,99)

## TaskHandler
    
    get taskOwnerInfo : getTaskOwnerInfo(1)

	get getRequestOwnerInfo : getRequestOwnerInfo(1)

	get countRequestsPerWorkzone : countRequestsPerWorkzone(1)

## UserHandler
	addUser : addUser("klamu","Klaus", "Mustermann")
	deactivateUser : deactivateUser("klamu")
	

## Workzones

    add a workzone : createWorkZone('BluePower','Blue Text')

	search for that new workzone : getWorkzoneID('BluePower')

	search for a not existing workzone : getWorkzoneID('GreenPower')
    

