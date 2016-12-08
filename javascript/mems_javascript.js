/*******************************/
/**********General**************/
/*******************************/

/// This function is included in many browsers, but most notably not some version of IE.
/// @return The position of the first occurrence of a specified value in a string.
if (!Array.prototype.indexOf){
	Array.prototype.indexOf = function(elt /*, from*/){
		var len = this.length >>> 0;

		var from = Number(arguments[1]) || 0;
		from = (from < 0) ? Math.ceil(from) : Math.floor(from);
		if(from < 0)
			from += len;

		for (; from < len; from++){
			if (from in this &&
			this[from] === elt)
			return from;
		}
		return -1;
	};
}

$(document).ready(function(){
	// Since IE does not handle the hover CSS attribute very well, the following
	//  should resolve any issues.
	$("li.drop").hover(
		function(){
			$(this).children("ul").css("display", "block");
		},
		function(){
			$(this).children("ul").css("display", "none");
	});

	// This object is used by all qTip instances, but only needs to be included once
	$('<div id="qtip-blanket">').css({
		position: 'absolute',
		top: $(document).scrollTop(), // Use document scrollTop so it's on-screen even if the window is scrolled
		left: 0,
		height: $(document).height(), // Span the full document height...
		width: '100%', // ...and full width
		opacity: 0.7, // Make it slightly transparent
		backgroundColor: 'black',
		zIndex: 5000  // Make sure the zIndex is below 6000 to keep it below tooltips!
	}).appendTo(document.body) // Append to the document body
	  .hide(); // Hide it initially
});

/*******************************/
/**********Form Editor**********/
/*******************************/

/// Applies the draggable jQuery UI attribute to an object
function applyDraggable(object){
	$(object).draggable({
		snap: true,
		//connectToSortable: "#formContainer",
		containment: "parent"
	});
}

/// Visibly marks a field as selected.  Also marks it in such a way that it can be easily
///  found and accessed.
function applySelected(object){
	$(object).css("background-color", "tan");
	$(object).addClass("SelectedField");
}

/// Clears the form fields that describe the selected field
function clearSelected(){
	$("#moduleFieldName").val("");
	$("#moduleFieldName").css("display", "none");
	$("#moduleFieldID").css("display", "block");
	$("#deleteFormField").css("display", "none");

	$("#mutable").attr("checked", "");
	$("#includeLabel").attr("checked", "");
	$("#width").val("");
	$("#height").val("");

	$("#updateFormField").children().text("Create New");
}

/// Sets-up everything related to the form editor
function initFormEditor(){
	$("#formContainer").resizable({
		containment: 'parent',
		grid: [20, 28]
	});

	// All fields that are already on the form should be made draggable
	$("#formContainer > div").each(function() {
		applyDraggable($(this));
	});

	$.data(document.body, "lastSelect", (new Date()).getTime());
	$(".FormElement").live('click', function(){
		// For some reason this event is triggered twice, so the following
		//  forces it to only occur once per click
		if($.data(document.body, "lastSelect") + 50 > (new Date()).getTime())
			return ;
		$.data(document.body, "lastSelect", (new Date()).getTime());
		
		var isSelected = $(this).hasClass("SelectedField");
		$(".FormElement").each(function(){
			$(this).css("background-color", "white");
			$(this).removeClass("SelectedField");
		});
		if(isSelected == false){
			applySelected($(this));

			$("#moduleFieldName").css("display", "block");
			$("#moduleFieldID").css("display", "none");

			// Some Form Fields are not removable (such as Username)
			if($(this).data("removable") == "1")
				$("#deleteFormField").css("display", "block");
			else
				$("#deleteFormField").css("display", "none");

			var moduleFieldName = $(this).data("moduleFieldName");
			$("#moduleFieldName").val(moduleFieldName);

			var mutable = $(this).data("mutable");
			$("#mutable").attr("checked", (mutable == "1" ? "checked" : ""));

			var includeLabel = $(this).children(".FieldLabel");
			includeLabel = includeLabel.text().length > 0 ? true : false;
			$("#includeLabel").attr("checked", (includeLabel == "1" ? "checked" : ""));

			var width = $(this).children(".FieldContent").css("width");
			$("#width").val(width.slice(0, -2));

			var height = $(this).children(".FieldContent").css("height");
			$("#height").val(height.slice(0, -2));

			$("#updateFormField").children().text("Update");
		}
		else
			clearSelected();
	});

	$("#deleteFormField").button();
	$("#deleteFormField").click(function(){
		var selectedField = $(".SelectedField");
		var moduleFieldID = selectedField.data("moduleFieldID");
		var moduleFieldName = selectedField.data("moduleFieldName");
		
		selectedField.remove();
		clearSelected();

		// Add ModuleID back to list
		$("#moduleFieldID").append($("<option value='" + moduleFieldID + "'>" + moduleFieldName + "</option>"));
	});

	// Note: we use the same button for updating existing fields and creating new fields,
	//  we just change the text of the button
	$("#updateFormField").button();
	$("#updateFormField").click(function(){
		var selectedField = $(".SelectedField");

		var top = null;
		var left = null;
		var moduleFieldID = null;
		var formFieldID = null;
		// If we have just chosen to create a new field
		if(selectedField.length === 0){
			top = "0px";	// We add 'px' here so we don't have to have a special case later on
			left = "0px";
			moduleFieldID = $("#moduleFieldID").val();
			if(moduleFieldID == null)
				return ;
			formFieldID = "0";
		}
		else{
			top = selectedField.css("top");
			left = selectedField.css("left");
			moduleFieldID = selectedField.data("moduleFieldID");
			formFieldID = selectedField.data("formFieldID");
		}

		$.ajax({
			type: "POST",
			async: false,
			url: "lib/editors/FormEditorAJAX.php",
			data: {
				"command": "formFieldHTML",
				"moduleFieldID": moduleFieldID,
				"height": $("#height").val(),
				"width": $("#width").val(),
				"left": Math.abs(left.slice(0, -2)),
				"top": Math.abs(top.slice(0, -2)),
				"mutable": $("#mutable").attr("checked") ? "1" : "0",
				"formFieldID": formFieldID,
				"includeLabel": $("#includeLabel").attr("checked") ? "1" : "0"
			},
			success: function(msg){
				if(msg == "")
					alert("An error has occurred");
				else{
					var newField = $(msg);
					if(selectedField.length !== 0)	// If we are updating an existing field
						selectedField.replaceWith(newField);
					else{ // Creating a new field
						$("#formContainer").append(newField);
						$("#moduleFieldID").children("[value=" + moduleFieldID + "]").remove();
					}

					clearSelected();	// it's easier if we just clear everything after doing anything
					applyDraggable(newField);
				}
		   }
		 });
	});

	$("#saveForm").button();
	$("#saveForm").click(function(){
		var formFields = new Array();
		
		$(".FormElement").each(function(){
			var dataEntry = {};
			var selectedField = $(this);

			dataEntry['top'] = selectedField.css("top").slice(0, -2);
			dataEntry['left'] = selectedField.css("left").slice(0, -2);
			dataEntry['width'] = selectedField.children(".FieldContent").css("width").slice(0, -2);
			dataEntry['height'] = selectedField.children(".FieldContent").css("height").slice(0, -2);
			dataEntry['moduleFieldID'] = selectedField.data("moduleFieldID");
			dataEntry['formFieldID'] = selectedField.data("formFieldID");
			dataEntry['mutable'] = selectedField.data("mutable");
			dataEntry['includeLabel'] = selectedField.data("includeLabel");
			formFields.push(json_encode(dataEntry));
		});
		
		$.ajax({
			type: "POST",
			async: false,
			url: "lib/editors/FormEditorAJAX.php",
			data: {
				"command": "submitForm",
				"formID": jQuery.data(document.body, "formID"),
				"data": encodeURIComponent(json_encode(formFields))
			},
			success: function(msg){
				if(msg != "")
					alert("An error has occurred: " + msg);
				else{
					alert("Changes submitted successfully");
					var currURL = document.URL;
					var componentList = currURL.split('?');
					window.location.href = componentList[0];
				}
		   }
		 });
	});
}

/// Creates the New Form button and its resulting dialog on the Form list page
function formEditorScript(){
	$(".DeleteFormLink").click(function(){
		if(confirm("Are your sure you wish to delete this Form?")){
			var moduleID = $(this).parent().parent().parent().attr("id");
			var formID = $(this).attr("id").substr(7);
			$.ajax({
				type: "POST",
				async: false,
				url: "lib/editors/FormEditorAJAX.php",
				data: {
					"command": "deleteForm",
					"formID": formID,
					"moduleID": moduleID
				},
				success: function(msg){
					if(msg != "")
						alert("An error has occurred while deleting this form: " + msg);
			   }
			});
		}
	});

	$('.EditFormSettings').each(function(){
		var formID = $(this).attr("id");
		$(this).qtip({
			content: {
				title: {
					text: 'Edit Form',
					button: 'Cancel'
				},
				url: 'lib/editors/FormEditorAJAX.php',
				data: {
					'command': 'editFormDialog',
					'formID': formID
				},
				method: 'post'
			},
			position: {
				target: $(document.body),
				corner: 'center'
			},
			show: {
				when: 'click'
			},
			hide: false,
			style: {
				width: {
					min: 300,
					max: 300
				},
				padding: '14px',
				border: {
					width: 9,
					radius: 9,
					color: '#666666'
				},
				title: {
					'font-size': '12pt'
				},
				name: 'light',
				'font-size': '12pt'
			},
			api: {
				beforeShow: function(){
					$('#qtip-blanket').fadeIn(this.options.show.effect.length);
				},
				beforeHide: function(){
					$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
				}
			}
		});
	});

	$("#newFormButton").button();
	$('#newFormButton').qtip({
		content: {
			title: {
				text: 'Create new Form',
				button: 'Cancel'
			},
			url: 'lib/editors/FormEditorAJAX.php',
			data: {
				'command': 'newFormDialog'
			},
			method: 'post'
		},
		position: {
			target: $(document.body),
			corner: 'center'
		},
		show: {
			when: 'click'
		},
		hide: false,
		style: {
			width: {
				min: 300,
				max: 300
			},
			padding: '14px',
			border: {
				width: 9,
				radius: 9,
				color: '#666666'
			},
			title: {
				'font-size': '12pt'
			},
			name: 'light',
			'font-size': '12pt'
		},
		api: {
			beforeShow: function(){
				$('#qtip-blanket').fadeIn(this.options.show.effect.length);
			},
			beforeHide: function(){
				$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
			}
		}
	});
}

/// Submits the general attributes of a Form to the server/DB, requires there
///  to be the proper fields IDs on the page
function submitFormScript(submitButtonID, formID){
	// Restrict the user intput to the text fields
	//  Essentially it replaces any character not in the approved list with an empty string
	jQuery('#formName').keyup(function () {
		this.value = this.value.replace(/[^a-zA-Z]/g,'');
	});
	jQuery('#formTitle').keyup(function () {
		this.value = this.value.replace(/[^a-z A-Z]/g,'');
	});
	jQuery('#forceLogin').keyup(function () {
		this.value = this.value.replace(/[^0-9]/g,'');
	});
	
	$("#" + submitButtonID).button();
	$("#" + submitButtonID).click(function(){
		$.ajax({
			type: "POST",
			async: false,
			url: "lib/editors/FormEditorAJAX.php",
			data: "command=submitFormSettings&formName=" + $("#formName").val()
					+ "&formDesc=" + $("#formDesc").val()
					+ "&moduleID=" + $("#moduleID").val()
					+ "&formTitle=" + $("#formTitle").val()
					+ "&forceLogin=" + $("#forceLogin").val()
					+ "&formID=" + formID,
			success: function(msg){
				if(msg == ""){
					alert("Submission Successful");
					location.reload(true);
				}
				else
					alert("There was an error completing your request: " + msg);
		   }
		 });
	 });
}

/*******************************/
/*********Listing Editor********/
/*******************************/

$(document).ready(function(){
	$(".ListOptionUpdate").button();
	$("#NewListByButton").button();
	$("#DeleteListByButton").button();
	$("#NewFilterButton").button();
	$("#DeleteFilterButton").button();
	$("#list_create_link").button();
});

/// Submits the general attributes of a Listing to the server/DB, requires there
///  to be the proper fields IDs on the page
function submitListingScript(submitButtonID, formID){
	$("#" + submitButtonID).button();
	$("#" + submitButtonID).click(function(){
		$.ajax({
			type: "POST",
			async: false,
			url: "lib/editors/ListingEditorAJAX.php",
			data: "command=submitListingSettings&listingName=" + $("#listingName").val()
					+ "&listingTitle=" + $("#listingTitle").val()
					+ "&moduleID=" + $("#moduleID").val()
					+ "&newEntryPageName=" + $("#newEntryPageName").val()
					+ "&maxItems=" + $("#maxItems").val()
					+ "&createText=" + $("#createText").val()
					+ "&listingDesc=" + $("#listingDesc").val()
					+ "&forceLogin=" + $("#forceLogin").val(),
			success: function(msg){
				if(msg == ""){
					alert("Submission Successful");
					location.reload(true);
				}
				else
					alert("There was an error completing your request: " + msg);
		   }
		 });
	 });
}

function listingEditorScript() {
	$(".EditListingButton").button();
	$(".CreateListingButton").button();
	$('.CreateListingButton').qtip({
		content: {
			title: {
				text: 'Create new Listing',
				button: 'Cancel'
			},
			url: 'lib/editors/ListingEditorAJAX.php',
			data: {
				'command': 'newListingDialog'
			},
			method: 'post'
		},
		position: {
			target: $(document.body),
			corner: 'center'
		},
		show: {
			when: 'click'
		},
		hide: false,
		style: {
			width: {
				min: 300,
				max: 300
			},
			padding: '14px',
			border: {
				width: 9,
				radius: 9,
				color: '#666666'
			},
			title: {
				'font-size': '12pt'
			},
			name: 'light',
			'font-size': '12pt'
		},
		api: {
			beforeShow: function(){
				$('#qtip-blanket').fadeIn(this.options.show.effect.length);
			},
			beforeHide: function(){
				$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
			}
		}
	});
}

function editListing() {
	var listingID = $('#listingSelect :selected').val();
	window.location = "ListingEditor.php?listingID="+listingID;
}

function newListBy(moduleID) {
	var divs = $('#list_options_by').find("div");
	var counter = 1;
	divs.each( function () {
		counter++;
	});
	$.ajax({
		type: "POST",
		url: "lib/editors/ListingEditorAJAX.php",
		data: "command=newListBy&moduleID="
		+ moduleID + "&rank="
		+ counter,
		success: function(msg){
			if(msg.indexOf("##") === 0 || msg === "") {
				alert(msg.substring(2));
			}
			else {
				$('#list_options_by').append("<div>"+msg+"</div>");
			}
		}
	});
}

function newListFilter(moduleID) {
	var divs = $('#list_options_filter').find("div");
	var counter = 1;
	divs.each( function () {
		counter++;
	});
	$.ajax({
		type: "POST",
		url: "lib/editors/ListingEditorAJAX.php",
		data: "command=newListFilter&moduleID="
		+ moduleID + "&rank="
		+ counter,
		success: function(msg){
			if(msg.indexOf("##") === 0 || msg === "") {
				alert(msg.substring(2));
			}
			else {
				$('#list_options_filter').append("<div id='filter_div_"+counter+"'>"+msg+"</div>");
			}
		}
	});
}

function removeLastListBy(listingID) {
	var divs = $('#list_options_by').find("div");
	var counter = 0;
	divs.each( function () {
		counter++;
	});
	if(counter > 1) {
		$.ajax({
			type: "POST",
			url: "lib/editors/ListingEditorAJAX.php",
			data: "command=removeListBy&listingID="
			+ listingID + "&rank="
			+ counter,
			success: function(msg){
				if(msg) {
					alert(msg);
				}
				else {
					$('#list_options_by').find("div").last().remove();
				}
			}
		});
	}
}

function removeLastFilter(listingID) {
	var div = $('#list_options_filter').find("div").last();
	var options = div.find(":input").serializeArray();
	var divs = $('#list_options_filter').find("div");
	var counter = 0;
	divs.each( function () {
		counter++;
	});
	if(counter > 1) {
		$.ajax({
			type: "POST",
			url: "lib/editors/ListingEditorAJAX.php",
			data: "command=removeLastFilter&listingID="
			+ listingID + "&filterField="
			+ options[0].value,
			success: function(msg){
				if(msg) {
					alert(msg);
				}
				else {
					$('#list_options_filter').find("div").last().remove();
				}
			}
		});
	}
}

function loadModulePages() {
	var moduleID = $("#moduleID :selected").val();
	$.ajax({
			type: "POST",
			async: false,
			url: "lib/editors/ListingEditorAJAX.php",
			data: "command=loadOptions&moduleID=" + moduleID,
			success: function(msg){
				if(msg != ""){
					$("#newEntryPageName").html(msg);
				}
				else
					alert("Could not get pages for module: " + moduleID);
		   }
		 });
}

function submitListSortsAndFilters(listingID) {
	//Sortings
	var divs = $('#list_options_by').find("div");
	var result = true;
	var counter = 0;
	divs.each( function () {
		counter++;
		$.ajax({
			type: "POST",
			url: "lib/editors/ListingEditorAJAX.php",
			data: "command=saveSortingOptions&sortField="
			+ $(this).find('select[name$="field"]').val() + "&sortDirection="
			+ $(this).find('input:checked[name$="dir"]').val() + "&sortType="
			+ $(this).find('input[name$="type"]').attr('checked') + "&sortOrder="
			+ $(this).find('input:checked[name$="or"]').val() + "&rank="
			+ counter + "&listingID="
			+ listingID,
			success: function(msg){
				if(msg) {
					alert(msg);
					result = false;
				}
				else {
					result = result && true;
				}
			}
		});
	});

	//Filters
	//Warning: It was decided not to support multiple filters on the same field
	//so previous updates to the same filterField will be overwritten
	var divs = $('#list_options_filter').find("div");
	divs.each( function () {
		var options = $(this).find(":input").serializeArray();
		$.ajax({
			type: "POST",
			url: "lib/editors/ListingEditorAJAX.php",
			data: "command=saveFilterOptions&listingID="
				+ listingID + "&filterField="
				+ options[0].value + "&filterValue="
				+ options[1].value,
			success: function(msg){
				if(msg) {
					alert(msg);
					result = false;
				}
				else {
					result = result && true;
				}
			}
		});
	});
	if(result) {
		alert("Sucessfully submitted sorting and filtering settings");
	}
}

function submitListFields(listingID){
	var divs = $('.columnOptions');
	var result = true;
	divs.each( function () {
			if($(this).find('input[name="includeField"]').attr('checked')) {
				$.ajax({
					type: "POST",
					async: false,
					url: "lib/editors/ListingEditorAJAX.php",
					data: "command=saveFieldOptions&moduleFieldID="
					+ $(this).find('input[name="moduleFieldID"]').val() + "&position="
					+ $(this).find('input[name="position"]').val() + "&width="
					+ $(this).find('input[name="width"]').val() + "&includeLabel="
					+ $(this).find('input[name="includeLabel"]').attr('checked') + "&listingID="
					+ listingID + "&linkPageName="
					+ $(this).find('select[name="link"]').val(),
					success: function(msg){
						if(msg) {
							alert(msg);
							result = false;
						}
						else
							result = result && true;
					}
				});
			}
			else {
				$.ajax({
					type: "POST",
					url: "lib/editors/ListingEditorAJAX.php",
					data: "command=deleteFieldOptions&moduleFieldID="
					+ $(this).find('input[name="moduleFieldID"]').val() + "&listingID="
					+ listingID,
					success: function(msg){
						if(msg) {
							alert(msg);
							result = false;
						}
						else
							result = result && true;
					}
				});
			}
	});
	if(result) {
		alert("Sucessfully submitted list field settings");
	}
}

/*******************************/
/**********Module Editor********/
/*******************************/

$(document).ready(function(){
	var sPath = window.location.pathname;
	var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);
	if(sPage == "ModuleCreator.php")
		replaceChangeEvents();
	
	$("#accordion").accordion({
		active: false,
		collapsible: true,
		autoHeight: false
	});

	$(".DeleteModuleButton").button();
	$(".CreateFieldButton").button();
	$(".ModuleSaveAll").button();
	$(".EditModuleButton").button();
	$(".CreateModuleButton").button();
});

function newModule(root, moduleName) {
	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=newModule&moduleName="
		+moduleName,
		success: function(msg){
			if(msg) {
				alert(msg);
			}
			else {
				window.location = root+"?moduleName="+moduleName;
			}
		}
	});
}

function redirectToModule(root, moduleFieldID) {
	var module = $("#field_"+moduleFieldID).val();
	window.location = root+"?moduleName="+module;
}

function clearAndReload() {
	var currURL = document.URL;
	var componentList = currURL.split('?');
	window.location.href = componentList[0];
}

function newModule(root, moduleName) {
	if(moduleName != null) {
		$.ajax({
			type: "POST",
			url: "lib/editors/ModuleCreatorAJAX.php",
			data: "command=newModule&moduleName=" + moduleName,
			success: function(msg){
				if(msg === "")
					window.location = root+"?moduleName="+moduleName;
				else
					alert(msg);
		   }
		});
	}
}

function reloadForm(formID, moduleID, moduleName, moduleFieldID) {
	var div = $("div[id='form_" + formID + "_" + moduleID + "_" + moduleName + "_" + moduleFieldID + "']");
	var child = $("div[id='form_" + formID + "_" + moduleID + "_" + moduleName + "_" + moduleFieldID + "'] select[name='Type']");
	var selected = child.val();

	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=reloadForm&formID=" + formID +
			"&moduleID=" + moduleID +
			"&moduleName=" + moduleName +
			"&moduleFieldID=" + moduleFieldID +
			"&newType=" + selected,
		success: function(msg){
			if(msg === ""){
			}
			else{
				div.html(msg);
				replaceChangeEvents();
			}
	   }
	});
}

function replaceChangeEvents() {
		var select = $("select[name=Type]");
		select.each(function() {
			var form = $(this).parents("form");
			var div = form.parents("div");
			var id = div.attr('id');
			var tokens = id.split("_");
			$(this).unbind("change");
			$(this).change(function () {reloadForm(tokens[1], tokens[2], tokens[3], tokens[4]);} );
		});
}

function addOption(moduleFieldID) {
	var options = $("#field_options_" + moduleFieldID);
	options.append("<div style='float:left;padding-right:5px;' ><div class='FieldLabel' ><label>Option Label</label></div>&nbsp;<input name='OptionLabel'" +
					" type='text' /></div>" +
					"<div style='float:left;' ><div class='FieldLabel' ><label>Option Value</label></div>&nbsp;<input name='OptionValue'" +
					" type='text' /></div><div style='clear:both' ></div>");
}

function deleteOption(moduleFieldID) {
	var selected = $("#field_options_" + moduleFieldID + " :selected");
	var label = selected.text();
	var val = selected.val();
	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=deleteOption&moduleFieldID=" + moduleFieldID +
			"&label=" + label +
			"&value=" + val,
		success: function(msg){
			if(msg === "1") {
				selected.remove();
			}
			else {
				$('#main').qtip({
				  content: "Failed to delete option",
				  position: {
					  target: $(document.body), // Position it via the document body...
					  corner: 'center' // ...at the center of the viewport
				   },
				   show: {
					  when: false, // Don't specify a show event
					  ready: true // Show the tooltip when ready
				   },
				   hide: {when: {event: 'unfocus'}},
				   style: {
					  'font-size': 32,
					  border: {
						 width: 5,
						 radius: 10
					  },
					  padding: 10,
					  textAlign: 'center',
					  name: 'cream'
				   },
				   api: {
					 beforeShow: function(){
						$('#qtip-blanket').fadeIn(this.options.show.effect.length);
					 },
					 beforeHide: function(){
						$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					 }
				   }
			   });
			}
	   }
	});
}

function deleteModule(moduleID) {
	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=deleteModule&moduleID=" + moduleID,
		success: function(msg){
			if(msg === "1"){
			}
			else {
				$('#main').qtip({
				  content: "Failed to delete module",
				  position: {
					  target: $(document.body), // Position it via the document body...
					  corner: 'center' // ...at the center of the viewport
				   },
				   show: {
					  when: false, // Don't specify a show event
					  ready: true // Show the tooltip when ready
				   },
				   hide: {when: {event: 'unfocus'}},
				   style: {
					  'font-size': 32,
					  border: {
						 width: 5,
						 radius: 10
					  },
					  padding: 10,
					  textAlign: 'center',
					  name: 'cream'
				   },
				   api: {
					 beforeShow: function(){
						$('#qtip-blanket').fadeIn(this.options.show.effect.length);
					 },
					 beforeHide: function(){
						$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					 }
				   }
			   });
			}
	   }
	});
}

function deleteModuleField(moduleFieldID) {
	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=deleteModuleField&moduleFieldID=" + moduleFieldID,
		success: function(msg){
			if(msg === "1"){
				var form = $('form[id$="moduleField_'+moduleFieldID+'"]');
				var div = form.parent();
				var header = div.prev();
				header.remove();
				div.remove();
			}
			else{
				$('#main').qtip({
				  content: "Failed to delete field",
				  position: {
					  target: $(document.body), // Position it via the document body...
					  corner: 'center' // ...at the center of the viewport
				   },
				   show: {
					  when: false, // Don't specify a show event
					  ready: true // Show the tooltip when ready
				   },
				   hide: {when: {event: 'unfocus'}},
				   style: {
					  'font-size': 32,
					  border: {
						 width: 5,
						 radius: 10
					  },
					  padding: 10,
					  textAlign: 'center',
					  name: 'cream'
				   },
				   api: {
					 beforeShow: function(){
						$('#qtip-blanket').fadeIn(this.options.show.effect.length);
					 },
					 beforeHide: function(){
						$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					 }
				   }
			   });
			}
	   }
	});
}

function newModuleField(moduleCreatorID, editModuleID, moduleName) {
	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=newModuleField&moduleCreatorID=" + moduleCreatorID +
			"&editModuleID=" + editModuleID +
			"&moduleName=" + moduleName,
		success: function(msg){
			if(msg === ""){
				$('#main').qtip({
				  content: "Failed to create new field",
				  position: {
					  target: $(document.body), // Position it via the document body...
					  corner: 'center' // ...at the center of the viewport
				   },
				   show: {
					  when: false, // Don't specify a show event
					  ready: true // Show the tooltip when ready
				   },
				   hide: {when: {event: 'unfocus'}},
				   style: {
					  'font-size': 32,
					  border: {
						 width: 5,
						 radius: 10
					  },
					  padding: 10,
					  textAlign: 'center',
					  name: 'cream'
				   },
				   api: {
					 beforeShow: function(){
						$('#qtip-blanket').fadeIn(this.options.show.effect.length);
					 },
					 beforeHide: function(){
						$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					 }
				   }
			   });
			}
			else{
				var moduleFieldID = msg.toString().substring(0, msg.toString().indexOf("#", 0));
				var html = msg.toString().substring(msg.toString().indexOf("#", 0) + 1);
				var header = "<h3><a href='#'>New Field</a></h3><div id='form_" +
					moduleCreatorID + "_" + editModuleID + "_" + moduleName + "_" + moduleFieldID + "'>" +
					html + "</div>";
				$("#accordion").append(header).accordion('destroy').accordion({active: false, collapsible: true});
				replaceChangeEvents();
			}
	   }
	});
}

function redirectToModule(root, moduleFieldID) {
	var module = $("#field_"+moduleFieldID).val();
	window.location = root+"?moduleName="+module;
}

function moduleCreatorPageSubmit(moduleID) {
	$('#moduleCreatorPageForm').children().attr('disabled', 'disabled');
	var msg = "Success";
	$.ajax({
		type: "POST",
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=saveModuleName&moduleName=" + $('#moduleName').val() +
			"&moduleID=" + moduleID,
		success: function(msg){
			if(msg != "") {
				jQuery.data(document.body, "submitResult", false);
			}
			else {
				msg = "Success";
				jQuery.data(document.body, "submitResult", true);

				var forms = $('.moduleCreatorForm');
				forms.each(function() {
					$(this).submit();
				});
			}
			$('#moduleCreatorPageForm').children().attr('disabled', '');
			$('<div id="qtip-blanket">')
					  .css({
						 position: {adjust: {scroll: true}},
						 top: $(document).scrollTop(), // Use document scrollTop so it's on-screen even if the window is scrolled
						 left: 0,
						 height: $(document).height(), // Span the full document height...
						 width: '100%', // ...and full width

						 opacity: 0.7, // Make it slightly transparent
						 backgroundColor: 'black',
						 zIndex: 5000  // Make sure the zIndex is below 6000 to keep it below tooltips!
					  })
					  .appendTo(document.body) // Append to the document body
					  .hide(); // Hide it initially
					  
				if(jQuery.data(document.body, "submitResult"))
				   msg = "Submit successful";
				else
					msg = "Error submitting some fields";

				$('#footer').qtip({
				  content: msg,
				  position: {
					  target: $(document.body), // Position it via the document body...
					  corner: 'center' // ...at the center of the viewport
				   },
				   show: {
					  when: false, // Don't specify a show event
					  ready: true // Show the tooltip when ready
				   },
				   hide: {when: {event: 'unfocus'}},
				   style: {
					  'font-size': 32,
					  border: {
						 width: 5,
						 radius: 10
					  },
					  padding: 10,
					  textAlign: 'center',
					  name: 'cream'
				   },
				   api: {
					 beforeShow: function(){
						$('#qtip-blanket').fadeIn(this.options.show.effect.length);
					 },
					 beforeHide: function(){
						$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					 }
				   }
			   });
		}
	});
}

function moduleCreatorFormSubmit(formID, moduleID, moduleName, moduleFieldID){
	var mcfIDArray = [];
	var idArray = [];
	var valArray = [];

	var allInputs = $('#form_'+formID+'_moduleField_'+moduleFieldID+' :input[type!="submit"][id^="modulefield_"]');

	allInputs.each(function() {
		mcfIDArray.push($(this).attr("id").toString().substr($(this).attr("id").toString().lastIndexOf('field_') + 6));
		idArray.push($(this).attr("name").toString());
		if(jQuery.isArray($(this).val()))	// Select Multiple may have multiple values
			valArray.push($(this).val().join("##"));
		else
			valArray.push($(this).val());
	});

	var optionsIdArray = [];
	var optionsValArray = [];

	var allOptions = $('#field_options_'+moduleFieldID+' :input[type!="submit"]');

	allOptions.each(function() {
		optionsIdArray.push($(this).attr("name").toString());
		if($(this).attr("type") == "checkbox"){
			optionsValArray.push($(this).context.checked)
		}
		else if(jQuery.isArray($(this).val()))	// Select Multiple may have multiple values
			optionsValArray.push($(this).val().join("##"));
		else
			optionsValArray.push($(this).val());
	});

	$.ajax({
		type: "POST",
		async: false,
		url: "lib/editors/ModuleCreatorAJAX.php",
		data: "command=submitModule&formID="
				+ formID + "&moduleID="
				+ moduleID + "&moduleName="
				+ moduleName + "&moduleFieldID="
				+ moduleFieldID + "&mcfIDs="
				+ encodeURIComponent(json_encode(mcfIDArray)) + "&ids="
				+ encodeURIComponent(json_encode(idArray)) + "&values="
				+ encodeURIComponent(json_encode(valArray)) + "&optionIDs="
				+ encodeURIComponent(json_encode(optionsIdArray)) + "&optionValues="
				+ encodeURIComponent(json_encode(optionsValArray)),
		success: function(msg){
			//if(msg == "1"){
				//alert("Module fields submitted successfully");
				//clearAndReload();
			//}
			var msgArray = msg.split("##");

			var form = $('form[id$="moduleField_'+moduleFieldID+'"]');
			var div = form.parent();
			var header = div.prev();

			for(i = 1; i < msgArray.length; i++)
				$('#modulefield_'+moduleFieldID+'_field_'+msgArray[i]).parents('.FormElement').css('background-color', 'red');

			if(msgArray.length > 1) {
				header.children('a').css('color', 'red');
				jQuery.data(document.body, "submitResult", false);
			}
			else {
				header.children('a').css('color', '');
				var previousResult = jQuery.data(document.body, "submitResult");
				jQuery.data(document.body, "submitResult", previousResult && true);
			}
	   }
	});
}

/*******************************/
/**********Uploads**************/
/*******************************/

/// Creates the HTML code used to show file that has already been uploaded
function downloadRowEntry(fileName, moduleFieldID){
	var moduleInstanceID = jQuery.data(document.body, "moduleInstanceID");
	
	var newRow = $('<tr></tr>');
	var deleteButton = $('<td><span class="ui-icon ui-icon-trash"></span></td>');
	deleteButton.mouseover(function () {
		$(this).css('cursor', 'pointer');
	});
	deleteButton.click(function() {
		if(!confirm("Are you sure you wish to delete '" + fileName + "'?"))
			return ;
		$.ajax({
			type: "POST",
			url: "lib/fieldAJAX/UploadAJAX.php",
			data: "command=delete&moduleFieldID=" + moduleFieldID +
					"&moduleInstanceID=" + moduleInstanceID +
					"&fileName=" + fileName,
			success: function(msg){
				if(msg == "Success"){
					setTextField(moduleFieldID, "", fileName);
					$(".FileName").each(function(){
						if($(this).text() == fileName)
							$(this).parent().remove();
					});
				}
				else
					alert(msg);
		   }
		});
	});
	newRow.append(deleteButton);
	newRow.append($('<td class="FileName">' + fileName + '</td>'));
	return newRow;
}

/// Designed to be used when the upload window first appears, we want to show
///  any files that have already been uploaded in its list
function initDownloadRowEntries(currentValue, moduleFieldID){
	var values = new Array();
	if(currentValue != "")
		values = currentValue.split('##');
	
	$.each(
		values,
		function( intIndex, objValue ){
			$('#downloadTable').append(downloadRowEntry(objValue, moduleFieldID))
		}
	);
}

/// Adds or removes a filename in the form text field
function setTextField(moduleFieldID, addFileName, removeFileName){
	// Update the file name textbox in the form to reflect the new files
	var currentText = $("#field_" + moduleFieldID).val();

	var values = new Array();
	if(currentText != "")
		values = currentText.split('##');

	if(addFileName != "")
		values.push(addFileName)
	if(removeFileName != ""){
		var removeIndex = values.indexOf(removeFileName);
		if(removeIndex != -1)
			values.splice(removeIndex, 1);
	}

	$("#field_" + moduleFieldID).val(values.join("##"));
}

/// Applies the upload UI to the modal window
function applyUploadUI(formID, moduleFieldID, moduleInstanceID){
	$('#' + formID).fileUploadUI({
		uploadTable: $('#uploadTable'),
		downloadTable: $('#downloadTable'),
		dragDropSupport: false,
		url: 'lib/fieldAJAX/UploadAJAX.php',
		method: 'POST',
		formData: [
			{
				name: 'command',
				value: 'upload'
			},
			{
				name: 'moduleFieldID',
				value: moduleFieldID
			},
			{
				name: 'moduleInstanceID',
				value: moduleInstanceID
			}
		],
		buildUploadRow: function(files, index) {
			var newFile = files[index];
			return $('<tr><td>' + newFile.name + '</td>' +
					'<td class="file_upload_progress"><div></div></td>' +
					'<td class="file_upload_cancel">' +
					'<button class="ui-state-default ui-corner-all" title="Cancel">' +
					'<span class="ui-icon ui-icon-cancel">Cancel</span>' +
					'</button></td></tr>');
		},
		buildDownloadRow: function(newFile) {
			if(newFile.error != ""){
				alert(newFile.error);
				return null;
			}
			setTextField(moduleFieldID, newFile.name, "");
			return downloadRowEntry(newFile.name, moduleFieldID);
		}
	});
}

/// Creates the modal window
function fileUploadWindow(moduleFieldID, moduleFieldLabel, currentValue){
	var moduleInstanceID = jQuery.data(document.body, "moduleInstanceID");
	var windowContent =
	$('#field_' + moduleFieldID).qtip({
		content: {
			title: {
				text: moduleFieldLabel,
				button: 'Close'
			},
			url: 'lib/fieldAJAX/UploadAJAX.php',
			data: {
				'command': 'modalHTML',
				'moduleFieldID': moduleFieldID,
				'moduleInstanceID': moduleInstanceID,
				'currentValue': currentValue
			},
			method: 'post'
		},
		position: {
			target: $(document.body),
			corner: 'center'
		},
		show: {
			when: 'click'
		},
		hide: false,
		style: {
			width: {
				min: 500,
				max: 500
			},
			padding: '14px',
			border: {
				width: 9,
				radius: 9,
				color: '#666666'
			},
			title: {
				'font-size': '12pt'
			},
			name: 'light',
			'font-size': '12pt'
		},
		api: {
			beforeShow: function(){
				$('#qtip-blanket').fadeIn(this.options.show.effect.length);
			},
			beforeHide: function(){
				$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
			}
		}
	});
}

/*******************************/
/**********Forms****************/
/*******************************/

/// Initializes Form actions and buttons
function initForm(){
	// If we click on the Add/drop button 
	$(".SelectMemberField").button();
	$(".SelectMemberField").click(function(){
		var moduleInstanceID = $.data(document.body, "moduleInstanceID");
		var moduleFieldID = $(this).parent().parent().parent().data("moduleFieldID");

		if(confirm("Confirm: " + $(this).children().text())){
			$.ajax({
				type: "POST",
				async: false,
				url: "lib/fieldAJAX/SelectMemberAJAX.php",
				data: {
					"command": "toggle",
					"moduleInstanceID": moduleInstanceID,
					"moduleFieldID": moduleFieldID
				},
				success: function(msg){
					if(msg == "")
						history.back();
					else
						alert(msg);
				}
			});
		}
	});

	// All fields that are validatable have one of the following two css classes
	//  since we want to check for a different event for text entry vs selects
	$(".ValidateField").live('blur', function(){
		checkField($(this));
	});
	$(".ValidateSelect").live('change', function(){
		checkField($(this));
	});
	

	$("#newRegButton").button();

	$("#forgotPWButton").button();
	$("#forgotPWButton").click(function(){
		// TODO: Need a create a small form that asks the user for their email address
		//  Send it to AJAX, and if it matches, reset the password and email the new one
	});

	$(".FormSubmit").button();

	$(".FormDelete").button();
	$(".FormDelete").click(function(){
		deleteConfirm($.data(document.body, "moduleInstanceID"), $.data(document.body, "moduleID"));
	});

	$("#instancePrivDialogButton").button({
		icons: {
			primary:'ui-icon-unlocked',
			secondary:'ui-icon-transferthick-e-w'
		}
	});

	$("#instancePrivDialogButton").qtip({
		content: {
			title: {
				text: 'Set Instance Privileges',
				button: 'Close'
			},
			url: 'lib/security/PermissionsAJAX.php',
			data: {
				'command': 'instancePrivDialog',
				'moduleInstanceID': $.data(document.body, "moduleInstanceID"),
				'moduleID': $.data(document.body, "moduleID")
			},
			method: 'post'
		},
		position: {
			target: $(document.body),
			corner: 'center'
		},
		show: {
			when: 'click'
		},
		hide: false,
		style: {
			width: {
				min: 300,
				max: 500
			},
			padding: '14px',
			border: {
				width: 9,
				radius: 9,
				color: '#666666'
			},
			title: {
				'font-size': '12pt'
			},
			name: 'light',
			'font-size': '12pt'
		},
		api: {
			beforeShow: function(){
				$('#qtip-blanket').fadeIn(this.options.show.effect.length);
			},
			beforeHide: function(){
				$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
			}
		}
	});
}

/// While most forms use another function for submission, log-in uses a specialized
///  version that only sends the username and password.
/// TODO: Add a "Remember Me" checkbox
function submitLogin(formID, moduleInstanceID){
	var username = $('input[name="UserName"]').val();
	var password = $('input[name="Password"]').val();
	
	$.ajax({
		type: "POST",
		async: false,
		url: "lib/sessions/LogonAJAX.php",
		data: {
			"command": "login",
			"username": username,
			"password": password
		},
		success: function(msg){
			if(msg == "1"){
				location.reload(true);
			}
			else{
			   $("#main").qtip({
				  content: "Logon unsuccessful",
				  position: {
					  target: $(document.body), // Position it via the document body...
					  corner: 'center' // ...at the center of the viewport
				   },
				   show: {
					  when: false, // Don't specify a show event
					  ready: true // Show the tooltip when ready
				   },
				   hide: {when: {event: 'unfocus'}},
				   style: {
					  'font-size': 32,
					  border: {
						 width: 5,
						 radius: 10
					  },
					  padding: 10,
					  textAlign: 'center',
					  name: 'cream' // Style it according to the preset 'cream' style
				   },
				   api: {
					 beforeShow: function(){
						$('#qtip-blanket').fadeIn(this.options.show.effect.length);
					 },
					 beforeHide: function(){
						$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					 }
				   }
			   });
			}
	   }
	});
}

/// Sends the current field value to the DB for validation
function checkField(moduleField){
	var moduleFieldID = $(moduleField).parent().parent().data("moduleFieldID");

	// If there are multiple input objects with the same ModuleFieldID, ensure
	//	they all have the same value.  For example, if we want to change the password,
	//  and include two password fields for confirmation.
	var name = $(moduleField).attr("name");
	var inputsWithName = $(":input[name=" + name + "]");

	var result = true;
	var firstValue = inputsWithName.first().val();
	inputsWithName.each(function() {
		var curValue = $(this).val();
		if(curValue != firstValue){
			// PENDING: Create the proper error tooltip
			return false;
		}
	});

	if(jQuery.isArray(firstValue))	// ComboBoxes(and others) may have multiple values
		firstValue = firstValue.join("##");
	
	$.ajax({
		type: "POST",
		url: "lib/form/FormAJAX.php",
		data: {
			"command": "checkFieldValue",
			"moduleFieldID": moduleFieldID,
			"value": firstValue
		},
		success: function(msg){
			if(msg == "")
				$(moduleField).parent().parent().css('background-color', '');
			else{
				// TODO: Add a tooltip with the error string
				$(moduleField).parent().parent().css('background-color', 'tomato');
			}
	   }
	});
}

/// Submits a form by gathering all of the field IDs and data, and sending it
///  off to the server
function formSubmit(formID, moduleInstanceID){
	var idArray = [];
	var valArray = [];

	var allInputs = $(':input[type!="submit"][id^="field_"]');
	allInputs.each(function() {
		if($(this).attr("type") == "checkbox"){
			idArray.push($(this).attr("id").toString().substr(6));
			valArray.push($(this).context.checked)
		}
		else{
			// .substr(6) will remove the "field_" prefix
			idArray.push($(this).attr("id").toString().substr(6));
			if(jQuery.isArray($(this).val()))	// ComboBoxes may have multiple values
				valArray.push($(this).val().join("##"));
			else
				valArray.push($(this).val());
		}
	});

	$.ajax({
		type: "POST",
		async: false,
		url: "lib/form/FormAJAX.php",
		data: {
			"command": "submit",
			"formID": formID,
			"moduleInstanceID": moduleInstanceID,
			"ids": json_encode(idArray),
			"values": json_encode(valArray)
		},
		success: function(msg){
			$('<div id="qtip-blanket">')
				  .css({
					 position: 'absolute',
					 top: $(document).scrollTop(), // Use document scrollTop so it's on-screen even if the window is scrolled
					 left: 0,
					 height: $(document).height(), // Span the full document height...
					 width: '100%', // ...and full width

					 opacity: 0.7, // Make it slightly transparent
					 backgroundColor: 'black',
					 zIndex: 5000  // Make sure the zIndex is below 6000 to keep it below tooltips!
				  })
				  .appendTo(document.body) // Append to the document body
				  .hide(); // Hide it initially

			var result = "";
			if(msg == "Success")
				result = "Submit successful";
			else{
				var msgArray = msg.split("##");

				for(i = 1; i < msgArray.length; i++) {
					$('#div_formfield_modulefield_'+msgArray[i]).css('background-color', 'tomato');

					// TODO: Actually display the error messages resulting from form submission errors
//					$('#div_formfield_modulefield_'+msgArray[i]).qtip({
//						content: 'Some basic content for the tooltip'
//					});
				}

				result = msgArray[0];
			}
			$('#form_'+formID+' :submit').qtip({
			  content: result,
			  position: {
				  target: $(document.body),
				  corner: 'center'
			   },
			   show: {
				  when: false,
				  ready: true
			   },
			   hide: {when: {event: 'unfocus'}},
			   style: {
				  'font-size': 32,
				  border: {
					 width: 5,
					 radius: 10
				  },
				  width: {max: 1000},
				  padding: 10,
				  textAlign: 'center',
				  name: 'cream'
			   },
			   api: {
				 beforeShow: function(){
					$('#qtip-blanket').fadeIn(this.options.show.effect.length);
				 },
				 beforeHide: function(){
					$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
					if(msg == "Success")
						history.go(-1);
				 }
			   }
		   });
	   }
	});
}

/*******************************/
/**********Listings*************/
/*******************************/

$(document).ready(function(){
	$(".ListOptionUpdate").button();
	$("#list_create_link").button();
});

/// When the user changes the field in a filter drop-down menu, we want to grab
///  the right html field to show them to enter a value
function changeFilterField(filtercount, newModuleFieldID){
	$.ajax({
		type: "POST",
		url: "lib/Listing/ListAJAX.php",
		data: {
			"command": "filter_change",
			"newMFID": newModuleFieldID,
			"filterCount": filtercount,
			"moduleID": $.data(document.body, "moduleID")
		},
		success: function(msg){
			if(msg.substr(0, 6) == "Error:")
				alert(msg);
			else{
				$("#filter_div_" + filtercount).empty();
				$("#filter_div_" + filtercount).html(msg);
			}
		}
	});
}

/// Collects all of the list-by and list-filter entries, and adds them as URL
///  parameters.  Then reloads the page.
function submitListOptions(){
	var count = 1;
	var options = $("#list_options_form").serialize();

	currURL = document.URL;
	componentList = currURL.split('?');
	window.location.href = componentList[0] + "?" + options;
}

function goToPage(pageName, moduleInstanceID, moduleID){
	currURL = document.URL;
	componentList = currURL.split('?');
	window.location.href = componentList[0] + "?Page=" + pageName + "&MIID=" + moduleInstanceID + "&MID=" + moduleID;
}

/// Confirmation to delete a module instance.  Possible avenues to get here include
///  using the ListOption drop-down option, or clicking the "Delete" button with a form
function deleteConfirm(miid, moduleID){
	if(confirm("Are you sure you wish to delete this entry?")){
		$.ajax({
			type: "POST",
			async: false,
			url: "lib/Listing/ListAJAX.php",
			data: "command=deleteMI&moduleInstanceID=" + miid
					+ "&moduleID=" + moduleID,
			success: function(msg){
				if(msg == "")
					alert("Deletion completed successfully");
				else
					alert("There was an error completing your request: " + msg);
		   }
		 });
	}
}

/// Shows or hides the options div that appears above the Listing
/// @param time How long we want the effect to last for
function showHideOptions(time){
	$("#list_options").toggle("blind", function() {
		filterDateRangePickers();	// We need to call this after the picker is visible, otherwise the offset will be wrong
	}, time);
}

/// We need to initialize all currently visible date-range-pickers (it won't work
///  if they are hidden).
function filterDateRangePickers(){
	var elements = $('.DateRangePicker:visible');	//[display!="none"]
	elements.each(function(){
		var parentID = $(this).parent().attr("id");
		var grandparentID = $(this).parent().parent().attr("id");
		var appendTo = "";
		var divName = "default";
		var regex = /^filter_div_(.*)$/;

		if(regex.exec(parentID)){
			appendTo = parentID;
			divName = RegExp.$1;
		}
		else if(regex.exec(grandparentID)){
			appendTo = grandparentID;
			divName = RegExp.$1;
		}
		else
			return false;

		$(this).daterangepicker( {
			posX: $(this).offset().left - 50,
			arrows: true,
			appendTo: '#filter_div_0',
			name: divName
		});
	});
}

/**********************************/
/**********Permissions*************/
/**********************************/

/// After a user creates a new instance privilege, we want to add an entry to the
///  current list of privileges.  That entry is created here.
function appendInstancePrivEntry(instancePrivID, userName, roleName){
	var newEntry = $("<tr id='IPEntry_" + instancePrivID + "'><td>" + userName
		+ "</td><td>" + roleName
		+ "</td><td align='center'><div class='ui-icon ui-icon-trash DeleteButton' id='delete_" + instancePrivID + "'></div></td></tr>");
	$("#instancePrivList").append(newEntry);

	var deleteButton = $("#delete_" + instancePrivID);
	deleteButton.mouseover(function () {
		$(this).css('cursor', 'pointer');
	});
	deleteButton.click(function() {
		var instancePrivID = $(this).attr("id").substr(7);
		$.ajax({
			type: "POST",
			async: false,
			url: "lib/security/PermissionsAJAX.php",
			data: "command=deleteInstancePrivilege&instancePrivilegeID=" + instancePrivID
					+ "&ModuleID=" + $.data(document.body, "moduleID"),
			success: function(msg){
				if(msg == "")
					$("#IPEntry_" + instancePrivID).remove();
				else
					alert("An error has occured: " + msg);
			}
		});
	});
}

/// Dialog to create new or delete existing instance privileges
function initInstancePrivDialog(){
	$("#newInstancePriv").button();
	$("#newInstancePriv").click(function(){
		if($("#roleSelect").val() == null || $("#userSelect").val() == null)
			return ;
		var userName = $("#userSelect :selected").text();
		var roleName = $("#roleSelect :selected").text();

		var userID = $("#userSelect").val();
		var roleID = $("#roleSelect").val();

		$.ajax({
			type: "POST",
			async: false,
			url: "lib/security/PermissionsAJAX.php",
			data: "command=submitInstancePrivilege&userMIID=" + userID +
					"&roleID=" + roleID + 
					"&moduleInstanceID=" + $.data(document.body, "moduleInstanceID") +
					"&moduleID=" + $.data(document.body, "moduleID"),
			success: function(msg){
				// If msg is a number, then that is the new instanceprivID, otherwise an error has occurred
				var regex = /^[0-9]{1,10}$/;
				if(regex.exec(msg)){
					var instancePrivID = msg;

					appendInstancePrivEntry(instancePrivID, userName, roleName);
				}		
				else
					alert("An error has occurred: " + msg);
		   }
		});
	});
}

/// Initializes the various special buttons and fields throughout the privilege tabs,
///	 such as the button to submit the general privileges
function initPrivilegeForms(roleID){
	$("#tabs").tabs();

	$("#submitGenPrivButton").button();
	$("#submitGenPrivButton").click(function(){
		var data = "";
		$('#genPrivTable input:checkbox:checked').each(function(){
			data += $(this).attr("id") + "=" + $(this).val() + "&";
		});
		data = data.substring(0, data.length - 1);

		$.ajax({
			type: "POST",
			async: false,
			url: "lib/security/PermissionsAJAX.php",
			data: "command=submitGenPriv&roleID=" + roleID + "&" + data,
			success: function(msg){
				if(msg == "")
					alert("Changes accepted");
				else
					alert("There was an error completing your request: " + msg);
		   }
		});
	});

	$(".SubmitModulePrivButton").each(function(){$(this).button();});
	$(".SubmitModulePrivButton").each(function(){
		var moduleID = $(this).attr("id").toString().substring(11);
		$(this).click(function(){
			var data = "";
			$('#priv_form_' + moduleID + ' input:checkbox:checked').each(function(){
				data += $(this).attr("id") + "=" + $(this).val() + "&";
			});
			data = data.substring(0, data.length - 1);

			$.ajax({
				type: "POST",
				async: false,
				url: "lib/security/PermissionsAJAX.php",
				data: "command=submitModulePriv&roleID=" + roleID + 
					"&moduleID=" + moduleID + "&" + data,
				success: function(msg){
					if(msg == "")
						alert("Changes accepted");
					else
						alert("There was an error completing your request: " + msg);
			   }
			});
		});
	});

	submitRoleScript('submitRoleSettingsButton', false, roleID);
}

/// Submits the general attributes of a Role to the server/DB, requires there
///  to be the proper fields IDs on the page
function submitRoleScript(submitButtonID, returnOnSuccess, roleID){
	$("#" + submitButtonID).button();
	$("#" + submitButtonID).click(function(){
		var newRoleName = $("#roleName").val()
		var newRoleDesc = $("#roleDesc").val()
		$.ajax({
			type: "POST",
			async: false,
			url: "lib/security/PermissionsAJAX.php",
			data: "command=submitRole&roleName=" + newRoleName
					+ "&roleDesc=" + newRoleDesc
					+ "&roleID=" + roleID,
			success: function(msg){
				if(msg == ""){
					if(returnOnSuccess){
						currURL = document.URL;
						componentList = currURL.split('?');
						window.location.href = componentList[0];
					}
					else{
						alert("Changes successful");
						location.reload(true);
					}
				}
				else
					alert("There was an error completing your request: " + msg);
		   }
		 });
	 });
}

/// Creates the New Role button on the Permissions page
function newRoleButton(){
	$("#newRoleButton").button();
	$('#newRoleButton').qtip({
		content: {
			title: {
				text: 'Create new Role',
				button: 'Cancel'
			},
			text: '<form onsubmit="$(\'#newRoleSubmitButton\').click(); return false;">' +
				'<label for="roleName">Enter the new role Name:</label>' +
				'<input type="text" size="30" maxlength="20" id="roleName" /><br/><br/>' +
				'<label for="roleDesc">Description (optional):</label>' +
				'<textarea type="text" rows="3" cols="30" id="roleDesc" /><br/><br/>' +
				'<div id="newRoleSubmitButton">Submit</div>' +
				'</form>' +
				'<script type="text/javascript">submitRoleScript("newRoleSubmitButton", true, "");</script>'
		},
		position: {
			target: $(document.body),
			corner: 'center'
		},
		show: {
			when: 'click'
		},
		hide: false,
		style: {
			width: {
				min: 300,
				max: 300
			},
			padding: '14px',
			border: {
				width: 9,
				radius: 9,
				color: '#666666'
			},
			title: {
				'font-size': '12pt'
			},
			name: 'light',
			'font-size': '12pt'
		},
		api: {
			beforeShow: function(){
				$('#qtip-blanket').fadeIn(this.options.show.effect.length);
			},
			beforeHide: function(){
				$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
			}
		}
	});
}

/// Some general items that need to be set-up for privileges
// TODO: Probably should put most/all of this into an init() function.
$(document).ready(function(){
	jQuery('#editRoleName').keyup(function () {
		this.value = this.value.replace(/[^a-zA-Z\.]/g,'');
	});

	$(".DeleteRoleLink").click(function(){
		if(confirm("Are your sure you wish to delete this Role?")){
			var roleID = $(this).attr("id").substr(7);
			$.ajax({
				type: "POST",
				async: false,
				url: "lib/Security/PermissionsAJAX.php",
				data: "command=deleteRole&roleID=" + roleID,
				success: function(msg){
					if(msg != "")
						alert("An error has occurred while deleting this role: " + msg);
			   }
			 });
		}
	});

	$("#activeRoleButton").button({
		icons: {
			primary:'ui-icon-locked ',
			secondary:'ui-icon-unlocked'
		}
	});
	$("#activeRoleButton").qtip({
		content: {
			title: {
				text: 'Set Active Roles',
				button: 'Save'
			},
			url: 'lib/security/PermissionsAJAX.php',
			data: {
				'command': 'activeRoleDialog'
			},
			method: 'post'
		},
		position: {
			target: $(document.body),
			corner: 'center'
		},
		show: {
			when: 'click'
		},
		hide: false,
		style: {
			width: {
				min: 300,
				max: 500
			},
			padding: '14px',
			border: {
				width: 9,
				radius: 9,
				color: '#666666'
			},
			title: {
				'font-size': '12pt'
			},
			name: 'light',
			'font-size': '12pt'
		},
		api: {
			beforeShow: function(){
				$('#qtip-blanket').fadeIn(this.options.show.effect.length);
			},
			beforeHide: function(){
				var data = [];
				$(".ActiveRoleToggle").each(function(){
					data += "&" + $(this).attr("id") + "=" + $(this).text();
				});
				console.log(data);
				$.ajax({
					type: "POST",
					url: "lib/Security/PermissionsAJAX.php",
					data: "command=submitActiveRoles" + data,
					success: function(msg){
						if(msg != "")
							alert("An error has occurred while setting your active roles, please try again.\n"+
								"If you continue to have trouble, contact your administrator." + msg);
				   }
				 });
				$('#qtip-blanket').fadeOut(this.options.hide.effect.length);
			}
		}
	});
});