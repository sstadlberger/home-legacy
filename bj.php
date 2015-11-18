<?php

require_once('lib/config.php');
require_once('lib/bosh.php');

$bosh = new BOSH(true);

switch ($_GET['type']) {
	case 'setSwitch':
		$bosh->setSwitch($_GET['actuator'], $_GET['channel'], $_GET['command']);
		break;
	case 'setSwitchGroup':
		$bosh->setSwitchGroup($_GET['actuator'], $_GET['channel'], $_GET['command']);
		break;
	case 'setDimmer':
		$bosh->setDimmer($_GET['actuator'], $_GET['channel'], $_GET['command']);
		break;
	case 'setShutter':
		$bosh->setShutter($_GET['actuator'], $_GET['channel'], $_GET['command']);
		break;
	case 'setShutterGroup':
		$bosh->setShutterGroup($_GET['actuator'], $_GET['channel'], $_GET['command']);
		break;
	case 'SetScene':
		$bosh->SetScene($_GET['actuator'], $_GET['channel'], $_GET['command']);
		break;
	default:
		echo 'error: unkown set type: ' . $_GET['type'];
}
?>