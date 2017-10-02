<?php
//VERSION: V0.5
//Changelog:
// 0.2
// - Removed json_encode functions, caused error.
// 0.3
// - Added tests
// 0.4
// - Did successful tests for add notes, tasks and tags.
// - WARNING: Structure of create_task changed.
//   WARNING: Json input of update_case for custom fields changed in edited json file.
// - Bug fixes for notes, tasks, tags and update case. Custom fields feature still not tested.
// 0.5
// - Fix for kase when input of case ID is a string, now converting all case ID's to integers.
// - Fix for creating tasks, category needed to be an object of its own.
// - Task function now returns proper false or true if succeded or failed.
// 0.6
// - Add Party To Case added
// 0.7
// - Added additional party functionality as default, add ,true to make main party.

class Capsule_CRM_API {

	private $base_url = 'api.capsulecrm.com/api/v2';
	public  $last_http_code;
	private $access_token;
	private $curl_object;
	private $debugging;

	public function __construct( $api_key, $debugging = false ) {
		$this->access_token = $api_key;
		$this->debugging    = $debugging;
	}

	public function update_case( $caseId, $args ) {
		if ( is_array( $args ) ) {
			$return_data = $this->curl_request( "/kases/$caseId", 'PUT', $args );

			return isset( $return_data->kase->id );
		} else {
			print "ERROR UPDATING CASE: Data is not an array! ($args)";

			return false;
		}
	}

	public function add_party_to_case($case_id, $party_id, $main_party = false)
	{
		if($main_party == true) {
			return $this->update_case( $case_id, [
				"kase" => [
					"party" => [
						"id" => intval( $party_id )
					]
				]
			] );
		} else {
			$this->curl_request("/kases/$case_id/parties/$party_id", 'POST');
			return ($this->last_http_code == 204);
		}
	}

	public function create_note( $object_type, $object_id, $content ) {
		$api_data = $this->curl_request( "/entries", 'POST',
			[
				"entry" => [
					$object_type => [
						"id" => intval($object_id)
					],
					"type"       => "note", //only notes are supported for now.
					"content"    => $content
				]
			]
		);

		return isset( $api_data->entry->id );
	}

	public function add_kase_tag_by_name( $tag_name, $kase_id ) {
		$api_data = $this->curl_request( "/kases/$kase_id", 'PUT', [
				"kase" => [
					"tags" => [
						[
							"name" => $tag_name,
						]
					]
				]
			]
		);

		return isset( $api_data->kase->id );
	}

	public function get_tag_id( $tag_name, $object_type ) {
		$api_result = $this->curl_request( "/$object_type/tags", 'GET' );

		if ( ! empty( $api_result->tags ) ) {
			if ( count( $api_result->tags ) != 0 ) {
				foreach ( $api_result->tags as $tag ) {
					if ( $tag->name == $tag_name ) {
						return $tag->id;
					}
				}
			}
		}

		return false;
	}

	public function get_case( $caseId, $embed = null ) {
		$api_result = $this->curl_request( "/kases/$caseId", 'GET', [], [ "embed" => $embed ] );

		if ( isset( $api_result->case->id ) ) {
			return $api_result->case;
		} else {
			return false;
		}
	}

	public function create_task( $description, $object_type, $object_id, $due_on, $owner_id = null, $detail = null, $category = null, $due_time = null ) {
		$task                 = array();
		$task["description"]  = $description;
		$task[ $object_type ] = [ "id" => intval($object_id) ];
		$task["dueOn"]        = $due_on; //Required.

		( $detail ) ? $task["detail"] = $detail : "";
		( $category ) ? $task["category"] = [ "name" => $category ] : "";
		( $owner_id ) ? $task["owner"] = [ "id" => $owner_id ] : "";
		( $due_time ) ? $task["dueTime"] = $due_time : "";

		$api_data = $this->curl_request( "/tasks", 'POST',
			[
				'task' => $task
			]
		);

		return isset( $api_data->task->id );
	}

	private function initialize_curl_object() {
		$this->curl_object = curl_init( '' );
		curl_setopt( $this->curl_object, CURLOPT_RETURNTRANSFER, 1 );

		return $this->curl_object;
	}

	private function get_url_path( $alias, $params = [] ) {
		return 'https://' . preg_replace( '/\/+/', '/', "{$this->base_url}/{$alias}" ) . '?'
		       . http_build_query( $params ?: [] );
	}

	private function curl_request( $alias, $method = 'GET', array $params = [], array $urlParams = [] ) {
		$this->curl_object = $this->initialize_curl_object();
		if ( $method == 'POST' ) {
			curl_setopt( $this->curl_object, CURLOPT_POST, true );
		} else {
			curl_setopt( $this->curl_object, CURLOPT_CUSTOMREQUEST, $method );
		}
		if ( $method == 'GET' ) {
			curl_setopt( $this->curl_object, CURLOPT_URL, $this->get_url_path( $alias, $params ) );
		} else {
			curl_setopt( $this->curl_object, CURLOPT_URL, $this->get_url_path( $alias, $urlParams ) );
			curl_setopt( $this->curl_object, CURLOPT_POSTFIELDS, json_encode( $params ) );
		}

		$headers   = array();
		$headers[] = 'Authorization: Bearer ' . $this->access_token;
		$headers[] = 'Accept: application/json';
		$headers[] = 'Content-Type: application/json';

		curl_setopt( $this->curl_object, CURLOPT_AUTOREFERER, true );
		curl_setopt( $this->curl_object, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $this->curl_object, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $this->curl_object, CURLOPT_VERBOSE, 1 );
		$fp = fopen( dirname( __FILE__ ) . '/errorlog.txt', 'w' );
		curl_setopt( $this->curl_object, CURLOPT_STDERR, $fp );
		curl_setopt( $this->curl_object, CURLOPT_HTTPHEADER, $headers );

		if ( $this->debugging ) {
			print "INPUT\n\n";
			print_r( json_encode( $params ) );
			print "\n\n";
		}

		return $this->execute();
	}


	private function execute() {
		$return = curl_exec( $this->curl_object );
		$this->last_http_code = curl_getinfo($this->curl_object, CURLINFO_HTTP_CODE);
		if ( curl_errno( $this->curl_object ) ) {
			$errorNo   = curl_errno( $this->curl_object );
			$errorText = curl_error( $this->curl_object );
			throw new \Exception( "cUrl Error ({$errorNo}) : {$errorText}." );
		}
		curl_close( $this->curl_object );
		$return = json_decode( $return );

		if ( $this->debugging ) {
			print "OUTPUT\n\n";
			print_r( $return );
			print "\n\n";
		}

		return $return;
	}


}