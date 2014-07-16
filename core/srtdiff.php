<?php
require_once '../core/finediff.php';
require_once '../core/SRT.php';

/**
 * Displays the end result of a SRT file comparison
 * 
 */
class ResultViewer {
	private $simpleCheck=false;
	private $seeAll=false;
	private $hasTimings=true;
	
	/**
	 * Constructor for the ResultViewer class
	 * @param string $originalPath Path to the file before changes
	 * @param string $modifiedPath Path to the modified file
	 * @param string $basefilePath Path to the original file if the compared files are translations
	 * @param object $config Contains configuration parameters
	 */
	public function __construct($originalPath, $modifiedPath, $basefilePath = null,$config=null) {
		if (isset($config->simpleCheck)) {
			$this->simpleCheck = $config->simpleCheck;
		}
		if (isset($config->seeAll)) {
			$this->seeAll = $config->seeAll;
		}
		if (isset($config->hasTimings)) {
			$this->hasTimings = $config->hasTimings;
		}
		//ini_set("auto_detect_line_endings", true);
		//We set a short time limit, those comparisons shouldn't take too long to run
		set_time_limit(8);
		//When the timeout is reach, the result of this method is displayed
		register_shutdown_function(array('ResultViewer', 'maxTimeExecError'));
		try {
			$SRTDiff = new SRTDiff($originalPath, $modifiedPath, $basefilePath, $this->hasTimings);
			if ($this->simpleCheck) {
				$this->simpleCheck = $SRTDiff->simpleCheck();
				return 0;
			} else if ($this->seeAll) {
				$SRTDiff->compare($this->seeAll);
			} else if (!$this->hasTimings) {
				$SRTDiff->compareText();
			} else {
				$SRTDiff->compare();
			}
		} catch ( Exception $e ) {
			echo "<div class=\"srtdiff_error\">" . $e->getMessage() . "</div>";
			return 0;
		}
		
		echo "<ul id=\"stringList\">\n";
		$i = 0;
		while (($string = $SRTDiff->next()) !== false && $i < 5000) {
			$i++;
			echo "<li class=\"" . $string->status . "String\">\n";
			echo "<span class=\"timestamps\">" . $string->ts . "</span>\n";
			if (!empty($string->baseString)) {
				echo "\t<p class=\"bString\">" . $string->baseString . "</p>\n";
			}
			echo "\t<p class=\"npString\">" . $string->oldString . "</p>\n";
			if (!empty($string->changedString)) {
				echo "\t<p class=\"pString\">" . $string->changedString . "</p>\n";
			}
			echo "</li>\n";
		}
		echo "</ul>\n";
	}

	public static function maxTimeExecError() {
		$error = error_get_last();
		if ($error !== null && $error['type'] == 1) {
			echo "<div class=\"srtdiff_error\">The comparison couldn't complete because one of the subtitle files that were transmitted uses a file encoding ";
			echo "that wasn't accounted for by the system.<br/>Please send your files to linguisticteam@gmail.com for analysis.</div>";
		}
	}
	
	public function getSimpleCheck(){
		return $this->simpleCheck;
	}
}

class SRTDiff {
	/**
	 * Tags on a diff string indicating what kind of change it went through 
	 */
	const NO_CHANGES = 'noChange';
	const STRING_CHANGED = 'changed';
	const STRING_ADDED = 'added';
	const STRING_DELETED = 'deleted';
	const STRING_POSSIBLY_DELETED = 0;
	const STRING_MERGED = 'merged';
	const STRING_SPLIT = 'split';
	/**
	 * Reading speed indicators
	 */
	const CPS_TOO_FAST = 35;
	const CPS_FAST = 30;
	const CPS_FAST_OK = 25;
	const CPS_OK = 20;
	const CPS_PERFECT = 15;
	const CPS_SLOW_OK = 10;
	const CPS_TOO_SLOW = 0;
	const NEWLINE_SEP = " | | ";
	private $srt = array();
	private $original;
	private $modified;
	private $base;
	private $originalPath = "";
	private $modifiedPath = "";
	private $oldStrings = array();
	private $newStrings = array();

	/**
	 * 
	 * Constructor for the ResultViewer class
	 * @param string $originalPath Path to the file before changes
	 * @param string $modifiedPath Path to the modified file
	 * @param string $basefilePath Path to the original file if the compared files are translations
	 * @param boolean $hasTimings If timestamps have to be checked
	 * @throws BadTimingsException Timestamps of the original and modified file have to be the same
	 * @throws SameFileException Files are the same
	 */
	public function __construct($originalPath, $modifiedPath, $basefilePath, $hasTimings = true) {
		$this->originalPath = $originalPath;
		$this->modifiedPath = $modifiedPath;
		$this->basefilePath = $basefilePath;
		try {
			$this->original = new DiffOldSRT($originalPath, true, $hasTimings);
			//$sv = gzcompress(serialize($this->original),1);
			//$sf = gzuncompress($sv);
			$this->modified = new DiffNewSRT($modifiedPath, false,$hasTimings);
			
			//If we have an base file that will be displayed on top of the original and modified strings
			if (!is_null($this->basefilePath) && !empty($this->basefilePath)) {
				$this->base = new DiffBaseSRT($basefilePath, true, $hasTimings);
				
				//Timings of the original file and base file have to be the same.
				if ($this->original->timings !== $this->base->timings) {
					throw new BadTimingsException();
				}
			}
			
			//No point in comparing two exact same files...
			if (hash_file('crc32', $this->originalPath) === hash_file('crc32', $this->modifiedPath)) {
				throw new SameFileException();
			}
		} catch ( CmpException $cmp ) {
			return;
		}
		
	}
	
	/**
	 * Function that does the actual string comparison once the files are read
	 * @param boolean $seeAll Whether we want to visualize all strings in the file regardless of whether they're different
	 */
	public function compare($seeAll=false) {
		$tmp = array();
		$srt=array();
		$specialStrings = array();
		$possiblyDeleted = array();
		$mergedItems = array();
		$i = 0;
		/**
		 * We start by parsing the strings a first time and compare the begin timestamps
		 */
		foreach ($this->original->srt as $startTime => $obj) {
			if (isset($this->modified->srt[$startTime])) {
				if (strcmp($obj->string, $this->modified->srt[$startTime]->string) !== 0) {
					$srt[$startTime] = new ResultString();
					$srt[$startTime]->endGap = $this->gapCalc($this->modified->srt[$startTime]->end, $obj->end);
					$resultSrt = FineDiff::diff($obj->string, $this->modified->srt[$startTime]->string);
					$obj->string=$resultSrt[0];
					$this->modified->srt[$startTime]->string=$resultSrt[1];
					
					$srt[$startTime]->changedString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $this->modified->srt[$startTime]->string);
					$srt[$startTime]->status = self::STRING_CHANGED;
					$srt[$startTime]->start = $startTime;
					$srt[$startTime]->oldString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $obj->string);
					if (!empty($srt[$startTime]->endGap)) {
						$srt[$startTime]->end = $this->modified->srt[$startTime]->end;
					} else {
						$srt[$startTime]->end = $obj->end;
					}
					$srt[$startTime]->start = $startTime;
					$srt[$startTime]->oldString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $obj->string);
					if (isset($this->base)) {
						$srt[$startTime]->baseString = $this->base->srt[$startTime]->string;
					}
				} else if ($seeAll) {
					$srt[$startTime] = new ResultString();
					$srt[$startTime]->endGap = $this->gapCalc($this->modified->srt[$startTime]->end, $obj->end);
					if (!empty($srt[$startTime]->endGap)) {
						$srt[$startTime]->end = $this->modified->srt[$startTime]->end;
					} else {
						$srt[$startTime]->end = $obj->end;
					}
					$srt[$startTime]->start = $startTime;
					$srt[$startTime]->oldString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $obj->string);
				}
				
				unset($this->modified->srt[$startTime]);
			} else {
				$srt[$startTime]->status = self::STRING_POSSIBLY_DELETED;
				$srt[$startTime] = new ResultString();
				$tmp[$startTime] = new OldString();
				$tmp[$startTime]->start = $startTime;
				$tmp[$startTime]->end = $obj->end;
				$tmp[$startTime]->string = $obj->string;
				$srt[$startTime]->end = $obj->end;
				$srt[$startTime]->oldString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $obj->string);
				if (isset($this->base)) {
					$srt[$startTime]->baseString = $this->base->srt[$startTime]->string;
				}
			}
		}
		$possiblyDeleted = $tmp;
		foreach ($this->modified->srt as $startTime => $obj) {
			$tmp[$startTime] = $obj;
		}
		ksort($tmp);

		$min = $max = $i = 0;
		foreach ($tmp as $startTime => $obj) {
			if ($startTime > $max) {
				$min = $startTime;
				$max = $obj->end;
				$i++;
			}
			$obj->start = $startTime;
			$specialStrings[$i][substr(get_class($obj), 0, 3)][] = $obj;
		}
		unset($tmp);
		
		if (!empty($specialStrings)) {
			foreach ($specialStrings as $a) {
				if (isset($a['Old'])) {
					if (isset($a['New'])) {
						//A String has been split in multiple parts.
						if (count($a['New']) > 1) {
							foreach ($a['New'] as $shrapnel) {
								$srt[$shrapnel->start] = new ResultString();
								$srt[$shrapnel->start]->end = $shrapnel->end;
								$srt[$shrapnel->start]->changedString = '<ins>' . str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $shrapnel->string) . '</ins>';
								$srt[$shrapnel->start]->status = self::STRING_ADDED;
							}
							//Timestamps have changed, and text has probably changed too.
						} else {
							$srt[$a['Old'][0]->start]->end = $a['New'][0]->end;
							if (strcmp($a['Old'][0]->string, $a['New'][0]->string) !== 0) {
								$diffStr = FineDiff::diff($a['Old'][0]->string, $a['New'][0]->string);
								$a['Old'][0]->string = $diffStr[0];
								$a['New'][0]->string = $diffStr[1];
								
								
							}
							$srt[$a['Old'][0]->start]->oldString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $a['Old'][0]->string);
							$srt[$a['Old'][0]->start]->changedString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $a['New'][0]->string);
							$srt[$a['Old'][0]->start]->status = self::STRING_CHANGED;
							$srt[$a['Old'][0]->start]->startGap = $this->gapCalc($a['New'][0]->start, $a['Old'][0]->start);
							
							if (!empty($srt[$a['Old'][0]->start]->startGap)) {
								$srt[$a['Old'][0]->start]->start = $a['New'][0]->start;
							}
							$srt[$a['Old'][0]->start]->endGap = $this->gapCalc($a['New'][0]->end, $a['Old'][0]->end);
							if (!empty($srt[$a['Old'][0]->start]->endGap)) {
								$srt[$a['Old'][0]->start]->end = $a['New'][0]->end;
							}
						}
						//Strings have been merged.
					} else {
						$mergedItems[$a['Old'][0]->start] = $a['Old'][0]->string;
					}
					//A string has been added
				} else {
					$srt[$a['New'][0]->start] = new ResultString();
					$srt[$a['New'][0]->start]->start = $a['New'][0]->start;
					$srt[$a['New'][0]->start]->end = $a['New'][0]->end;
					$srt[$a['New'][0]->start]->changedString = '<ins>' . str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $a['New'][0]->string) . '</ins>';
					$srt[$a['New'][0]->start]->status = self::STRING_ADDED;
				}
			}
		}
		
		/**
			A string that is not present in the modified file is not necessarily a removed string,
			it could be a merged string. That's why we need to check whether or not
			that supposedly missing string has been attached to another string.
		 */
		reset($possiblyDeleted);
		reset($mergedItems);
		$itemsToDelete = array();
		$hasNext = true;
		if (!empty($srt)) {
			foreach ($srt as $startTime => $obj) {
				if (!empty($possiblyDeleted)) {
					$todel = array();
					if (!empty($obj->changedString)) {
						foreach ($possiblyDeleted as $key => $pD) {
							if ($startTime < $pD->end) {
								if (strpos($obj->changedString, htmlspecialchars($pD->string, ENT_QUOTES)) !== false || strpos($obj->changedString, $pD->string) !== false) {
									$todel[] = $key;
									
									if ($obj->changedString != $pD->string) {
										if (preg_match("#^\<#", str_replace(htmlspecialchars($pD->string, ENT_QUOTES), "", $obj->changedString))) {
											$srt[$key]->oldString = str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $pD->string);
											$obj->changedString = str_replace(htmlspecialchars($pD->string, ENT_QUOTES), htmlspecialchars($pD->string, ENT_QUOTES), $obj->changedString);
										} else {
											$itemsToDelete[] = $key;
											$obj->changedString = str_replace(htmlspecialchars($pD->string, ENT_QUOTES), "<b>+[</b>" . htmlspecialchars($pD->string, ENT_QUOTES) . "<b>]</b> ", $obj->changedString);
											if (isset($this->base->srt[$startTime]) && isset($this->base->srt[$key])) {
												$obj->baseString .= " <b>+(</b>" . $this->base->srt[$key]->string . "<b>)</b>";
											}
										}
									}
									$srt[$key]->status = SRTDiff::STRING_CHANGED;
									$srt[$key]->start = $key;
									$srt[$key]->end = $pD->end;
									$srt[$key]->changedString = $obj->changedString; //"";
								}
							} else {
								$todel[] = $key;
								$srt[$key]->status = SRTDiff::STRING_DELETED;
								$srt[$key]->start = $key;
								$srt[$key]->end = $this->original->srt[$key]->end;
								$srt[$key]->oldString = '<del>' . str_replace(array(chr(10), "\\n"), self::NEWLINE_SEP, $this->original->srt[$key]->string) . '</del>';
								$srt[$key]->changedString = '';
							}
						}
					}
					
					foreach ($todel as $elem) {
						unset($possiblyDeleted[$elem]);
					}
				}
				$srt[$startTime]->start = SRT::intToTimestamp($startTime);
				$srt[$startTime]->end = SRT::intToTimestamp($srt[$startTime]->end);
				if (!empty($srt[$startTime]->startGap)) {
					$srt[$startTime]->startGap = '(' . $srt[$startTime]->startGap . ') ';
				}
				if (!empty($srt[$startTime]->endGap)) {
					$srt[$startTime]->endGap = ' (' . $srt[$startTime]->endGap . ')';
				}
				
				$srt[$startTime]->ts = $srt[$startTime]->startGap . $srt[$startTime]->start . ' --> ' . $srt[$startTime]->end . $srt[$startTime]->endGap;
			}
		}
		
		if (!empty($itemsToDelete)) {
			foreach ($itemsToDelete as $elem) {
				unset($srt[$elem]);
			}
		}
		
		ksort($srt);
		$this->srt = array_values(array_merge(array(0=>0), $srt));
		reset($this->srt);
		unset($srt);
	}
	
	public function compareText(){
		//TODO: to be implemented if we want to compare only the text in SRTs
	}
	
	/**
	 * This simply checks whether files are identical or not, and how dissimilar they are otherwise.
	 * @return string
	 */
	public function simpleCheck(){
		$changed=0;
		$lines = 0;
		foreach ($this->original->srt as $startTime => $obj) {
			if (isset($this->modified->srt[$startTime])) {
				if (strcmp($obj->string, $this->modified->srt[$startTime]->string) !== 0||$this->gapCalc($this->modified->srt[$startTime]->end, $obj->end)!="") {
					$changed++;
				}
			} else {
				$changed++;
			}
			$lines++;
		}
		if($changed>0){
			//return '<script type="text/javascript">alert("The files are different, '.floor($changed/$lines*100).'% of the strings have changed.");</script>';
			return 'THE FILES ARE DIFFERENT, '.floor($changed/$lines*100).'% OF THE STRINGS HAVE CHANGED.';
		}else{
			//return '<script type="text/javascript">alert("The files are identical.");</script>';
			return 'THE FILES ARE IDENTICAL.';
		}
	}
	
	private function gapCalc($base, $substract) {
		$n = $base - $substract;
		return ($n > 0) ? "+" . number_format($n / 1000, 3) : (($n < 0) ? number_format($n / 1000, 3) : "");
	}

	public function getResult() {
		return $this->srt;
	}

	public function next() {
		if (next($this->srt) !== false) {
			return current($this->srt);
		} else {
			return false;
		}
	}
}

class CmpException extends Exception {
	public function __construct(){
		echo "<div class=\"srtdiff_error\">".$this->message."</div>";
	}
		
}

class BadTimingsException extends CmpException {
	protected $message="The base file must have the same timings as the original ";
}

class SameFileException extends CmpException {
	protected $message="The compared files are identical";
}
