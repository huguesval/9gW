<?php
/**
* 9gW - 9gag whisperer
* Hugues Valentin
**/

class NgW {

	var $average = 4;
	var $it = 200;

	private function removeALLnt($content){
		//$content = str_replace(chr(0xA0),"",$content);
		//$content = str_replace(chr(0x09),"",$content);
		$content = trim($content);
		return $content;
	}
	
	private function getContent($id){
		$content = '';
		$fp = fopen('http://9gag.com/gag/'.$id, 'r');
		//$fp = fopen('http://9gag.com/gag/123', 'r');
		//$fp = fopen('test.html', 'r');
		while(!feof($fp))
			$content .= $this->removeALLnt(fgets($fp,4096));
		fclose($fp);
		return $content;
	}
	
	private function humanToUnixDate($date){
		$now = time();

		if(strpos($date,'hour') !== false){
			$date = substr($date,0,strpos($date,'hour')-1);
			$date = $now - $date*3600;
		}	
		else if(strpos($date,'day') !== false){
			$date = substr($date,0,strpos($date,'day')-1);
			$date = $now - 24*3600*$date;
		}
		else if(strpos($date,'month') !== false){
			if(strpos($date,'month') > 5){
				if(strpos($date,'years') !== false){
					$years = substr($date,0,strpos($date,'years')-1);
					$now = $now - 24*3600*365*$years;
					$date = substr($date,8,strlen($date));
				}
				else{
					$years = substr($date,0,strpos($date,'year')-1);
					$now = $now - 24*3600*365*$years;
					$date = substr($date,7,strlen($date));
				}
				$date = substr($date,0,strpos($date,'month')-1);
				$date = $now - 24*3600*30*$date;
			}
			else{
					$date = substr($date,0,strpos($date,'month')-1);
					$date = $now - 24*3600*30*$date;
			}
		}
		else if(strpos($date,'year') !== false){
			$date = substr($date,0,strpos($date,'year')-1);
			$date = $now - 24*3600*365*$date;
		}
		else
			return 0;
			
		return $date;
	}
	
	
	private function getData($type,$content){
		if($type == 'url'){
			if(strpos($content, '<div class="img-wrap"><a href="/random"><img src="') !== false){
					$content = substr($content,strpos($content, '<div class="img-wrap"><a href="/random"><img src="')+50,strlen($content));
					if(strpos($content, '" alt="') !== false)
						$content = substr($content,0,strpos($content, '" alt="'));
					else
						$content = 'error';
				}
				else
					$content = 'error';
		}
		else if($type == 'name'){
			if(strpos($content, '<div class="post-info-pad"><h1>') !== false){
				$content = substr($content,strpos($content, '<div class="post-info-pad"><h1>')+31,strlen($content));
				if(strpos($content, '</h1><p><a') !== false){
					$content = substr($content,0,strpos($content, '</h1><p><a'));
				}	
				else
					$content = 'error5';
			}
			else
				$content = 'error6';
		}
		else if($type == 'date'){
			if(strpos($content, '<span class="seperator">|</span>') !== false){
				$content = substr($content,strpos($content, '<span class="seperator">|</span>')+32,strlen($content));
				if(strpos($content, '<span class="comment"><fb') !== false){
					$content = substr($content,0,strpos($content, '<span class="comment"><fb'));
					$content = intval($this->humanToUnixDate($content));
				}	
				else
					$content = 'error3';
			}
			else
				$content = 'error4';
		}
		else if($type == 'like'){
			if(strpos($content, 'votes="') !== false){
				$content = substr($content,strpos($content, 'votes="')+7,strlen($content));
				if(strpos($content, '" score="') !== false){
					$content = substr($content,0,strpos($content, '" score="'));
					if($content == '&bull;') $content = 0;
				}	
				else
					$content = 'error1';
			}
			else
				$content = 'error2';
		}
		else
			return 'Hug stop, you\'re screwed';
		
		return	$content;
	}
	
	/**
	* Validate that Chuck isn't here
	*/
	private function chuckIsHere($content){
		if(strpos("has been removed by Chuck Norris",$content) !== false)
			return true;
		else return false;
	}
	
	private function nSFW($content){
		if(strpos("This post may contain content that is inappropriate for some users",$content)!== false) return true;
		else return false;
		
	}
	
	private function isDir($date){
		if(!is_dir(date('m-Y',$date))){
			mkdir(date('m-Y',$date));
			chmod(date('m-Y',$date), 0755);
		}
		return true;
	}
	
	private function saveImg($i,$url,$name,$date){
			$this->isDir($date);
			$img = file_get_contents($url);
			$fp = fopen(date('m-Y',$date).'/'.$i.'-'.$name.'.'.substr($url,-3), "w");
			fwrite($fp, $img);
			fclose($fp);
	}
	
	private function average($avg){
		$this->average = ($this->average * $this->it + $avg) / ($this->it+1);
		$this->it++;
		return true;
	}
	
	private function attack($z){
		//940000
		$output = '';
		$y = 974000-(500*$z);
		for($i = $y;$i>($y-500);$i--){
			$content = $this->getContent($i);
			if(!$this->nSFW($content) && !$this->chuckIsHere($content) && $this->getData('like',$content) > $this->average){
				$url = $this->getData('url',$content);
				$name = $this->getData('name',$content);
				$date = $this->getData('date',$content);
				if($date != "error4" && $date != "error3"){
					$this->saveImg($i,$url,$name,$date);
					$output .= $i.' OK Done ! <br />';
					$this->average($this->getData('like',$content));
				}
				else $output .= $i.' chuck is here<br />';
			}
			else{
				//debug : 
				if($this->chuckIsHere($content))
					$output .= $i.' chuck is here<br />';
				else
					$output .= $i.' not funny : '.$this->getData('like',$content).'<br />';
			}
		}
		//sleep(0.5);
		return $output;
	}
	
	public function NgW($start){
		echo "Let's start<br />";
		for($i=$start;$i<($start+200);$i++){
			$output = $this->attack($i);
			$fp = fopen('tmp/tmp'.$i.'.txt','w');
			fwrite($fp,str_replace("<br />","\r\n",$output));
			fclose($fp);
		}
	}
}


?>