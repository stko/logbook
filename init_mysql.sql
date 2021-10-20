CREATE TABLE logbook_users (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
			username VARCHAR( 50 ) NOT NULL,
			firstname TEXT, 
			lastname TEXT,
			state INTEGER );
INSERT INTO logbook_users VALUES(1,'foo','Any','Nobody',1);
INSERT INTO logbook_users VALUES(2,'foo2','Alice','Smith',1);
CREATE TABLE logbook_workzone (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR( 200 ) NOT NULL );
INSERT INTO logbook_workzone VALUES(1,'customer.project');
INSERT INTO logbook_workzone VALUES(2,'customer.project.build');
INSERT INTO logbook_workzone VALUES(3,'customer.project.build.event');
CREATE TABLE logbook_tasknames (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR( 200 ) NOT NULL );
CREATE TABLE logbook_tasklist (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
			workzone_id INTEGER NOT NULL,
			tasknameid INTEGER NOT NULL,
			ownerid INTEGER NOT NULL,
			startdate INTEGER NOT NULL,
			enddate INTEGER NOT NULL,
			plannedenddate INTEGER NOT NULL,
			duration INTEGER NOT NULL,
			fulfillrate INTEGER NOT NULL,
			ismilestone INTEGER NOT NULL,
			title VARCHAR( 200 ) NOT NULL,
			content TEXT,
			validated INTEGER NOT NULL,
			state INTEGER NOT NULL);
CREATE TABLE logbook_edgelist (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
			workzone_id INTEGER NOT NULL,
			fromtaskid INTEGER NOT NULL,
			totaskid INTEGER NOT NULL,
			state INTEGER NOT NULL);
CREATE TABLE logbook_changelog (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
			taskid INTEGER NOT NULL,
			timestamp INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			taskowner INTEGER NOT NULL,
			predecessorState INTEGER NOT NULL,
			validated INTEGER NOT NULL,
			changetype INTEGER NOT NULL,
			comment TEXT,
			content TEXT,
			state INTEGER NOT NULL);
CREATE TABLE logbook_statecodes (
			id INTEGER PRIMARY KEY AUTO_INCREMENT,
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

