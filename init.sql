PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;

-- do the users
CREATE TABLE logbook_users (
			id INTEGER PRIMARY KEY, 
			username VARCHAR( 50 ) NOT NULL,
			firstname TEXT, 
			lastname TEXT,
			state INTEGER ); -- set to 0 if user is not valid anymore, but not delete him to not crash the history
INSERT INTO logbook_users VALUES(1,'foo','Any','Nobody',1);
INSERT INTO logbook_users VALUES(2,'foo2','Alice','Smith',1);

-- do the roles
CREATE TABLE logbook_roles (
			id INTEGER PRIMARY KEY, 
			rolename VARCHAR( 50 ) NOT NULL );
INSERT INTO logbook_roles VALUES(1,'Service');
INSERT INTO logbook_roles VALUES(2,'Production');
INSERT INTO logbook_roles VALUES(3,'Developer');

-- do the role assignments
CREATE TABLE logbook_roles_assignments (
			id INTEGER PRIMARY KEY, 
			workzoneid INTEGER NOT NULL,
			rolenameid INTEGER NOT NULL,
			userid INTEGER NOT NULL
			);
INSERT INTO logbook_roles_assignments VALUES(1,1,1,1);
INSERT INTO logbook_roles_assignments VALUES(2,1,2,2);

-- the Workzones, 
CREATE TABLE logbook_workzone (
			id INTEGER PRIMARY KEY, 
			objname VARCHAR( 50 ) NOT NULL ,
			description VARCHAR( 200 ) 
			);
INSERT INTO logbook_workzone VALUES(1,'BlauKraft','Sample Project');

-- the requests
CREATE TABLE logbook_request_templates (
			id INTEGER PRIMARY KEY, 
			objname VARCHAR( 200 ) NOT NULL,
			scheme TEXT
			);
INSERT INTO logbook_request_templates VALUES(1,'Teil einbauen','');
INSERT INTO logbook_request_templates VALUES(2,'Teil ausbauen','');
INSERT INTO logbook_request_templates VALUES(3,'Teil tauschen','');

-- the tasks
CREATE TABLE logbook_task_templates (
			id INTEGER PRIMARY KEY, 
			objname VARCHAR( 200 ) NOT NULL,
			scheme TEXT
			);
INSERT INTO logbook_task_templates VALUES(1,'Teil eingebaut','');
INSERT INTO logbook_task_templates VALUES(2,'Teil ausgebaut','');

/*
 the request to tasks proposals

this table contains the proposals of which task could be applicable to the request
*/

CREATE TABLE logbook_task_proposals (
			id INTEGER PRIMARY KEY, 
			request_template_id  INTEGER,
			task_template_id  INTEGER
			);
INSERT INTO logbook_task_proposals VALUES(1,1,1);
INSERT INTO logbook_task_proposals VALUES(2,2,2);
INSERT INTO logbook_task_proposals VALUES(3,3,1);
INSERT INTO logbook_task_proposals VALUES(4,3,2);


/*
 the real requests, assigned to a part
*/

CREATE TABLE logbook_part_requests (
			id INTEGER PRIMARY KEY, 
			request_id  INTEGER,
			part_id  INTEGER,
			objvalue TEXT,
			request_state  INTEGER
			);
INSERT INTO logbook_part_requests VALUES(1,1,1,'',0);


/*
 the potential task, assigned to a real request
*/

CREATE TABLE logbook_request_tasks (
			id INTEGER PRIMARY KEY, 
			request_id  INTEGER,
			task_template_id  INTEGER,
			task_execution_state  INTEGER -- not is use
			);
-- INSERT INTO logbook_part_requests VALUES(1,1,1,0);

/*
 real tasks executed on a real part
*/

CREATE TABLE logbook_part_tasks (
			id INTEGER PRIMARY KEY, 
			part_id  INTEGER,
			task_template_id  INTEGER,
			objvalue TEXT
			);
-- INSERT INTO logbook_part_requests VALUES(1,1,1,0);

/*
 real parts
*/

CREATE TABLE logbook_parts (
			id INTEGER PRIMARY KEY, 
			part_basenumber  VARCHAR( 50 ) NOT NULL ,
			part_serial  VARCHAR( 50 ) NOT NULL ,
			build_into_id  INTEGER
			);
INSERT INTO logbook_parts VALUES(1,'vehicle','D1',0);
INSERT INTO logbook_parts VALUES(2,'BP21-14401-AKA','000001',1);




/*
 the change log
*/



CREATE TABLE logbook_changelog (
			id INTEGER PRIMARY KEY, 
			part INTEGER NOT NULL,
			timestamp INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			action_type INTEGER NOT NULL,
			action_reference_1 INTEGER NOT NULL,
			action_reference_2 INTEGER NOT NULL
			);

CREATE TABLE logbook_statecodes (
			id INTEGER PRIMARY KEY, 
			statename VARCHAR( 30 ) NOT NULL,
			statecolor VARCHAR( 30 ) NOT NULL,
			statecolorcode VARCHAR( 10 ) NOT NULL,
			state INTEGER NOT NULL);
INSERT INTO logbook_statecodes VALUES(1,'Requested',"Gainsboro","#DCDCDC",0);
INSERT INTO logbook_statecodes VALUES(2,'Done',"Lime","#00FF00",1);
INSERT INTO logbook_statecodes VALUES(3,'In Work',"Aqua","#00FFFF",2);
INSERT INTO logbook_statecodes VALUES(4,'Rework',"Gold","#FFD700",3);
INSERT INTO logbook_statecodes VALUES(5,'Unclear',"Orange","#FFA500",4);
INSERT INTO logbook_statecodes VALUES(6,'Faulty',"OrangeRed","#FF4500",5);
INSERT INTO logbook_statecodes VALUES(7,'Ignore',"Magenta","#FF00FF",6);
			

COMMIT;
