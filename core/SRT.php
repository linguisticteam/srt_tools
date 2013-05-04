<?php
/**
 * Contains all the SRT file manipulation functions 
 * 
 * 
 **/
define("WORD_COUNT_MASK", "/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}]*/u");

class SRT {
	protected $class = __CLASS__;

	/**
	 * Converts a timestamp from an integer value to a regular SRT style timestamp, ie 00:00:00,000
	 */
	public static function intToTimestamp($time) {
		$ms = $time % 1000;
		$time = ($time - $ms) / 1000;
		$seconds = $time % 60;
		$time = ($time - $seconds) / 60;
		$minutes = $time % 60;
		$hours = ($time - $minutes) / 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT) . ',' . str_pad($ms, 3, '0', STR_PAD_LEFT);
	}

	public function strDisplayLen($a, $b) {
		return self::timestampToInt($b) - self::timestampToInt($a);
	}

	public function timestampToInt($val) {
		return intval(substr($val, 9)) + (intval(substr($val, 6, 2)) * 1000) + (intval(substr($val, 3, 2)) * 60000) + (intval(substr($val, 0, 2)) * 3600000);
	}

	public function timeshift($a, $time) {
		$b = self::timestampToInt($a);
		if ($b > $time) {
			return $this->intToTimestamp($b - $time);
		} else {
			return $a;
		}
	}

	/**
	 * Makes sure the string is encoded in UTF-8,
	 * otherwise the comparison functions won't work
	 */
	public static function convert($string) {
		return (@iconv('utf-8', 'utf-8//IGNORE', $string) == $string) ? $string : utf8_encode($string);
	}

	function str_word_count_utf8($string, $format = 0) {
		$matches = array();
		switch ($format) {
			case 1 :
				preg_match_all(WORD_COUNT_MASK, $string, $matches);
				return $matches[0];
			case 2 :
				preg_match_all(WORD_COUNT_MASK, $string, $matches, PREG_OFFSET_CAPTURE);
				$result = array();
				foreach ($matches[0] as $match) {
					$result[$match[1]] = $match[0];
				}
				return $result;
		}
		return preg_match_all(WORD_COUNT_MASK, $string, $matches);
	}

	/**
	 * Reads an srt file, whose format goes as follows
	 * 1
	 * 00:00:11,924 --> 00:00:15,934
	 * The SRT string goes here
	 * 
	 * 2
	 * 00:00:20,773 --> 00:00:25,074
	 * Second string goes here
	 * 
	 * The function runs a counter from 1 to 4 as it reads the lines
	 * and does the appropriate processing according to which line is read.
	 * 
	 */
	function read($file) {
		$tab = array();
		$handle = @fopen($file, 'r');
		if ($handle) {
			$buffer = fgets($handle);
			if (trim($buffer) != '1' && trim($buffer) != chr(239) . chr(187) . chr(191) . chr(49)) {
				throw new FileException($file);
			}
			$i = 2;
			$cnt = 3;
			while (($buffer = fgets($handle)) !== false) {
				switch ($i) {
					//This is the timestamp
					case 2 :
						$t = explode(' --> ', trim($buffer));
						$st = $this->timestampToInt($t[0]);
						$this->srt[$st] = SRTString::makeString($this->class);
						$this->srt[$st]->end = $this->timestampToInt($t[1]);
						break;
					//This is the text itself
					case 3 :
						$this->srt[$st]->string = self::convert(trim($buffer));
						break;
					//This is the blank line between SRT strings
					case 4 :
						if (trim($buffer) != "") {
							$this->srt[$st]->string .= chr(10) . self::convert(trim($buffer));
							continue 2;
						}
						$i = 1;
						continue 2;
				}
				$i++;
			}
			if (!feof($handle)) {
				throw new FileException($file);
			}
			fclose($handle);
		} else {
			throw new FileException($file);
		}
	}
	
	function displayTable($columns, $data, $header) {
		echo "<h2>" . $header . "</h2>";
		if (is_array($columns) && !empty($columns) && !empty($data)) {
				
			echo "<table><thead>
			<tr>";
			foreach ($columns as $value) {
				echo "<th scope=\"col\">" . $value . "</th>\n";
			}
			echo "</tr>
	  		</thead>
	  		<tbody>" . $data . "</tbody>
	  	</table><br/><br/>";
		} else {
			echo "<h5>This guideline is perfectly respected</h5>";
		}
	}
}

class DiffSRT extends SRT {
	protected $class = __CLASS__;
	public $srt;
	public $timings = 0;
	private $mustConvert = false;

	public function __construct($file, $checkTimings = true, $hasTimings = true) {
		if ($hasTimings) {
			$this->read($file, $checkTimings);
		} else {
			$this->readNoTimings($file, $checkTimings);
		}
	}

	function read($file, $checkTimings = true) {
		$tab = array();
		$handle = @fopen($file, 'r');
		if ($handle) {
			$buffer = fgets($handle);
			if (trim($buffer) != '1' && trim($buffer) != chr(239) . chr(187) . chr(191) . chr(49)) {
				throw new FileException($file);
			}
			$i = 2;
			$cnt = 3;
			while (($buffer = fgets($handle)) !== false) {
				switch ($i) {
					case 2 :
						$t = explode(' --> ', trim($buffer));
						$st = $this->timestampToInt($t[0]);
						$this->srt[$st] = SRTString::makeString($this->class);
						$this->srt[$st]->end = $this->timestampToInt($t[1]);
						if ($checkTimings) {
							$this->timings += ($this->srt[$st]->end + $st) * $cnt;
							$cnt += 2;
						}
						break;
					case 3 :
						$this->srt[$st]->string = self::convert(trim($buffer));
						break;
					case 4 :
						if (trim($buffer) != "") {
							$this->srt[$st]->string .= chr(10) . self::convert(trim($buffer));
							continue 2;
						}
						$i = 1;
						if ($cnt == 7) {
							$cnt = 3;
						}
						continue 2;
				}
				$i++;
			}
			if (!feof($handle)) {
				throw new FileException($file);
			}
			fclose($handle);
		} else {
			throw new FileException($file);
		}
	}

	function readNoTimings($file, $checkTimings = true) {
		$tab = array();
		$handle = @fopen($file, 'r');
		if ($handle) {
			$buffer = fgets($handle);
			if (trim($buffer) != '1' && trim($buffer) != chr(239) . chr(187) . chr(191) . chr(49)) {
				throw new FileException($file);
			}
			$line = 1;
			$i = 2;
			$cnt = 3;
			while (($buffer = fgets($handle)) !== false) {
				switch ($i) {
					case 3 :
						$this->srt[$line] = self::convert(trim($buffer));
						break;
					case 4 :
						if (trim($buffer) != "") {
							$this->srt[$line] .= chr(10) . self::convert(trim($buffer));
							continue 2;
						}
						$line++;
						$i = 1;
						continue 2;
				}
				$i++;
			}
			if ($checkTimings) {
				$this->timings = md5(serialize($this->srt));
			}
			if (!feof($handle)) {
				throw new FileException($file);
			}
			fclose($handle);
		} else {
			throw new FileException($file);
		}
	}
}

class DiffOldSRT extends DiffSRT {
	protected $class = __CLASS__;
}

class DiffNewSRT extends DiffSRT {
	protected $class = __CLASS__;
}

class DiffBaseSRT extends DiffSRT {
	protected $class = __CLASS__;
}

class SRTString {
	public $end = 0;
	public $string = "";

	public static function makeString($class) {
		if ($class == "originalSRT") {
			return new OldString();
		}
		return new NewString();
	}
}

class OldString extends SRTString {}

class NewString extends SRTString {}

class ResultString extends SRTString {
	public $start = "";
	public $end = "";
	public $startGap = "";
	public $endGap = "";
	public $ts = "";
	public $status = SRTDiff::NO_CHANGES;
	public $oldString = "";
	public $changedString = "";
	public $baseString = "";
	public $CPSChange = "";
}

class SRTAnalyzer extends SRT {
	const SANDBOX = 120;
	const CHILDREN = 140;
	const ADULT = 160;
	const ADULT_INTENSE = 200;
	const TOO_LONG_TIME = 6;

	public function __construct($file, $filename) {
		$this->filename = $filename;
		$this->read($file);
	}

	public function read($file) {
		//Counter : number of lines containing text (the subtitle strings)
		$textnumlines = 0;
		//Contains the items that don't follow the guidelines
		$errorLog = array();
		//Contains word counts for all the lines
		$wordCount = array();
		
		$error = new stdClass();
		$error->too_fast = new stdClass();
		$error->too_fast->name = "Strings displayed for less than 1.5s";
		$error->too_fast->table_header = array("Start time", "String", "Display Time");
		$error->too_fast->values = array();
		
		$error->slighlty_fast = new stdClass();
		$error->slighlty_fast->name = "35+ char strings displayed for less than 2s";
		$error->slighlty_fast->table_header = array("Start time", "String", "Display Time");
		$error->slighlty_fast->values = array();
		
		$error->no_gap = new stdClass();
		$error->no_gap->name = "Gap of less than 100-140ms between strings";
		$error->no_gap->table_header = array("Start time");
		$error->no_gap->values = array();
		
		$error->too_long = new stdClass();
		$error->too_long->name = "70+ char strings";
		$error->too_long->table_header = array("Start time", "String", "Length");
		$error->too_long->values = array();
		
		$error->too_long_time = new stdClass();
		$error->too_long_time->name = self::TOO_LONG_TIME . "+ second strings";
		$error->too_long_time->table_header = array("Start time", "String", "Display Time");
		$error->too_long_time->values = array();
		
		$m = false;
		$handle = @fopen($file, 'r');
		$timestamps = array();
		$i = 1;
		while (($buffer = fgets($handle, 4096)) !== false) {
			switch ($i) {
				case 2 :
					$timestamps = explode(' --> ', trim($buffer));
					$total_file[$textnumlines]["START"] = $timestamps[0];
					$total_file[$textnumlines]["END"] = $timestamps[1];
					
					$len = $this->strDisplayLen($total_file[$textnumlines]["START"], $total_file[$textnumlines]["END"]);
					$lenDisplay = number_format($len / 1000, 3);
					if (isset($total_file[$textnumlines - 1]["END"])) {
						if ($this->strDisplayLen($total_file[$textnumlines - 1]["END"], $total_file[$textnumlines]["START"]) < 100) {
							$error->too_fast->values[] = implode(";", array($total_file[$textnumlines]["START"]));
						}
					}
					break;
				
				case 3 :
					$min = intval(substr($total_file[$textnumlines]["START"], 3, 2));
					if (!isset($wordCount[$min])) {
						$wordCount[$min] = $this->str_word_count_utf8($buffer);
					} else {
						$wordCount[$min] += $this->str_word_count_utf8($buffer);
					}
					$buffer = trim($buffer);
					if (strlen($buffer) > 35 && $len < 2000) {
						$error->slighlty_fast->values[] = array($total_file[$textnumlines]["START"], $buffer, $lenDisplay);
					}
					
					if ($len < 1500 && strlen($buffer) > 10) {
						$error->too_fast->values[] = array($total_file[$textnumlines]["START"], $buffer, $lenDisplay);
					}
					if ($len > (self::TOO_LONG_TIME * 1000)) {
						$error->too_long_time->values[] = array($total_file[$textnumlines]["START"], $buffer, $lenDisplay);
					}
					
					if (strlen($buffer) > 70) {
						$error->too_long->values[] = array($total_file[$textnumlines]["START"], $buffer, strlen($buffer));
					}
					
					$textnumlines++;
					break;
				case 4 :
					if (trim($buffer) != "") {
						continue 2;
						//die("This script doesn't work for strings containing line breaks.");
					}
					$i = 1;
					continue 2;
					break;
			}
			$i++;
		}
		fclose($handle);
		//Calculation of the words per minute
		$wpm = intval(array_sum($wordCount) / count($wordCount));
		//Different levels of difficulty for the subtitle file
		$levels = array("Sandbox", "Children And Above", "Adult", "Adult Intense", "Above comfortable standards");
		
		switch ($wpm) {
			case $wpm < self::SANDBOX :
				$current_level = 0;
				break;
			case $wpm < self::CHILDREN :
				$current_level = 1;
				break;
			case $wpm < self::ADULT :
				$current_level = 2;
				break;
			case $wpm < self::ADULT_INTENSE :
				$current_level = 3;
				break;
			case $wpm > self::ADULT_INTENSE :
				$current_level = 4;
				break;
		}
		echo "<h1>Results of the analysis</h1><br/>";
		if ($this->filename > 50) {
			echo "<h1>" . substr($this->filename, 0, 50) . "</h1><br/>";
		} else {
			echo "<h1>" . $this->filename . "</h1><br/>";
		}
		
		echo "<h5>Average words per minute : " . $wpm . "<br/>";
		echo "<br/>Degree of readability : </h5>";
		foreach ($levels as $key => $value) {
			if ($key == $current_level) {
				echo "<span id=\"levels\"><b>" . $value . "</b></span>";
			} else {
				echo "<span id=\"levels\" style=\"color:#E5E5E5;text-decoration : none;\">" . $value . "</span>";
			}
		}
		
		foreach ($error as $values) {
			echo "<h2>" . $values->name . "</h2>";
			if (!empty($values->values)) {
				
				echo "<table><thead>\n<tr>\n";
				foreach ($values->table_header as $value) {
					echo "<th scope=\"col\">" . $value . "</th>\n";
				}
				echo "</tr>\n</thead>\n<tbody>\n";
				foreach ($values->values as $value) {
					echo "<tr><td>" . implode("</td><td>", $value) . "</td></tr>";
				}
				echo "</tbody>\n</table><br/><br/>";
			} else {
				echo "<h5>This guideline is perfectly respected</h5>";
			}
		}
	}

	public function old_read($file) {
		//Counter : number of lines containing text (the subtitle strings)
		$textnumlines = 0;
		//Contains the items that don't follow the guidelines
		$errorLog = array();
		//Contains word counts for all the lines
		$wordCount = array();
		
		$errorLog["SLIGHTLY_TOO_FAST"] = $errorLog["TOO_FAST"] = $errorLog["TOO_LONG"] = $errorLog["TOO_LONG_TIME"] = $errorLog["NOT_READABLE"] = "";
		
		$m = false;
		$handle = @fopen($file, 'r');
		$timestamps = array();
		while (($buffer = fgets($handle, 4096)) !== false) {
			if (strpos($buffer, "-->") !== false) {
				$timestamps = explode(' --> ', trim($buffer));
				$total_file[$textnumlines]["START"] = $timestamps[0];
				$total_file[$textnumlines]["END"] = $timestamps[1];
				
				$len = $this->strDisplayLen($total_file[$textnumlines]["START"], $total_file[$textnumlines]["END"]);
				$lenDisplay = number_format($len / 1000, 3);
				if (isset($total_file[$textnumlines - 1]["END"])) {
					if ($this->strDisplayLen($total_file[$textnumlines - 1]["END"], $total_file[$textnumlines]["START"]) < 100) {
						$errorLog["NOT_READABLE"] .= "<tr>\n\t<td>" . $total_file[$textnumlines]["START"] . "</td></tr>\n";
					}
				}
				$m = true;
			} else if ($m) {
				$min = intval(substr($total_file[$textnumlines]["START"], 3, 2));
				if (!isset($wordCount[$min])) {
					$wordCount[$min] = $this->str_word_count_utf8($buffer);
				} else {
					$wordCount[$min] += $this->str_word_count_utf8($buffer);
				}
				
				$buffer = trim($buffer);
				if (strlen($buffer) > 35 && $len < 2000) {
					$errorLog["SLIGHTLY_TOO_FAST"] .= "<tr>\n\t<td>" . $total_file[$textnumlines]["START"] . "</td><td>" . $buffer . "</td><td>" . $lenDisplay . "</td></tr>\n";
				}
				
				if ($len < 1500 && strlen($buffer) > 10) {
					$errorLog["TOO_FAST"] .= "<tr>\n\t<td>" . $total_file[$textnumlines]["START"] . "</td><td>" . $buffer . "</td><td>" . $lenDisplay . "</td></tr>\n";
				}
				if ($len > 6000) {
					$errorLog["TOO_LONG_TIME"] .= "<tr>\n\t<td>" . $total_file[$textnumlines]["START"] . "</td><td>" . $buffer . "</td><td>" . $lenDisplay . "</td></tr>\n";
				}
				
				if (strlen($buffer) > 70) {
					$errorLog["TOO_LONG"] .= "<tr>\n\t<td>" . $total_file[$textnumlines]["START"] . "</td><td>" . $buffer . "</td><td>" . strlen($buffer) . "</td></tr>\n";
				}
				
				$textnumlines++;
				$m = false;
			}
		}
		fclose($handle);
		//Calculation of the words per minute
		$wpm = intval(array_sum($wordCount) / count($wordCount));
		//Different levels of difficulty for the subtitle file
		$levels = array("Sandbox", "Children And Above", "Adult", "Adult Intense", "Above comfortable standards");
		
		switch ($wpm) {
			case $wpm < 120 :
				$current_level = 0;
				break;
			case $wpm < 140 :
				$current_level = 1;
				break;
			case $wpm < 160 :
				$current_level = 2;
				break;
			case $wpm < 200 :
				$current_level = 3;
				break;
			case $wpm > 200 :
				$current_level = 4;
				break;
		}
		echo "<h1>Results of the analysis</h1><br/>";
		if ($this->filename > 50) {
			echo "<h1>" . substr($this->filename, 0, 50) . "</h1><br/>";
		} else {
			echo "<h1>" . $this->filename . "</h1><br/>";
		}
		
		echo "<h5>Average words per minute : " . $wpm . "<br/>";
		echo "<br/>Degree of readability : </h5>";
		foreach ($levels as $key => $value) {
			if ($key == $current_level) {
				echo "<span id=\"levels\"><b>" . $value . "</b></span>";
			} else {
				echo "<span id=\"levels\" style=\"color:#E5E5E5;text-decoration : none;\">" . $value . "</span>";
			}
		}
		echo "<br/><br/>";
		
		$this->displayTable(array("Start time", "String", "Display Time"), $errorLog["TOO_FAST"], "Strings displayed for less than 1.5s");
		$this->displayTable(array("Start time", "String", "Display Time"), $errorLog["SLIGHTLY_TOO_FAST"], "35+ char strings displayed for less than 2s");
		$this->displayTable(array("Start time", "String", "Length"), $errorLog["TOO_LONG"], "70+ char strings");
		$this->displayTable(array("Start time", "String", "Display Time"), $errorLog["TOO_LONG_TIME"], "6+ second strings");
		$this->displayTable(array("Start time"), $errorLog["NOT_READABLE"], "Gap of less than 100-140ms between strings");
	}
}

class SRTEditor extends SRT {

	function __construct($file, $chkGaps, $chkDots, $export, $exportcsv) {
		//parent::__construct($file);
		$this->chkGaps = $chkGaps;
		$this->chkDots = $chkDots;
		$this->export = $export;
		$this->exportCSV = $exportcsv;
		$this->read($file);
	}

	public function read($file) {
		$totalnumlines = 0;
		$textnumlines = 1;
		
		$m = false;
		$handle = @fopen($file, 'r');
		$timestamps = array();
		while (($buffer = fgets($handle, 4096)) !== false) {
			$totalnumlines++;
			if (strpos($buffer, "-->") !== false) {
				preg_match_all("#([0-9]{2}:){2}[0-9]{2},[0-9]{3}#", $buffer, $timestamps);
				$total_file[$textnumlines]["START"] = $timestamps[0][0];
				$total_file[$textnumlines]["END"] = $timestamps[0][1];
				
				if ($this->chkGaps) {
					if (isset($total_file[$textnumlines - 1]["END"])) {
						if ($this->strDisplayLen($total_file[$textnumlines - 1]["END"], $total_file[$textnumlines]["START"]) < 140) {
							$total_file[$textnumlines - 1]["END"] = $this->timeshift($total_file[$textnumlines - 1]["END"], 140);
						}
					}
				}
				$m = true;
			} else if ($m) {
				$total_file[$textnumlines]["TXT"] = trim($buffer);
				
				if (strlen(trim($buffer)) == 1 && $this->chkDots) {
					$total_file[$textnumlines] = false;
				} else {
					$total_file[$textnumlines]["TXT"] = trim($buffer);
				}
				$textnumlines++;
				$m = false;
			}
		}
		fclose($handle);
		if (!empty($total_file)) {
			//if we export only the text in the file
			if ($this->export) {
				reset($total_file);
				foreach ($total_file as $item) {
					if (is_array($item)) {
						if (!empty($item["TXT"])) {
							$t[] = $item["TXT"];
						}
					}
				}
				echo implode(" ", $t);
			} else if ($this->exportCSV) {
				$totalnumlines = 1;
				$fileNumLines = 1;
				$total_file = array_values(array_filter($total_file));
				echo "Line Number,Start Timestamp,Stop Timestamp,String Duration,Text,Translation" . chr(10);
				reset($total_file);
				foreach ($total_file as $item) {
					if (is_array($item)) {
						if (isset($item["START"]) && isset($item["END"])) {
							$text = (str_replace(array(chr(10), chr(13)), "", $item["TXT"]));
							echo "\"" . ($fileNumLines++) . "\",\"" . str_replace(array(chr(10), chr(13)), "", $item["START"] . "\",\"" . $item["END"]) . "\",\"" . number_format(($this->strDisplayLen($item["START"], $item["END"])) / 1000, 3) . "\",\"" . str_replace("\"", "\"\"", $text) . "\"," . chr(10);
						}
					}
				}
			} else {
				$totalnumlines = 1;
				$fileNumLines = 1;
				$total_file = array_values(array_filter($total_file));
				//We prefix the file with the three characters that allow it to be identified as a SubRip formatted file.
				echo chr(239) . chr(187) . chr(191);
				reset($total_file);
				foreach ($total_file as $item) {
					if (is_array($item)) {
						if (isset($item["START"]) && isset($item["END"])) {
							echo ($fileNumLines++) . chr(10) . str_replace(array(chr(10), chr(13)), "", $item["START"] . "-->" . $item["END"]) . chr(10) . $item["TXT"] . chr(10) . chr(10);
						}
					}
				}
			}
		}
	}
}

class FileException extends Exception {

	public function __construct($file) {
		echo "The file '$file' could not be read. Is it an <a href=\"http://en.wikipedia.org/wiki/.srt#SubRip_text_file_format\">SRT file</a>?";
	}
}