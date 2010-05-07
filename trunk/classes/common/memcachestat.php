<?php
define('MEMCACHE_DATE_FORMAT','Y/m/d H:i:s');


class memcachestat
{
	var $_server = null;
	var $_port = null;

	function memcachestat() {
		$ini = eZINI::instance('memcache.ini');
		$this->_server = $ini->variable( 'MemcacheSettings', 'Server' );
		$this->_port = $ini->variable( 'MemcacheSettings', 'Port' );
	}

	function get_memcache_servers() {
		return $this->_server.":".$this->_port;
	}

	function sendMemcacheCommand($command){
		$error = 0;
		$errstr = '';
		$s = @fsockopen($this->_server,$this->_port, $error, $errstr, 1);
		if (!$s){
			die("Cant connect to:".$_server.':'.$_port);
			return false;
		}

		fwrite($s, $command."\r\n");

		$buf='';
		while ((!feof($s))) {
			$buf .= fgets($s, 256);
			if (strpos($buf,"END\r\n")!==false){ // stat says end
				break;
			}
			if (strpos($buf,"DELETED\r\n")!==false || strpos($buf,"NOT_FOUND\r\n")!==false){ // delete says these
				break;
			}
			if (strpos($buf,"OK\r\n")!==false){ // flush_all says ok
				break;
			}
		}
		fclose($s);
		return $this->parseMemcacheResults($buf);
	}

	function parseMemcacheResults($str){

		$res = array();
		$lines = explode("\r\n",$str);
		$cnt = count($lines);
		for($i=0; $i< $cnt; $i++){
			$line = $lines[$i];
			$l = explode(' ',$line,3);
			if (count($l)==3){
				$res[$l[0]][$l[1]]=$l[2];
				if ($l[0]=='VALUE'){ // next line is the value
					$res[$l[0]][$l[1]] = array();
					list ($flag,$size)=explode(' ',$l[2]);
					$res[$l[0]][$l[1]]['stat']=array('flag'=>$flag,'size'=>$size);
					$res[$l[0]][$l[1]]['value']=$lines[++$i];
				}
			}elseif($line=='DELETED' || $line=='NOT_FOUND' || $line=='OK'){
				return $line;
			}
		}
		return $res;

	}

	function getMemcacheStats(){
		$r = $this->sendMemcacheCommand('stats');
		$res = $r['STAT'];
		return $res;
	}

	function duration($ts) {
		global $time;
		$years = (int)((($time - $ts)/(7*86400))/52.177457);
		$rem = (int)(($time-$ts)-($years * 52.177457 * 7 * 86400));
		$weeks = (int)(($rem)/(7*86400));
		$days = (int)(($rem)/86400) - $weeks*7;
		$hours = (int)(($rem)/3600) - $days*24 - $weeks*7*24;
		$mins = (int)(($rem)/60) - $hours*60 - $days*24*60 - $weeks*7*24*60;
		$str = '';
		if($years==1) $str .= "$years year, ";
		if($years>1) $str .= "$years years, ";
		if($weeks==1) $str .= "$weeks week, ";
		if($weeks>1) $str .= "$weeks weeks, ";
		if($days==1) $str .= "$days day,";
		if($days>1) $str .= "$days days,";
		if($hours == 1) $str .= " $hours hour and";
		if($hours>1) $str .= " $hours hours and";
		if($mins == 1) $str .= " 1 minute";
		else $str .= " $mins minutes";
		return $str;
	}

	function bsize($s) {
		foreach (array('','K','M','G') as $i => $k) {
			if ($s < 1024) break;
			$s/=1024;
		}
		return sprintf("%5.1f %sBytes",$s,$k);
	}
}
?>
