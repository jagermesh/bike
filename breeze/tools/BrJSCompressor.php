<?php

class BrJSCompressor {

	private $_strings;
	private $_count;

	function compress($content) {
		$this->_strings = array();
		$this->_count = 0;

		// Replace strings and regexps
		$content = preg_replace_callback('/\\\\(\"|\'|\\/)/', array(&$this, '_encode'), $content); // Replace all \/, \", \' with tokens
		$content = preg_replace_callback('/(\'[^\'\\n\\r]*\')|("[^"\\n\\r]*")|(\\s+(\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?))|([^\\w\\x24\\/\'"*)\\?:]\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?)/', array(&$this, '_strToItems'), $content);

		// Remove comments
		$content = preg_replace('/(\\/\\/[^\\n\\r]*[\\n\\r])|(\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\/)/', '', $content);

		// Remove whitespace
		$content = preg_replace('/\s*([=&|!+\\-\\/?:;,\\^\\(\\)\\{\\}<>%]+)\s*/', '$1', $content);
		$content = preg_replace('/[\r\n]+|(;)\s+/', '$1', $content);
		$content = preg_replace('/\s+/', ' ', $content);

		// Restore strings and regexps
		$content = preg_replace_callback('/¤@([^¤]+)¤/', array(&$this, '_itemsToStr'), $content);
		$content = preg_replace_callback('/¤#([^¤]+)¤/', array(&$this, '_decode'), $content); // Restore all \/, \", \'

		return $content;
	}

	function _strToItems($matches) {
		$this->_strings[] = $matches[0];

		return '¤@' . ($this->_count++) . '¤';
	}

	function _itemsToStr($matches) {
		return $this->_strings[intval($matches[1])];
	}

	function _encode($matches) {
		$this->_strings[] = $matches[0];

		return '¤#' . ($this->_count++) . '¤';
	}

	function _decode($matches) {
		return $this->_strings[intval($matches[1])];
	}

}
