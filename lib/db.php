<?php

require_once('config.php');
	
class DBProvider {

	private static $provider;
	private $db;

	public static function getProvider () {
		if (!self::$provider) {
			self::$provider = new DBProvider();
		}
		
		return self::$provider;
	}

	public function getConnection() {
		if (!$this->db) {
			$this->db = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASSWORD);
		}
		
		return $this->db;
	}
	
}

class ActuatorSettings {

	private $settings;
	
	public function __construct () {
		$this->_loadSettings();
	}
	
	private function _loadSettings () {
		$dbh = DBProvider::getProvider()->getConnection();

		$data = [];
		
		$floor_id = NULL;
		$room_id = NULL;
		$floor = -1;
		$room = -1;
	
		// get favorites
		$sth = $dbh->prepare('
			SELECT
				list_name,
				room_name,
				list_type,
				actuator_sn,
				actuator_input_channel
			FROM
				favorites 
			JOIN
				lists 
					ON (
						favorites.favorite_list = lists.list_id
					) 
			JOIN
				rooms 
					ON (
						rooms.room_id = lists.list_room
					) 
			JOIN
				actuators 
					ON (
						lists.list_actuator = actuators.actuator_id
					)
			ORDER BY
				favorite_order ASC;
		');
		$sth->execute();
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$favorites = $sth->fetchAll();
		if ($favorites) {
			$floor++;
			$data[] = [
				'name' => 'Favoriten',
				'icon' => 'ion-ios-star',
				'rooms' => [
					[
						'name' => 'Favoriten',
						'actuators' => [],
					],
				],
			];
			foreach ($favorites as $favorite) {
				array_push($data[$floor]['rooms'][0]['actuators'], [
					'name' => $favorite['list_name'] . ' (' . $favorite['room_name'] . ')',
					'type' => $favorite['list_type'],
					'actuator' => $favorite['actuator_sn'],
					'channel' => $favorite['actuator_input_channel'],
				]);
			}
		}
		
		// get rooms
		$sth = $dbh->prepare('
			SELECT
				floor_id,
				floor_name,
				floor_icon,
				room_id,
				room_name,
				list_name,
				list_type,
				list_actuator,
				actuator_sn,
				actuator_input_channel
			FROM
				lists
			JOIN
				rooms 
					ON (
						rooms.room_id = lists.list_room
					) 
			JOIN
				floors 
					ON (
						floors.floor_id = rooms.room_floor
					) 
			LEFT JOIN
				actuators 
					ON (
						lists.list_actuator = actuators.actuator_id
					) 
			ORDER BY
				floor_order ASC,
				room_order ASC,
				list_order ASC;
		');
		$sth->execute();
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$items = $sth->fetchAll();

		foreach ($items as $item) {
			if ($floor_id !== $item['floor_id']) {
				$floor++;
				$data[$floor] = [
					'name' => $item['floor_name'],
					'icon' => $item['floor_icon'],
					'rooms' => []
				];
				$floor_id = $item['floor_id'];
				// new floor also means an new room
				$room_id = NULL;
				$room = -1;
			}
			if ($room_id !== $item['room_id']) {
				// new room
				$room++;
				$data[$floor]['rooms'][$room] = [
					'name' => $item['room_name'],
					'actuators' => []
				];
				$room_id = $item['room_id'];
			}
	
			$actuator = [
				'name' => $item['list_name'],
				'type' => $item['list_type']
			];
			if ($item['list_actuator']) {
				$actuator['actuator'] = $item['actuator_sn'];
				$actuator['channel'] = $item['actuator_input_channel'];
			}
			array_push($data[$floor]['rooms'][$room]['actuators'], $actuator);
		}
		$this->settings = $data;
	}
	
	public function getSettings () {
		return $this->settings;
	}
	
	public function getSettingsJSON () {
		return json_encode($this->settings);
	}
	
	public function reloadSettings () {
		$this->_loadSettings();
	}

}

?>