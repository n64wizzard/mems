<?php
	// TODO: image selecter(ie. drop-down), timepicker, radio button
	// TODO: Instead of having to list every file here, just include
	//  every file in this directory
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/fieldtypes/CaptchaField.php");
	require_once("lib/fieldtypes/DateField.php");
	require_once("lib/fieldtypes/PageInstanceLink.php");
	require_once("lib/fieldtypes/PasswordField.php");
	require_once("lib/fieldtypes/SelectEditField.php");
	require_once("lib/fieldtypes/SelectField.php");
	require_once("lib/fieldtypes/StaticField.php");
	require_once("lib/fieldtypes/TextAreaField.php");
	require_once("lib/fieldtypes/TextField.php");
	require_once("lib/fieldtypes/CheckBoxField.php");
	require_once("lib/fieldtypes/FileUploadField.php");
	require_once("lib/fieldtypes/SelectRoles.php");
	require_once("lib/fieldtypes/SelectMemberField.php");
	require_once("lib/fieldtypes/StaticImageField.php");
?>
