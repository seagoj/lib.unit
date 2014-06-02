<?php
namespace seago\devtools;
require_once("autoload.php");

class unit extends dbg {
	
	protected $commentTags;
	private $unit;
	private $methods;
	private $passed;
	private $depends;
	
	public function __construct($class) {
		print "<style>.errLabel{color:blue;}.errDesc{color:black;}.function{text-align:center;border:2px solid;border-radius:5px;-moz-border-radius:25px; /* Firefox 3.6 and earlier */}</style><div>Testing dbg</div>";
		$this->commentTags = array();
		$this->unit = new $class();
		$this->methods = get_class_methods($this->unit);
		$this->passed = array();
		$this->buildDepends();
		foreach($this->methods AS $method) {
			if(!in_array($method,$this->passed)) {
				if(in_array($method,$this->depends))
				{
					foreach($this->depends[$method] AS $dependancy) {
						if(!in_array($dependancy,$this->passed)) {
							print "<div class='function'>";
							$result = $this->$dependsTest();
							$result ? print "<div>".$dependsTest."() passed all unit tests</div></div><div>&nbsp;</div>" : die($this->dependsTest."() failed.");
						}
					}
				}
				$methodTest = $method."Test";
				print "<div class='function'>";
				$result = $this->$methodTest();
				$result ? print "<div>".$methodTest."() passed all unit tests</div></div><div>&nbsp;</div>" : die($methodTest."() failed.");
			}
			array_push($this->passed, $method);
		}
	}
	public function __destruct() {
		$pass=NULL;
		foreach($this->methods AS $method) {
			if($pass==NULL)
				$pass = dbg::test(!in_array($method."Test",$this->passed),"$method did not pass Unit tests.");
			else
				$pass = $pass && dbg::test(!in_array($method."Test",$this->passed),"$method did not pass Unit tests.");
		}
		$pass ? print "<div>dbg passed all unit tests</div><div>&nbsp;</div>": "";
	}
	private function buildDepends() {
		$unitTags = $this->unit->getUnitTags($_SERVER['SCRIPT_FILENAME']);
		//dbg::dump($unitTags);
		foreach($unitTags AS $method=>$comments) {
			//$depends = array();
			$temp = array();
			foreach($comments AS $comment) {
				foreach($comment AS $tag=>$value) {
					if($tag=='@depends') {
						array_push($temp,$value);
					}
				}
			}
			//dbg::dump($method);
			$this->depends[$method]=$temp;
		}
		return true;
	}
	public function getCommentTags($filename,$type=''){
		$this->scanCommentsForTags($filename,$type);
		return $this->commentTags;
	}
	public function getUnitTags($filename) {
		$this->scanCommentsForTags($filename);
		$unitTags = array(
				'@expectedException',
				'@expectedExceptionCode',
				'@expectedExceptionMessage',
				'@dataProvider',
				'@depends',
		);
		$return = array();
		//dbg::dump($this->commentTags);
		foreach($this->commentTags AS $method=>$values) {
			//dbg::dump($this->commentTags);
			foreach($values AS $value) {
				//dbg::dump($values);
				foreach($value AS $tag=>$comment) {
					if(in_array($tag,$unitTags)){
						$return = array($method=>array(array("$tag"=>"$comment")));
					}
				}
			}
		}
		//dbg::dump($return);
		return $return;
	}
	private function scanCommentsForTags($filename='dbg.php', $type='') {
		$source = file_get_contents($filename);
	
		$tokens = token_get_all($source);
		$comment = array(
				T_COMMENT,      // All comments since PHP5
				T_DOC_COMMENT	// PHPDoc comments
		);
		$commentTags = array();
		$fuctionTags = array();
		$commentsFound = false;
		$functionFound = false;
		$whitespace = array(" "=>0,"\t"=>0,"\r"=>0,"\n"=>0,"\r\n"=>0,"\n\r"=>0);
		//dbg::dump($tokens);
		foreach( $tokens as $token ) {
				
			if(in_array($token[0],$comment)) {
				preg_match_all('/@[a-zA-Z0-9 \t_]*/',$token[1],$matches);
	
				foreach($matches[0] AS $match) {
					$firstPos = 0;
					$rightChar = '';
					foreach($whitespace AS $char=>$pos) {
						if($pos = strpos($match,$char)) {
							//dbg::msg("$char $match ");
							if($firstPos==0||$pos<$firstPos) {
								$firstPos = $pos;
								$rightChar = $char;
							}
							$whitespace[$char]=$pos;
						}
					}/*
					$firstPos = 1000;
					foreach($whitespace AS $char=>$pos) {
					if($pos<$firstPos && $pos!=0)
						$firstPos=$pos;
					}*/
					$pivot = $firstPos;
					//dbg::dump($whitespace,false);
					//dbg::dump($pivot);
						
						 	
					/*
					 if(strpos($match,"\t")) {
					if(strpos($match,"\t")<strpos($match," ")) {
					$pivot = strpos($match,"\t");
					dbg::msg("$match $pivot");
					}
					else {
					$pivot = strpos($match, " ");
					dbg::msg("$match $pivot");
					}
					}
					else if (strpos($match, " ")) {
					$pivot = strpos($match, " ");
					dbg::msg("$match $pivot");
					} else {
					dbg::msg("Not Found");
					}
					}
					*/
					//dbg::dump("end",false);
					/*
						if(strpos($match,"\t")&&(strpos($match,"\t")<strpos($match," "))) {
					if(strpos($match,"\t")>0) {
					$pivot = strpos($match,"\t");
					dbg::msg("tab $match");
					} else {
					dbg::dump(strpos($match, "\t")."space or tab not found in $match");
					}
					} else if(strpos($match," ")>0) {
					$pivot = strpos($match," ");
					dbg::msg("space");
					} else
						dbg::dump(strpos($match, " ")."space or tab not found in $match");
					*/
					//dbg::dump($pivot,false);
					dbg::test($pivot!=0, "Pivot is zero");
					$tag = substr($match,0,$pivot);
					$value = substr($match,$pivot+1);
					//dbg::dump($tag."=>".$value,false);
					array_push($commentTags, array($tag=>$value));
					$commentsFound = true;
				}
			} else if(($token[0]==T_FUNCTION ||$token[0]==T_CLASS)&& $commentsFound) {
				$functionFound = true;
				$commentsFound = false;
			} else if($token[0]==307 && $functionFound) {
				while($tag = array_pop($commentTags)) {
					//dbg::dump($tag,false);
					$this->commentTags[$token[1]] = array($tag);
				}
				$functionFound = false;
			}
		}
		//dbg::dump($this->commentTags);
	}
}
