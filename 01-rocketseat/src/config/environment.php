<?php

ini_set('display_errors', 'On');

const DEV_MODE = false;

/*
	Debuging Requests for validate error and result format of request. 
*/
const TEST_MODE = false;

/*
	Used to see process result of methods.
	0: Just return methods process result, not confirming nothing.
	1: Return methods process result, and confirm operation on the session.
	2: Return methods process result, confirm operation on the session, and commit it.
*/
const MODE_LEVEL = 0;


$setEnvVars = function ($envDataLocation) {
	// #### Read File
	if (!is_file($envDataLocation)) {
		return false;
	}
	ob_start();
	include $envDataLocation;
	$envDataAsString = ob_get_clean();
	$envData = json_decode($envDataAsString, true);

	// #### Set Env Vars

	foreach ($envData as $key => $value) {
		putenv("$key=$value");
	}
};

$setEnvVars('src/config/.env');
unset($setEnvVars);