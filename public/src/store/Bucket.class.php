<?php
namespace store;

use RuntimeException;
use store\data\File;

/**
 * @TODO write unit-tests
 */
class Bucket {
	
	const PATTERN_GROUP = '/^[A-Za-z0-9_-]{3,32}$/';
	const PATTERN_KEY = '/^[A-Za-z0-9_-]{3,32}$/';
	
	private $data = array();
	
	/**
	 * @param string $group
	 * @param string $key
	 * @return bool
	 */
	final public function exists($group, $key) {
		return isset($this->data[$group][$key]);
	}
	
	/**
	 * @param string $group
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	final public function get($group, $key, $default = null) {
		if ($this->exists($group, $key)) {
			return $this->data[$group][$key];
		}
		return $default;
	}

	/**
	 * @param string $group
	 * @param string $key
	 * @param mixed $value
	 * @throws RuntimeException if group or key invalid
	 * @return Bucket
	 */
	final public function set($group, $key, $value) {
		if (!preg_match(PATTERN_GROUP, $group)) {
			throw new RuntimeException('Invalid group `'.$group.'`.');
		}
		if (!preg_match(PATTERN_KEY, $key)) {
			throw new RuntimeException('Invalid key `'.$key.'`.');
		}
		$this->data[$group][$key] = $value;
		return $this;
	}
	
	/**
	 * @param File $iniFile
	 * @throws RuntimeException if file not exists
	 * @throws RuntimeException if file is invalid
	 * @return Bucket
	 */
	final public function apply(File $iniFile) {
		if ($iniFile === null || !$iniFile->isFile()) {
			throw new RuntimeException('File not exists `'.$iniFile.'`.');
		}
		foreach (parse_ini_file($iniFile, true, INI_SCANNER_TYPED) as $group => $keyList) {
			if (!is_array($keyList)) {
				# ignore keys without group
				continue;
			}
			foreach ($keyList as $key => $value) {
				try {
					$this->set($group, $key, $value);
				}
				catch (RuntimeExcepion $e) {
					throw new RuntimeException('File `'.$iniFile.'` is invalid. Caught Exception: '.$e->getMessage());
				}
			}
		}
		return $this;
	}
	
}