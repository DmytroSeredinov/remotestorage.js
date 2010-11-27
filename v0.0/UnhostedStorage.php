<?php
define('CLOUDNAME', 'cloud.com');
require_once 'PubSign.php';
class UnhostedStorage implements PubSignBackend {
	private function parsePath($path) {
		return array('channel', 'app', 'cloud.com', 'path/to/key');
	}
	private function lockUpdate($path) {
		return 1234567000;
	}
	private function unlockUpdate($path) {
	}
	private function pathToFileName($path) {
		return '/home/michiel/unhostedStore/'.substr(base64_encode($path), 0, 64);
	}
	private function update($path, $newValue, $command) {
		$fileName = $this->pathToFileName($path);
		file_put_contents($fileName, $newValue);
		file_put_contents($fileName.'.log', json_encode($command)."\n\n", FILE_APPEND);
	}
	function process($channel, $payload, $sign){
		$command = json_decode($payload);
		switch($command->cmd) {
		case 'SET':
			list($ch, $app, $cloud, $keyPath) = $this->parsePath($command->path);
			if($cloud != CLOUDNAME) {
				return 'RELAYING DENIED';
			}
			if($ch != $channel) {
//				return 'UNHOSTED CHANNEL DIFFERS FROM PUBSIGN CHANNEL';
			}
			$lastRevision = $this->lockUpdate($command->path);
			if($lastRevision != $command->lastRevision) {
				$this->unlockUpdate($command->path);
				return 'ATTEMPT TO MERGE FROM STALE REVISION - TRY '.$lastRevision;
			}
			$this->update($command->path, $command->payload, array(
				'protocol'=>'PubSign0.1',
				'channel'=>$channel,
				'payload'=>$payload,
				'sign'=>$sign));
			return TRUE;
		default:
			return FALSE;
		}
	}

	function get($path) {
		echo "getting path $path<br/>";
		list($ch, $app, $cloud, $keyPath) = $this->parsePath($command->path);
		if($cloud != CLOUDNAME) {
			return 'RELAYING DENIED';
		}
		echo "cloud $cloud is ok<br/>";
		$fileName = $this->pathToFileName($path);
		echo "getting $fileName from disk<br/>";
		return file_get_contents($fileName);
	}

}
