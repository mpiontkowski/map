<?php
namespace handler\mode;

use handler\AbstractHandler;
use peer\http\HttpConst;
use RuntimeException;
use store\Bucket;
use store\data\File;
use store\data\net\MAPUrl;
use store\data\net\Url;
use store\Logger;

abstract class AbstractModeHandler extends AbstractHandler {

	const TEXT_PREFIX   = '{';
	const TEXT_SPLITTER = '#';
	const TEXT_SUFFIX   = '}';

	/**
	 * @var MAPUrl
	 */
	protected $request = null;

	/**
	 * mode settings
	 *
	 * @var array { string => mixed }
	 */
	protected $settings = array();

	/**
	 * @return AbstractModeHandler this
	 */
	abstract public function handle();

	/**
	 * @throws RuntimeException
	 * @param  Bucket $config
	 * @param  MAPUrl $request
	 * @param  array  $settings { string => mixed }
	 */
	public function __construct(Bucket $config, MAPUrl $request, $settings) {
		if (!isset($settings['type'])) {
			throw new RuntimeException('mode invalid: expect `type`');
		}
		$this->setContentType($settings['type']);

		$this->request  = $request;
		$this->settings = $settings;
		parent::__construct($config);
	}

	/**
	 * @param  string $mimeType
	 * @return AbstractModeHandler this
	 */
	final protected function setContentType($mimeType) {
		header('Content-Type: '.$mimeType);
		return $this;
	}

	/**
	 * @param  Url $address
	 * @return AbstractModeHandler this
	 */
	final protected function setLocation(Url $address) {
		header('Location: '.$address);
		return $this;
	}

	/**
	 * get file in app folder
	 *
	 * @throws RuntimeException
	 * @return null|File
	 */
	final protected function getFile() {
		if (!isset($this->settings['folder'], $this->settings['extension'])) {
			throw new RuntimeException('mode invalid: expect `folder` and `extension`');
		}

		$fileList = array(
				new File('private/src/area/'.$this->request->getArea().'/app'),
				new File('private/src/common/app')
		);

		$page = implode('/', array_merge(array($this->request->getPage()), $this->request->getInputList()));

		foreach ($fileList as $file) {
			if (!($file instanceof File)) {
				continue;
			}
			$file
					->attach($this->settings['folder'])
					->attach($page.$this->settings['extension']);
			if ($file->isFile()) {
				return $file;
			}
		}
		return null;
	}

	/**
	 * @param  string $text
	 * @return string
	 */
	final protected function translate($text) {
		$locateTexts = array();

		$suffixPosition = -1;
		while (true) {
			$prefixPosition   = strpos($text, self::TEXT_PREFIX, $suffixPosition + 1);
			$splitterPosition = strpos($text, self::TEXT_SPLITTER, $prefixPosition + 2);
			$suffixPosition   = strpos($text, self::TEXT_SUFFIX, $splitterPosition + 2);

			if ($prefixPosition === false || $splitterPosition === false || $suffixPosition === false) {
				break;
			}

			$group = substr($text, $prefixPosition + 1, $splitterPosition - $prefixPosition - 1);
			$key   = substr($text, $splitterPosition + 1, $suffixPosition - $splitterPosition - 1);

			if (!isset($locateTexts[$group])) {
				$locateTexts[$group] = array();
			}
			if (!in_array($key, $locateTexts[$group])) {
				$locateTexts[$group][] = $key;
			}
		}

		$textBucket = $this->getTextBucket();
		foreach ($locateTexts as $group => $keyList) {
			foreach ($keyList as $key) {
				if ($textBucket->isString($group, $key)) {
					$pattern = self::TEXT_PREFIX.$group.self::TEXT_SPLITTER.$key.self::TEXT_SUFFIX;
					$text    = str_replace($pattern, $textBucket->get($group, $key), $text);
				}
			}
		}
		return $text;
	}

	/**
	 * @return Bucket
	 */
	final protected function getTextBucket() {
		$texts = new Bucket();

		if (isset($this->settings['multiLang']) && $this->settings['multiLang'] === true) {
			# additional lang-files
			if ($this->config->isArray('multiLang', 'loadList')) {
				$loadPathList = $this->config->get('multiLang', 'loadList');
			}
			else {
				$loadPathList = array();
			}

			# auto-loading lang-file
			if ($this->config->isTrue('multiLang', 'autoLoading')) {
				$language       = $this->config->get('multiLang', 'language');
				$area           = $this->request->getArea();
				$page           = $this->request->getPage();
				$loadPathList[] = 'area/'.$area.'/text/'.$language.'/page/'.$page.'.ini';
			}

			foreach ($loadPathList as $loadPath) {
				$loadFile = (new File('private/src'))
						->attach($loadPath);

				if ($loadFile->isFile()) {
					$texts->applyIni($loadFile);
				}
				else {
					Logger::warning('lang-file `'.$loadFile.'` not found');
				}
			}
		}
		return $texts;
	}

	/**
	 * @param  int $code
	 * @return AbstractModeHandler this
	 */
	protected function error($code) {
		if (!HttpConst::isStatus($code)) {
			throw new RuntimeException('unknown HTTP-Status Code `'.$code.'`');
		}
		http_response_code($code);

		# pipe to URL
		if (isset($this->settings['err'.$code.'-pipe'])) {
			$target = new MAPUrl($this->settings['err'.$code.'-pipe'], $this->config);

			if ($target->get() === $this->request->get()) {
				Logger::error('endless pipe-loop (status: `'.$code.'`) - interrupted with HTTP-Status `508`');
				return $this->error(508);
			}

			$this->setLocation(new Url($this->settings['err'.$code.'-pipe']));
			return $this;
		}

		# default error output
		if (defined('peer\http\HttpConst::STATUS_'.$code)) {
			$message = constant('peer\http\HttpConst::STATUS_'.$code);
		}
		else {
			$message = 'N/A';
		}

		$this->setContentType('text/plain');
		echo '['.$code.'] '.$message;
		return $this;
	}

}
