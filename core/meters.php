<?php
/**
 * Utility class to display a script's execution time
 * 
 * Call execution execTime::start() to start the counter
 * and execTime::out() to display total execution time.
 */
class execTime {
	private static $time;
	private static $start;
	private static $end;
	private static $addWasCalled = false;
	private static $stepWasCalled = false;
	private static $isDebugWindow = true;

	public static function start() {
		self::$start = microtime(true);
	}

	public static function end() {
		return microtime(true) - self::$start;
	}

	public static function out() {
		if (!is_array(self::$time)) {
			if (self::$addWasCalled) {
				return "\n\n";
				return self::displayText(self::$time);
			} else {
				return self::displayText(self::end());
			}
		} else {
			reset(self::$time);
			while (list($key,$item) = each(self::$time)) {
				return $key . " : " . self::displayText($item);
			}
		}
	}

	public static function add() {
		self::$addWasCalled = true;
		self::$time += self::end();
	}

	public static function step($label) {
		self::$stepWasCalled = true;
		self::$time[$label] = self::end();
		self::$start = microtime(true);
	}

	public static function displayText($val) {
		if (self::$isDebugWindow) {
			$sep = "\n";
		} else {
			$sep = "<br/>";
		}
		return (($s = ($val) * 1000) > 1000) ? number_format($s / 1000, 3) . " s" . $sep : number_format($s, 3, ".", "") . " ms" . $sep;
	}

	public static function isDebug($val) {
		self::$isDebugWindow = $val;
	}
}

/**
 * Utility class to display memory usage of a script
 *
 */
class memUsage {
	private static $memory;
	private static $start;
	private static $isDebugWindow = true;
	private static $realMemUsage = false;

	public static function start($realMemUsage = false) {
		self::$realMemUsage = $realMemUsage;
		self::$start = memory_get_usage($realMemUsage);
	}

	public static function end() {
		return memory_get_usage(self::$realMemUsage) - self::$start;
	}

	public static function out() {
		return self::displayText(self::end());
	}

	public static function convert($size) {
		if ($size > 0) {
			$unit = array('B', 'KB', 'MB', 'GB');
			return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . '' . $unit[$i];
		} else {
			return 0;
		}
	}

	public static function displayText($val) {
		if (self::$isDebugWindow) {
			$sep = "\n";
		} else {
			$sep = "<br/>";
		}
		return self::convert($val) . $sep;
	}
}