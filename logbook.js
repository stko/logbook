'use strict';

var actualEditTaskID;
var actualWorkzoneName;
var wzOverview;
var taskTimer;
var bottomJsonEditor;
var jsonEditor;
var showValidWz;
var showValidTask;
var datepicker = $("#datepicker");


function validateEditor(values) {
	var isValid = true;
	$.each(values, function (index, value) {
		console.log(value);
		switch (typeof value) {
			case "boolean":
				if (value == false) {
					isValid = false
					console.log("fails on bool");
				}
				break;
			case "number":
				if (value == 0) {
					isValid = false
					console.log("fails on number");
				}
				break;
			case "string":
				if (value == "") {
					isValid = false
					console.log("fails on string");
				}
				break;
			default:
				isValid = false
				console.log("fails on " + (typeof value));
		}
	});
	console.log("is valid:" + isValid);
	console.log(values);
	return isValid;
}


function openEditor(taskID) {
	postIt("TasksHandler.php", { action: 5, taskID: taskID }, function (response) {
		console.log(response);
		if (response.content.schema) {

		} else {
			return;
		}
		actualEditTaskID = taskID;
		if (jsonEditor) {
			jsonEditor.destroy();
		}
		jsonEditor = new JSONEditor(document.getElementById('editTask'), {
			schema: response.content.schema,
			disable_collapse: true,
			disable_edit_json: true,
			disable_properties: true
		});
		jsonEditor.on('change', function () {
			var values = jsonEditor.getValue();
			validateEditor(values);
			$("#submitJsonEditor").prop("disabled", false);
		});
		postIt("TasksHandler.php", { action: 7, taskID: taskID }, function (response) {
			switchElementVisibility(true);
			if (response) {
				if ('isMileStone' in response) {
					taskTimer.isMileStone = response.isMileStone;
					delete response.isMileStone;
				}
			}
			taskTimer.workzoneName = actualWorkzoneName;
			jsonEditor.setValue(response);
			$("#submitJsonEditor").prop("disabled", true);
		});
	});
}

function gotoWorkZoneByName(wzName, data = null) {
	postIt("TasksHandler.php", { action: 3, wzName: wzName }, function (response) {
		wzOverview.workZoneTable = response;
	});
	showWorkZoneByName(wzName);
}

function switchElementVisibility(visible) {
	taskTimer.visible = visible;
	bottomJsonEditor.visible = visible;
}

function showWorkZoneByName(wzName) {
	postIt("TasksHandler.php", { action: 4, wzName: wzName }, function (response) {
		if (jsonEditor) {
			jsonEditor.destroy();
			switchElementVisibility(false);

		}
	});
}


function enableCreateButton() {
	var wz = $('#workzoneInput').val();
	if (wz.length < 3) {
		$("#createFlowButton").prop("disabled", true);
		return;
	}
	var task = $('#taskInput').val();
	if (task.length < 3) {
		$("#createFlowButton").prop("disabled", true);
		return;
	}
	postIt("TasksHandler.php", { action: 1, wzName: wz, taskName: task }, function (response) {
		if (response) {
			$("#createFlowButton").prop("disabled", false);
		} else {
			$("#createFlowButton").prop("disabled", true);
		}
	});
}


function postIt(url, data, success) {
	$.ajax({
		type: 'post',
		url: url,
		data: data,
		dataType: 'json'
	}).done(function (data) {
		if (data.errorcode !== 0) {
			alert(data.error);
			return;
		}

		success(data.data);
	});
}


$(function () {


	// ------------------------   Vue defines --------------------------------------

	wzOverview = new Vue({
		el: '#wzOverview',
		data: {
			workZoneTable: [{'name': 'name 1'}],
			workzoneName: "Auto"
		}
	});

	taskTimer = new Vue({
		el: '#taskTimer',
		methods: {
			installDatePicker: function () {
				$("#datepicker").change(function () {
					$("#submitJsonEditor").prop("disabled", false);
				});
				$("#duration").change(function () {
					$("#submitJsonEditor").prop("disabled", false);
				});
				$("#takeOver").click(function () {
					postIt("TasksHandler.php", { action: 11, taskID: actualEditTaskID }, function (response) {
						showWorkZoneByName(actualWorkzoneName);
						openEditor(actualEditTaskID);
					});
				});
				$("#submitJsonEditor").click(function () {
					var editorValues = jsonEditor.getValue();
					var validated = validateEditor(editorValues);
					// add values, which are not part of the Editor schema
					editorValues.isMileStone = taskTimer.isMileStone ? 1 : 0;
					editorValues.duration = taskTimer.duration;
					editorValues.endDate = Math.round(new Date(taskTimer.endDate).getTime() / 1000);

					var res = {
						"action": 6,
						"input":
						{
							"taskID": actualEditTaskID,
							"predecessorState": 0,
							"validated": validated ? 1 : 0,
							"comment": $("#commitMsg").val(),
							"content": editorValues,
							"state": validated ? 1 : 2
						}
					}
					console.log(editorValues);
					postIt("TasksHandler.php", res, function (response) {
						console.log("values saved on server");
						showWorkZoneByName(actualWorkzoneName);
					});

				});
			}
		},
		data: {
			isMileStone: true,
			duration: 20,
			endDate: new Date().toDateString(),
			visible: true,
			owner: "Klaus Mustermann",
			notMine: true,
			taskName: "",
			workzoneName: ""
		},
		mounted: function () {
			this.$nextTick(function () {
				// Code that will run only after the
				// entire view has been rendered
				this.installDatePicker();
			})
		},
		updated: function () {
			this.$nextTick(function () {
				// Code that will run only after the
				// entire view has been rendered
				this.installDatePicker();
			})
		}
	});

	bottomJsonEditor = new Vue({
		el: '#bottomeditdiv',
		data: {
			visible: true
		}
	});

	new Vue({
		el: '#app',
		vuetify: new Vuetify(),
	  })	


	$('#workzoneInput').change(function () {
		enableCreateButton();
		gotoWorkZoneByName($('#workzoneInput').val());
	});

	$("#showWorkZone").click(function () {
		gotoWorkZoneByName($('#workzoneInput').val());
	});


	// hide the elements at last, because jquery and vue can't initialize elements when they are invisible

	switchElementVisibility(false);

});

