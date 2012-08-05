<?php

class BrCSSCompressor {

	function compress($content) {
		// Remove comments
		$content = preg_replace('/\\/\\*.*?\\*\\//', '', $content);

		// Remove whitespace at the beginning and end of CSS
		$content = preg_replace('/^[\r\n]+/', '', $content);
		$content = preg_replace('/[\r\n]+$/', "\n", $content);

		// Remove redundant linebreaks
		$content = preg_replace('/\r\n/', "\n", $content);
		$content = preg_replace('/\n+/', "\n", $content);

		// Remove whitespace before/after styles inside rules
		$content = preg_replace('/\\{\\s*(.*?)\\s*\\}/', '{$1}', $content);

		// Remove remove whitespace between style rules and after the last one
		$content = preg_replace('/;\\s+/', ';', $content);
		$content = preg_replace('/\\{([^\\}]+);\\}/', '{$1}', $content);

		// Remove whitespace between :
		$content = preg_replace('/\\s*\\:\\s*/', ':', $content);

		return $content;
	}

}
