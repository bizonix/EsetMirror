<?php
include 'config.php';
$x=new EsetUpdatesDownloader();
print_r($x->downloadedFiles);
class EsetKeysGrab
{
    var $KeySites = array(
    		'http://feeds.feedburner.com/EsetNod32KeyslatestUpdatesfreshUsernamejem?format=xml' => "%Username:(\w+-.*)</?.*br/>.*Password:(.*)</?.*br/>%Ui",
    		"http://hhuu.net/" => "%Username:(.*)<br/>Password:(.*)<%Ui",
    		"http://page2rss.com/page?url=hhuu.net/" => "%page2rssins\">Username:(.*)<?Password:(.*)</p>%Ui",
    		"http://keys-nod.blogspot.com/" => "%<br>Username:(.*)<br>Password:(.*)<br>%Ui",
    		"http://www.nod327.net/category/nod32-key" => "%<p>Username:(.*)<br/>Password:(.*)</p>%Ui",
    		"http://www.nod325.com/" => "%<p>Username:(.*)<br/>Password:(.*)</p>%Ui",
    		"http://www.myarticle.com/Internet/Security/the-newest-nod32-keys.html" => "%<p>Username:(.*)<.*<p>Password:(.*)<%Ui",
            "http://www.nnvv.org/?p=66"=>"%<p>Username:(.*)<br/>Password:(.*)</p>%Ui",
            "http://www.fiaofiao.net/?p=105"=>"%<p>Username:(.*)<br/>Password:(.*)<%Ui",
            
    );
    var $xzKeys = array();
    var $pwd;
    
    function __construct()
    {
        $this->LoadDB();
        $this->getKey();
    }
    function __destruct()
    {
        $this->DB['Temp']=array();
        $this->SaveDB();
    }
    function getKey()
    {
        if (empty($this->DB['Good']))
        {
            $this->grabKeys();
        }
        foreach($this->DB['Good'] as $this->pwd)
        {
            if($this->checkPwd($this->pwd))
            {
                return $this->pwd;
            }
        }
    }
    function grabKeys()
    {
		echo "[*]Start Grab keys\n";
		foreach ($this->KeySites as $Site => $SitePreg)
        {
			if (preg_match_all($SitePreg, preg_replace("/\s+/", "", html_entity_decode(str_replace(array('nod32key'),array('Password'),file_get_contents($Site)) , null, 'UTF-8')) , $SiteKeys)) 
            {
				echo "[!]" . $Site;
				unset($SiteKeys[0]);
                //print_r(array($Site=>$SiteKeys));
                $pre=count($this->DB['Temp']);

			    for ($i = 0;$i < count($SiteKeys[1]);$i++) 
                {
					$login  = strtoupper(trim(str_replace('<br>', '', $SiteKeys[1][$i])));
					$passwd = trim($SiteKeys[2][$i]);
                    $pwd=$login.":".$passwd;
                    
					if (strlen($login) < 20 || strlen($login) < 20) 
					{
                        if(!in_array($pwd,$this->DB['Temp']))
                        {
                            array_push($this->DB['Temp'],$pwd);
                        }
					}
                    else
                    {
                        array_push($this->xzKeys,$pwd);
                    }
                }
				echo " OK Found ".(count($this->DB['Temp'])-$pre)." Keys\n";
            }
            else
            {
    		    echo "[-]" . $Site . "\n";
            }
        }
        $this->checkKeys();
    }
	function SaveDB() 
	{
		$this->DB['Date'] = date('d.m.Y');
		file_put_contents('KeyDB.txt', json_encode($this->DB));
	}
	function LoadDB() 
	{
		if (file_exists('KeyDB.txt')) 
		{
			$this->DB= json_decode(file_get_contents('KeyDB.txt') , true);
		}
		else
        {
            $this->DB= array(
		        'Date' => date('d.m.Y') ,
		        'Good' => array() ,
		        'Ban'  => array(),
                'Temp' => array()
		    );
        }
	}
    function checkPwd($KeysToChek)
    {

        if(false !== @file_get_contents('http://update.eset.com/v3-rel-sta/mod_015_amon/em015_32_n1.nup', false, stream_context_create(array(
        	'http' => array(
        		'method' => 'HEAD',
        		'header' => 'Authorization: Basic ' . base64_encode($KeysToChek) ,
        	)
        ))))
        {
            if( $unbanned = array_search($KeysToChek, $this->DB['Ban']) )
            {
                unset($this->DB['Ban'][$unbanned]);
				echo "[-]Banned Key $KeysToChek unBanned\n";
            }
            else
            {
    			echo "[!]Good Key " . $KeysToChek . "\n";
            }
            $this->addKeysToDB($KeysToChek,'Good');
            return true;
            
        }
        else
        {
            if( $banned = array_search($KeysToChek, $this->DB['Good']) )
            {
                unset($this->DB['Good'][$banned]);
				echo "[-]Good Key $KeysToChek Banned\n";
            }
            else
            {
    		     echo "[-]Key $KeysToChek Banned\n";
            }
            $this->addKeysToDB($KeysToChek,'Ban');
            return false;
        }
        
    }
    function checkKeys()
    {
		echo "[*]KeyDB [" . $this->DB['Date'] . "]\n";
        if (!empty($this->DB['Good']))
        {
            $this->DB['Temp'] = array_merge($this->DB['Temp'], $this->DB['Good']);
        }
        if (empty($this->DB['Temp'])) 
        {
        	$this->grabKeys();
        }
		echo "[*]Start Check ".count($this->DB['Temp'])." keys\n";
		foreach ($this->DB['Temp'] as $KeysToChek)
        {
            if (!empty($this->DB['Ban']) && in_array($KeysToChek,$this->DB['Ban']))
            {
				echo "[-]Key $KeysToChek Banned\n";
				continue;
            }
            
            $this->checkPwd($KeysToChek);
		}
        
    }
    function addKeysToDB($Key,$Section='Temp')
    {
        if(!in_array($Key,$this->DB[$Section]))
        {
            array_push($this->DB[$Section],$Key);
            $this->SaveDB();
        }
        else
        {
			echo "[-]$Section Key $Key alredy exist in DB\n";
        }
    }
}
class EsetUpdatesDownloader
{
	var $downloadedFiles = array();
	var $allowed_languages = array(
		'english' => '1033',
		'russian' => '1049',
	//	'Italian' => '1040',
	//	'Chinese Simplified' => '2052',
	//	'Chinese Traditional' => '1028',
	//	'Spanish' => '3082',
	//	'Slovak' => '1051',
	//	'Polish' => '1045',
	//	'French' => '1036',
	//	'Czech' => '1029',
	//	'German' => '1031',
	//	'Turkish' => '1055',
	//	'Hungarian' => '1038',
	//	'Croatian' => '1050',
	//	'Dutch' => '1043',
	//	'Portuguese Brasil' => '1046',
	//	'Slovenian' => '1060',
	//	'Ukrainian' => '1058',
	//	'Romanian' => '1048',
	//	'Swedish' => '1053',
	//	'Danish' => '1030',
	//	'Finnish' => '1035',
	//	'Norwegian' => '1044',
	//	'Bulgarian' => '1026'
	);
	var $EsetServers = array(
		'global' => 'um18.eset.com'//update
	);

	function __construct() 
	{
        if(!file_exists(unrar))
        {
            die("unrar not found!\n");
        }
        $this->EsetKey = new EsetKeysGrab();
        
		return $this->run();
	}
	public function run() 
	{
		$this->ProcessUpdate('/eset_upd/update.ver');
		$this->ProcessUpdate('/eset_upd/pcu/update.ver');
        
		$this->ProcessUpdate('/eset_upd/v4/update.ver');
		$this->ProcessUpdate('/eset_upd/v4/pcu/update.ver');
		
        $this->ProcessUpdate('/eset_upd/v5/update.ver');
		$this->ProcessUpdate('/eset_upd/v5/pcu/update.ver');
		
        $this->ProcessUpdate('/eset_upd/v6/update.ver');
		$this->ProcessUpdate('/eset_upd/v6/pcu/update.ver');
	}
	function ProcessUpdate($fileName) 
	{
		echo "http://dl.dropbox.com/u/" . DropBox . $fileName . "\n";
		$this->MoveUpdateFile(DOWNLOAD_DIRECTORY . $fileName, OLD_UPDATE_DIRECTORY . $fileName);
		$this->DownloadFile($fileName);
        $this->ExtractFile($fileName);
        
        if(!isset($this->pwd))
        {
            if(!$this->EsetKey->pwd)
            {
                $this->pwd=$this->EsetKey->getKey();
            }
            else
            {
                $this->pwd=$this->EsetKey->pwd;
            }
        }
        
        $oldUpdateData = $this->ReadUpdateFile(OLD_UPDATE_DIRECTORY.$fileName);
        $newUpdateData = $this->ReadUpdateFile(DOWNLOAD_DIRECTORY.$fileName);
        
        $this->setEsetServers($newUpdateData['HOSTS']['Other']);
        
        
        $this->DownloadChanges($oldUpdateData, $newUpdateData);
	}
    function RemoveLanguages()
    {
        file_put_contents(self::$UpFolder . '/' . $EsetVersion . '/update.ver', str_replace('file=', 'file=' . self::$dropbox, preg_replace('/\[HOSTS\].*\[Expire\].*\[/Us', '[', file_get_contents("update.ver"))));
    }
    function ExtractFile($fileName)
    {
        $downloadedUpdateFile = DOWNLOAD_DIRECTORY.$fileName;
        $downloadedUpdatePath = DOWNLOAD_DIRECTORY.dirname($fileName);
        $compressedFile = $downloadedUpdateFile. '.rar';

        $this->MoveUpdateFile($downloadedUpdateFile, $compressedFile);

        system(unrar.' e -y -inul ' . $compressedFile . ' ' . $downloadedUpdatePath);
        
    }
	function MoveUpdateFile($moveFrom, $moveTo) 
	{
		$this->EnsureDirExists($moveTo);
		if (is_file($moveTo))
        {
            unlink($moveTo);
        }
		if (is_file($moveFrom))
        {
            rename($moveFrom, $moveTo);
        }
	}
	function EnsureDirExists($fileName) 
	{
		$directory = dirname($fileName);
		if (!is_dir($directory)) 
		{
			mkdir($directory, 0777, true);
		}
	}
    function setEsetServers($EsetServers)
    {
        if($EsetServers!==null)
        {
            if (preg_match_all('%200@http://(.*)/(eset_upd|nod_upd)%U', $EsetServers, $Servers))
            {
                $this->EsetServers['Other']=$Servers[1];
            }
            else
            {
                echo "error:".$EsetServers;
            }
        }
    }
    function getEsetServer()
    {
        if(!empty($this->EsetServers['Other']))
        {
            $Server = array_shift($this->EsetServers['Other']);
            array_push($this->EsetServers['Other'],$Server);
            return $Server;
        }
        return $this->EsetServers['global'];
    }
	
    function DownloadFile($fileName, $nobody = false) 
	{

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->getEsetServer().$fileName);
        
		if ($nobody) 
		{
			curl_setopt($ch, CURLOPT_NOBODY, true);
    		curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		else
		{
            $file=DOWNLOAD_DIRECTORY.$fileName;
    		$this->EnsureDirExists($file);
            
			if (!($fp = fopen($file, "w"))) 
			{
				die('cannot save to file ' . $file);
			}
			$req_header = array(
				"Connection: close",
				"X-NOD32-Mode: passive",
				"Pragma: no-cache",
				"Cache-Control: no-cache"
			);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $req_header);
		}
		
		curl_setopt($ch, CURLOPT_USERAGENT, 'ESS Update (Windows; U; 32bit; VDB 12334; BPC 4.2.67.10; OS: 5.1.2600 SP 3.0 NT; CH 0.0; LNG 1049; x32c; UPD AUTOSELECT; APP eav; BEO 1; CPU 5964; ASP 0.10; FW 0.0; PX 0; PUA 1)');
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
        {
    		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
		curl_setopt($ch, CURLOPT_COOKIEJAR, HOME."/cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEFILE, HOME."/cookie.txt");
        
		if (isset($this->pwd)) 
		{
			curl_setopt($ch, CURLOPT_USERPWD, $this->pwd);
		}
		$res = curl_exec($ch);
		if (curl_errno($ch)) 
		{
			print "Error: [" . date('d.m.Y H:i') . "]\n" . 'Url: ' . $fileName . ' ' . curl_error($ch) . "\n";
			return false;
		}
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		curl_close($ch);
		if ($file) 
		{
			fclose($fp);
		}
		if (in_array($code,array(401,404))) 
		{
			if (is_file($file))
            {
                unlink($file);
            }
			echo "file $fileName code:$code\n";
			print_r($this->getEsetServer());
			die("\nerr 404\n");
		}
		if ($nobody) 
		{
			if ($code == $nobody) return true;
			elseif ($length == $nobody) 
			{
				return true;
			}
			else
			{
				if (!in_array($length, array(
					188
				))) print "need update $fileName remote:$length local:$nobody\n";
				return false;
			}
		}
		return $res;
	}
	function DownloadChanges($newUpdateData, $oldUpdateData) 
	{
        if($newUpdateData===null)
        {
            die('Non UpdateData');
        }
		foreach ($newUpdateData as $SectionKey => $SectionValue) 
		{
			if(isset($SectionValue['file'])) 
			{
				$newVersionId = $newBuild = $newBuildMs = $newFile = $newSize = False;
				$LocalFile = DOWNLOAD_DIRECTORY . $SectionValue['file'];
				$newFile = !file_exists($LocalFile);
				$LocalSize = $newFile?0:filesize($LocalFile);

				if (isset($SectionValue['language']) && !(in_array(strtolower($SectionValue['language']) , $this->allowed_languages) || in_array(strtolower($SectionValue['language']) , array_keys($this->allowed_languages)))) 
				{
					if (file_exists($LocalFile)) 
					{
						echo "need del " . $SectionValue['file'] . "\n";
						unlink($LocalFile);
					}
					continue;
				}
				foreach (array(
					'VersionId',
					'Build',
					'BuildMs',
					'Size'
				) as $RequestSection) 
				{
					$Section = strtolower($RequestSection);
					if (isset($SectionValue[$Section]) && isset($oldUpdateData[$SectionKey][$Section])) 
					{
						eval('$new' . $RequestSection . '=' . $this->toBool((int)$SectionValue[$Section] > (int)$oldUpdateData[$SectionKey][$Section]) . ';');
						$newBySize = ((isset($SectionValue['size']) && $SectionValue['size'] != $LocalSize) || (isset($oldUpdateData[$SectionKey]['size']) && $oldUpdateData[$SectionKey]['size'] != $LocalSize));
						if ($newSize || $newBuildMs || $newBuild || $newVersionId || $newFile || $newBySize) 
						{
							printf("new%s: %s -> %s File: %s, newFile: %s, newBySize: %s (LocalSize:%s,newSize:%s,oldSize:%s)\n", 
                                    $RequestSection,
                                    $oldUpdateData[$SectionKey][$Section],
                                    $SectionValue[$Section],
                                    $SectionValue['file'],
                                    $this->toBool($newFile),
                                    $this->toBool($newBySize),
                                    $LocalSize,
                                    $SectionValue['size'],
                                    $oldUpdateData[$SectionKey]['size']
                            );
                            if(!in_array($SectionValue['file'],$this->downloadedFiles))
                            {
                                $this->DownloadFile($SectionValue['file']);
                                array_push($this->downloadedFiles,$SectionValue['file']);
                            }
						}
					}
				}
			}
		}
	}
    function toBool($value)
    {
         return ($value===true? 'True' : 'False');
    }
	function ReadUpdateFile($file) 
	{
        if(!file_exists($file))
        {
            return null;
        }
        if(function_exists('parse_ini_file'))
        {
            return parse_ini_file($file, true, INI_SCANNER_RAW);
        }
		foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $curstr) 
		{
			$curstr = trim($curstr);
			if ($curstr[0] == '[') 
			{
				$section = substr($curstr, 1, strlen($curstr) - 2);
				$updatever[$section] = array();
			}
			else
			{
				list($key, $value) = split('=', $curstr);
				$updatever[$section][trim($key) ] = trim($value);
			}
		}
		return $updatever;
	}
}
