<?php

require_once('config.php');
	
class BOSH {

	private $rid;
	private $sessionid;
	private $use_session;
	
	public function __construct ($use_session = false) {
		$this->use_session = $use_session;
		
		if ($use_session) {
			session_start();
		}
		
		if (
				$use_session && 
				array_key_exists('time', $_SESSION) && 
				time() - $_SESSION['time'] < 30 &&
				array_key_exists('rid', $_SESSION)
			) {
			$this->rid = $_SESSION['rid'];
			$this->sessionid = $_SESSION['sessionid'];
		} else {
			$this->rid = 700400;
			$this->sessionid = false;

			$xml = $this->_get_base_xml();

			$output = $this->_curl_it($xml);

			$rxml = new SimpleXMLElement($output);
			$this->sessionid = (string)$rxml['sid'];

			$jid = XMPP_USER_NAME . '@' . XMPP_SERVER;
			$auth = base64_encode($jid . chr(0) . XMPP_USER_NAME . chr(0) . XMPP_USER_PASSWORD);

			$xml = $this->_get_base_xml();

			$plain = $xml->addChild('auth', $auth);
			$plain->addAttribute('xmlns', 'urn:ietf:params:xml:ns:xmpp-sasl');
			$plain->addAttribute('mechanism', 'PLAIN');

			$this->_curl_it($xml);

			$xml = $this->_get_base_xml();

			$iq = $xml->addChild('iq');
			$iq->addAttribute('type', 'set');
			$iq->addAttribute('id', 'bind_2');
			$bind = $iq->addChild('bind');
			$bind->addAttribute('xmlns', 'urn:ietf:params:xml:ns:xmpp-bind');
			$resource = $bind->addChild('resource', 'phpapi');

			$this->_curl_it($xml);

			$xml = $this->_get_base_xml();

			$iq = $xml->addChild('iq');
			$iq->addAttribute('to', XMPP_SERVER);
			$iq->addAttribute('type', 'set');
			$iq->addAttribute('id', 'sess_1');
			$session = $iq->addChild('session');
			$session->addAttribute('xmlns', 'urn:ietf:params:xml:ns:xmpp-session');

			$this->_curl_it($xml);
			
			if ($this->use_session) {
				$_SESSION['sessionid'] = $this->sessionid;
			}
		}
	}
	
	public function __call ($name, $arguments) {
		// use magic functions for a nicer external interface and simpler internal structures
		$valid = ['setSwitch', 'setSwitchGroup', 'setDimmer', 'setShutter', 'setShutterGroup', 'setScene'];
		if (in_array($name, $valid)) {
			if ($arguments[0] === NULL || $arguments[1] === NULL || $arguments[2] === NULL) {
				throw new Exception('parameters may not be NULL: ' . $arguments[0] . ' / ' . $arguments[1] . ' / ' . $arguments[2]);
			}
			$this->_setter($name, $arguments[0], $arguments[1], $arguments[2]);
		} else {
			throw new Exception('unknown method: ' . $name);
		}
	}
	
	// internal functions
	private function _setter ($type, $actuator, $channel, $command) {
		$commands = [
			'setSwitch' => [
				'on' => [
					'value' => 1, 
					'io' => 'i', 
					'dp' => 0,
				],
				'off' => [
					'value' => 0, 
					'io' => 'i', 
					'dp' => 0,
				],
			],
			'setSwitchGroup' => [
				'on' => [
					'value' => 1, 
					'io' => 'o', 
					'dp' => 2,
				],
				'off' => [
					'value' => 0, 
					'io' => 'o', 
					'dp' => 2,
				],
			],
			'setDimmer' => [
				'on' => [
					'value' => 1, 
					'io' => 'i', 
					'dp' => 0,
				],
				'off' => [
					'value' => 0, 
					'io' => 'i', 
					'dp' => 0,
				],
				'up' => [
					'value' => 9, 
					'io' => 'i', 
					'dp' => 1,
				],
				'down' => [
					'value' => 1, 
					'io' => 'i', 
					'dp' => 1,
				],
				'stop' => [
					'value' => 0, 
					'io' => 'i', 
					'dp' => 1,
				],
			],
			'setShutter' => [
				'up' => [
					'value' => 0, 
					'io' => 'i', 
					'dp' => 0,
				],
				'down' => [
					'value' => 1, 
					'io' => 'i', 
					'dp' => 0,
				],
				'stop' => [
					'value' => 1, 
					'io' => 'i', 
					'dp' => 1,
				],
			],
			'setShutterGroup' => [
				'up' => [
					'value' => 0, 
					'io' => 'o', 
					'dp' => 3,
				],
				'down' => [
					'value' => 1, 
					'io' => 'o', 
					'dp' => 3,
				],
				'stop' => [
					'value' => 1, 
					'io' => 'o', 
					'dp' => 4,
				],
			],
			'setScene' => [
				'set' => [
					'value' => 1, 
					'io' => 'o', 
					'dp' => 0,
				],
			],
		];
		if (!in_array($command, array_keys($commands[$type]))) {
			throw new Exception($type . ' command is unknown: ' . $command);
		}
		$this->_setValue(
			$actuator, 
			$channel, 
			$commands[$type][$command]['io'], 
			$commands[$type][$command]['dp'], 
			$commands[$type][$command]['value']
		);
	}
	
	private function _setValue ($actuator, $channel, $io, $connection, $value) {
		$target = $actuator . '/ch' . str_pad($channel, 4, '0', STR_PAD_LEFT) . '/' . $io . 'dp' . str_pad($connection, 4, '0', STR_PAD_LEFT);
		
		$xml = $this->_get_base_xml();
	
		$iq = $xml->addChild('iq');
		$iq->addAttribute('xmlns', 'jabber:client');
		$iq->addAttribute('to', 'mrha@busch-jaeger.de/rpc');
		$iq->addAttribute('type', 'set');
		$iq->addAttribute('id', time());
	
		$query = $iq->addChild('query');
		$query->addAttribute('xmlns', 'jabber:iq:rpc');
	
		$methodCall = $query->addChild('methodCall');
	
		$methodName = $methodCall->addChild('methodName', 'RemoteInterface.setDatapoint');
		$params = $methodCall->addChild('params');
	
		$param1 = $params->addChild('param');
		$value1 = $param1->addChild('value');
		$string1 = $value1->addChild('string', $target);
	
		$param2 = $params->addChild('param');
		$value2 = $param2->addChild('value');
		$string2 = $value2->addChild('string', $value);
	
		$this->_curl_it($xml);
	}
	
	private function _get_base_xml () {
		$xml = new SimpleXMLElement("<body xmlns='http://jabber.org/protocol/httpbind' xmlns:xmpp='urn:xmpp:xbosh' />");
		$xml->addAttribute('content', 'text/xml; charset=utf-8');
		$xml->addAttribute('rid', $this->rid);
		$xml->addAttribute('xml:lang', 'en');
		$xml->addAttribute('hold','1');
		$xml->addAttribute('to', XMPP_SERVER);
		$xml->addAttribute('route', 'xmpp:' . XMPP_SERVER . ':' . XMPP_HOST_PORT);
		$xml->addAttribute('secure','true');
		$xml->addAttribute('xmpp:version','1.6', 'urn:xmpp:xbosh');
		$xml->addAttribute('wait', 60);
		$xml->addAttribute('ack','1');
		$xml->addAttribute('xmlns:xmpp','urn:xmpp:xbosh');

		if ($this->sessionid !== false) {
			$xml->addAttribute('sid', $this->sessionid);
		}

		$this->rid++;
		if ($this->use_session) {
			$_SESSION['rid'] = $this->rid;
			$_SESSION['time'] = time();
		}

		return $xml;
	}
	
	private function _curl_it ($body) {
		$header = array('Content-Type: text/xml; charset=utf-8');

		$connection = curl_init(XMPP_HTTP_URL);

		curl_setopt($connection, CURLOPT_HEADER, 0);
		curl_setopt($connection, CURLOPT_POST, 1);
		curl_setopt($connection, CURLOPT_POSTFIELDS, $body->asXML());
		curl_setopt($connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($connection, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($connection, CURLOPT_VERBOSE, 0);
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($connection, CURLOPT_TIMEOUT, 60);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);

		$output = curl_exec($connection);
		curl_close($connection);

		// you don't want to know
		// it walks like utf8, it talks like utf8, but it is a huge cURL-created mess
		$output = print_r($output, true);

		return $output;
	}
	
}

?>