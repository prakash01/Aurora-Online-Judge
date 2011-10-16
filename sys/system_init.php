<?php

if(!isset($_SERVER["SSL_PROTOCOL"])) header("Location: https://".$_SERVER["SERVER_ADDR"].$_SERVER["REQUEST_URI"]);

$mysql_hostname = "127.0.0.1";
$mysql_username = "root";
$mysql_password = "aurora";
$mysql_database = "aurora";

$admin_teamname = "Judge";
$admin_password = "aurora";

// Include System Files
$f=opendir("sys");
while($e=readdir($f)){
	if($e=="."||$e=="..") continue;
	if(eregi("^system",$e) && file_exists("sys/$e") && $e!="system_init.php")
		include("sys/$e");
	}
closedir($f);

$admin = array();
$currentmessage;
$ajaxlogout=0;
$fullresult = array("AC"=>"Accepted","WA"=>"Wrong Answer","PE"=>"Presentation Error","CE"=>"Compilation Error","RTE"=>"Run Time Error","TLE"=>"Time Limit Exceeded");
$extension = array("Brain"=>"b","C"=>"c","C++"=>"cpp","Java"=>"java","JavaScript"=>"js","Pascal"=>"pas","Perl"=>"pl","PHP"=>"php","Python"=>"py","Ruby"=>"rb","Text"=>"txt");
$invalidchars = "[^A-Za-z0-9`~!@#$%^&*()_+|=\\\{\}\[\];:<>?,./ 	\n-]";
$invalidchars_js = eregi_replace("\n","\\n",$invalidchars);
$defaultlang = "C++";
$maxcodesize = 1024*100; // 100KB - Max size of source code
$maxfilesize = 3*1024*1024; // 3MB - Max size of input, output, statement, image
// application/octet-stream

// To add new results or new languages, the only change reqd is to add them to the $fullresult and $extension arrays above.

session_start();
$sessionid = (SID=="")?$_REQUEST["PHPSESSID"]:eregi_replace("PHPSESSID=","",SID);

$phpself = $_SERVER["SERVER_ADDR"].$_SERVER["PHP_SELF"];
if(!isset($_SESSION["tid"])) $_SESSION = array("tid"=>0,"teamname"=>"","status"=>"","time"=>time(),"ghost"=>0);
if(!isset($_SESSION["message"])) $_SESSION["message"] = array();

mysql_initiate();

if($_SESSION["status"]!="Admin" && (!isset($_SESSION["ghost"])||!$_SESSION["ghost"])) mysql_query("INSERT INTO logs VALUES (".(time()).",'".$_SERVER["REMOTE_ADDR"]."','".$_SESSION["tid"]."','".addslashes(json_encode($_GET))."')");

if(isset($_GET["download"]) && is_numeric($_GET["download"])){
	if($_SESSION["status"]=="Admin") $t = mysql_query("SELECT * FROM runs WHERE rid=$_GET[download] and access!='deleted'");
	else $t = mysql_query("SELECT * FROM runs WHERE rid=$_GET[download] and ((access='private' and tid=$_SESSION[tid]) or access='public')");
	if(is_resource($t) && mysql_num_rows($t)){
		$t = mysql_fetch_array($t);
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		//header("Cache-Control: private",false);
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename= \"Aurora Online Judge - Run ID $_GET[download].".$extension[$t["language"]]."\"");
		header("Content-Length: ".strlen($t["code"]));
		header("Content-Transfer-Encoding: binary"); 
		echo $t["code"];
		}
	else echo "Aurora Online Judge : The requested code could not be found in the Database.";
	exit;
	}

if(isset($_GET["image"]) && is_numeric($_GET["image"])){
	$pid = $_GET["image"];
	if($_SESSION["status"]=="Admin") $t = mysql_query("SELECT * FROM problems WHERE pid='$pid'");
	else $t = mysql_query("SELECT * FROM problems WHERE pid='$pid' and status='Active'");
	if(is_resource($t) && mysql_num_rows($t)==1){
		$t = mysql_fetch_array($t);
		$img = imagecreatefromstring(base64_decode($t["image"]));
		if($t["imgext"]=="jpg"||$t["imgext"]=="jpeg"){ header("Content-Type: image/jpeg"); imagejpeg($img); }
		if($t["imgext"]=="png"){ header("Content-Type: image/png"); imagejpeg($img); }
		if($t["imgext"]=="gif"){ header("Content-Type: image/gif"); imagejpeg($img); }
		imagedestroy($img);
		}
	else echo "Aurora Online Judge : The requested image could not be found in the Database.";
	exit;
	}

if(isset($_GET["action"])){
	if($_GET["action"]=="register") action_register();
	if($_GET["action"]=="login") action_login();
	if($_GET["action"]=="logout") action_logout();
	if($_GET["action"]=="updatepass") action_updatepass();
	
	if($_GET["action"]=="ajaxrefresh"){ echo action_ajaxrefresh(0); mysql_terminate(); exit; }
	
	if($_GET["action"]=="submitcode") $rid = action_submitcode();
	if($_GET["action"]=="makeproblem") action_makeproblem();
	if($_GET["action"]=="updateproblem") action_updateproblem();
	
	if($_GET["action"]=="updateteam") action_updateteam();
	if($_GET["action"]=="updateaccount") action_updateaccount();
	if($_GET["action"]=="updatewaiting") action_updatewaiting();
	if($_GET["action"]=="updatecontest") action_updatecontest();
	if($_GET["action"]=="updatestyle") action_updatestyle();
	
	if($_GET["action"]=="rejudge") action_rejudge();
	if($_GET["action"]=="makecodepublic") action_makecodepublic();
	if($_GET["action"]=="makecodeprivate") action_makecodeprivate();
	if($_GET["action"]=="makecodedeleted") action_makecodedeleted();
	if($_GET["action"]=="makeactive") action_makeproblemactive();
	if($_GET["action"]=="makeinactive") action_makeprobleminactive();
	
	if($_GET["action"]=="requestclar") action_requestclar();
	if($_GET["action"]=="updateclar") action_updateclar();
	
	if($_GET["action"]=="commitdata") action_commitdata();
	if($_GET["action"]=="commitupdate") action_commitupdate();
	
	if($_GET["action"]=="noticeupdate") action_noticeupdate();
	
	mysql_terminate();
	if($_GET["action"]=="register" && in_array("Registeration Successful",$_SESSION["message"])) 
		if($_SESSION["status"]=="Admin") $_SERVER["HTTP_REFERER"]="?display=adminteam"; else $_SERVER["HTTP_REFERER"]="?display=faq";
	if($_GET["action"]=="submitcode" && in_array("Code Submission Successful",$_SESSION["message"])) $_SERVER["HTTP_REFERER"]="?display=code&rid=$rid";
	if($_GET["action"]=="noticeupdate" && in_array("Notice Updation Successful",$_SESSION["message"])) $_SERVER["HTTP_REFERER"]="?display=notice";
	if(isset($_SERVER["HTTP_REFERER"])) header("Location: ".$_SERVER["HTTP_REFERER"]); else header("Location: ".$phpself);
	exit;
	}

?>