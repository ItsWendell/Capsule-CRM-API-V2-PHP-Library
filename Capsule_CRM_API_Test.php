<?php
/**
 * Created by PhpStorm.
 * User: WMisiedjan
 * Date: 29-9-2017
 * Time: 21:56
 */

include_once "./includes/Capsule-CRM-API.php";

$client = new Capsule_CRM_API("API KEY HERE.");

try {
	if ( $client->add_kase_tag_by_name( "TEST TAG 1.1", 1973211 ) ) {
		echo "TEST TAG 1 SUCCESSFULL!\n";
	} else {
		echo "ERROR!";
	}

	//TODO: Note that this function has changed.
	$date = date( 'Y-m-d' );
	if ( $client->create_task( "TEST TASK 1", "kase", 1973211, $date ) ) {
		echo "TEST TASKS 1 SUCCESSFULL!\n";
	} else {
		echo "ERROR!";
	}

	if ( $client->create_note( "kase", 1973211,"TEST NOTE 1" ) ) {
		echo "TEST NOTE 1 SUCCESSFULL!\n";
	} else {
		echo "ERROR!";
	}

	if ($client->add_party_to_case(1973211, 154572117)) {
		echo "ADD PARTY SUCCESSFULL!";
	} else {
		echo "HOOPS!";
	}

} catch (Exception $e) {
	print "{$e->getCode()}): {$e->getMessage()}";
}



