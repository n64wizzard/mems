<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/page/Page.php");
	require_once("lib/Security/Security.php");
	require_once("lib/fieldtypes/SelectField.php");
		
	class PermissionPage{
		/// Creates the content for some moduleID and Role to be used in a module Tab
		public static function moduleTab($moduleID, $role){
			$pageContents = <<<EOD
<form id='priv_form_$moduleID' onsubmit='submitmodulePriv(\"{$role->roleID()}\", \"$moduleID\"); return false;'>
<h4>Module Privileges</h4>
<table id='ModulePrivTable'>
EOD;

			foreach(ModulePrivilege::tasks() as $task => $description){
				$isChecked = ($role->privilege(new ModulePrivilege($task, $moduleID))) ? "checked='checked'" : "";
				$pageContents .= "<tr><td>$description</td><td><input id='{$moduleID}_{$task}' type='checkbox' $isChecked/></td></tr>\n";
			}
			$pageContents .= <<<EOD
</table><br/>
<h4>Module Field Privileges</h4>
<table id='priv_table_$moduleID' class='MFPrivTable'>
<tr><th class="FieldName">Field Name</th>
EOD;
			foreach(ModuleFieldPrivilege::tasks() as $task => $label)
				$pageContents .= "<th>$label</th>\n";
			$pageContents .= "</tr>\n";
			$module = Module::createModule($moduleID);

			foreach($module->moduleFields() as $moduleFieldID => $moduleField){
				if($moduleField->hidden()) continue;
				$pageContents .= <<<EOD
<tr>
	<td class="FieldName">{$moduleField->name()}</td>
EOD;
				foreach(ModuleFieldPrivilege::tasks() as $task => $label){
					$enabled = ($role->privilege(new ModuleFieldPrivilege($task, $moduleFieldID))) ? "checked='checked'" : "";
					$pageContents .= "<td><input id='{$moduleFieldID}_$task' type='checkbox' $enabled/></td>\n";
				}
				
				$writeEnabled = ($role->privilege(new ModuleFieldPrivilege("Write", $moduleFieldID))) ? "checked='checked'" : "";
				$pageContents .= "<tr/>\n";
			}
			$pageContents .= <<<EOD
</table><br/>
<div id='submitPriv_$moduleID' class="SubmitModulePrivButton">Save</div>
&nbsp;&nbsp;&nbsp;<a href='Permissions.php'>Return</a>
</form>
EOD;
			return $pageContents;
		}
		
		/// Creates the HTML content to be used in the General Privileges Tab
		private static function generalTab($role){
			$pageContents = "<form id='GenPriv' onsubmit='submitGenPriv(\"{$role->roleID()}\"); return false;'>\n";
			$pageContents .= "<table id='genPrivTable'>\n";

			foreach(GeneralPrivilege::tasks() as $task => $description){
				$isChecked = ($role->privilege(new GeneralPrivilege($task))) ? "checked='checked'" : "";
				$pageContents .= "<tr><td>$description</td><td><input id='$task' type='checkbox' $isChecked/></td></tr>\n";
			}
			$pageContents .= <<<EOD
</table><br/>
<div id='submitGenPrivButton'>Save</div>
&nbsp;&nbsp;&nbsp;<a href='Permissions.php'>Cancel and Return</a>
</form>
EOD;
			return $pageContents;
		}
		
		/// Creates the HTML content to be used in the Role Settings tab
		private static function roleSettings($role){
			$pageContents = <<<EOD
<form id='roleSettings' onsubmit='submitroleSettings("{$role->roleID()}"); return false;'>
<table id='roleSettingsTable'>
	<tr valign="top">
		<td><label for="roleName">Role Name:</label></td>
		<td><input type="text" size="30" maxlength="20" id="roleName" value="{$role->roleName()}"/></td>
	</tr>
	<tr valign="top">
		<td><label for="roleDesc">Description (optional):</label></td>
		<td><textarea type="text" rows="3" cols="60" id="roleDesc">{$role->description()}</textarea></td>
	</tr>
</table><br/>
<div id='submitRoleSettingsButton'>Save</div>
&nbsp;&nbsp;&nbsp;<a href='Permissions.php'>Cancel and Return</a>
</form>
EOD;
			return $pageContents;
		}

		/// Function creates an HTML string that will show a list of general
		///	 permissions and check-boxes to enable/disable them
		public static function permissionsPage($roleID){
			$role = Role::createRole($roleID);
			$additionalTabs = array("Settings", "General");

			$pageContents = "<div id='tabs'>\n";
			$pageContents .= "<ul>\n";
			foreach($additionalTabs as $tabName)
				$pageContents .= "<li><a href='#tabs-$tabName'>$tabName</a></li>\n";
			foreach(Module::moduleNames() as $moduleID =>$moduleName)
				$pageContents .= "<li><a href='#tabs-$moduleID'>Module: $moduleName</a></li>\n";
			$pageContents .= "</ul>\n";

			$generalTabContents = self::generalTab($role);
			$settingsTabContents = self::roleSettings($role);
			$pageContents .= <<<EOD
<div id='tabs-Settings'>
$settingsTabContents
</div>
<div id='tabs-General'>
$generalTabContents
</div>
EOD;

			foreach(Module::moduleNames() as $moduleID => $moduleName){
				$moduleTabContent = self::moduleTab($moduleID, $role);
				$pageContents .= "<div id='tabs-$moduleID'>\n$moduleTabContent</div>\n";
			}
			$pageContents .= "<script type='text/javascript'>initPrivilegeForms('$roleID');</script>";
			$newPage = new Page("Role: {$role->roleName()}", "Priv", $pageContents, 600);
			return $newPage;
		}

		/// Function creates an HTML string that will show a list of user roles,
		///  and links to pages that allow one to modify the permissions associated
		///  with a module or the role in general.
		public static function roleListPage(){
			$pageContents = "<div id='roleList'>";
			foreach(Security::roleList(true) as $roleID => $roleName){
				$role = Role::createRole($roleID, false);

				$editLink = Security::privilege(new GeneralPrivilege("EditRole"))
							? "<a href='Permissions.php?roleID=$roleID'>Edit</a>"
							: "";
				$deleteLink = Security::privilege(new GeneralPrivilege("DeleteRole"))
							? "<div class='RoleLink'><a id='delete_$roleID' href='' class='DeleteRoleLink'>Delete</a></div>"
							: "";
				$pageContents .= <<<EOD
<div class="RoleListEntry">
<div class="RoleName">$roleName</div>
<div class='RoleLink'>$editLink</div>
<div class='RoleLink'>$deleteLink</div>
<div class='RoleDescription'>{$role->description()}</div>
</div>
EOD;
			}
			$pageContents .= "</div><br/>";
			$pageContents .= Security::privilege(new GeneralPrivilege("CreateRole"))
							 ? "<div id='newRoleButton'>Create New Role</div>"
							 : "";
			$pageContents .= "<script type='text/javascript'>newRoleButton();</script>";
			$newPage = new Page("Roles", "Roles", $pageContents, 600);
			return $newPage;
		}
	}
?>
