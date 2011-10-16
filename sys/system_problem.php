<?php

function display_problem(){
	global $admin,$currentmessage,$defaultlang,$maxcodesize;
	if($admin["mode"]=="Lockdown" && $_SESSION["tid"]==0){
		$_SESSION["message"] = $currentmessage; $_SESSION["message"][] = "Access Denied : The contest is currently in Lockdown Mode. Please try again later.";
		echo "<script>window.location='?display=faq';</script>"; return;
		}
	if(isset($_GET["pid"]) && !empty($_GET["pid"])) $pid = $_GET["pid"]; else $pid = 0;
	if($_SESSION["status"]=="Admin") $data = mysql_query("SELECT * FROM problems WHERE pid=$pid ");
	else $data = mysql_query("SELECT * FROM problems WHERE status='Active' and pid=$pid");
	if($pid!=0) if(!is_resource($data) || mysql_num_rows($data)!=1){
		$_SESSION["message"] = $currentmessage; $_SESSION["message"][] = "Problem Access Error : The problem you requested does not exist or is currently inactive.";
		$pid=0;
		}
	if($pid==0){
		echo "<center>";
		//echo "<h2>Problems Index</h2>";
		if(($g=mysql_getdata("SELECT distinct pgroup FROM problems WHERE status='Active' ORDER BY pgroup"))!=NULL){
			$t=array(); foreach($g as $gn) $t[] = $gn["pgroup"]; $g=$t; unset($t);
			if(in_array("",$g)){ unset($g[array_search("",$g)]); $g[]=""; } // make groups array.
			echo "<div class='filter'><b>Select Group<b> : <select style='width:150px;' id='category-select' onChange=\"$('input#query').attr('value',''); problem_search(); if(this.value==0){ $('span.group').slideDown(250); } else { for(i=1;i<=".count($g).";i++){ if(this.value=='group'+i) $('span#group'+i).slideDown(250); else $('span#group'+i).slideUp(250); } }\"><option value=0>All Groups</option>";
			foreach($g as $i=>$gn) echo "<option value='group".($i+1)."'>".eregi_replace("^#[0-9]+ ","",($gn==""?"Unclassified":$gn))."</option>";
			echo "</select> <input placeholder='Enter Search Term Here' id='query' onKeyUp=\"$('#category-select').val(0); $('span.group').slideDown(250); problem_search();\" style='text-align:center;'> <input type='button' value='Clear' onClick=\"$('input#query').attr('value',''); problem_search();\"></div>";
			if(($s=mysql_getdata("SELECT distinct pid FROM runs WHERE tid=$_SESSION[tid] AND access!='deleted'"))==NULL) $s = array();
			else { $t=array(); foreach($s as $sp) $t[]=$sp["pid"]; $s=$t; unset($t); }
			echo "<div id='probindex' class='probindex'>";
			echo "<div class='probheaders2' style='display:none;'><h2>Search Results</h2>";
			echo "<table><th>Problem ID</th><th>Problem Name</th><th>Problem Code</th><th>Problem Type</th><th>Score</th><th>Statistics</th></tr></table></div>";
			foreach($g as $i=>$gn){
				echo "<span id='group".($i+1)."' class='group'><div class='probheaders1'><h2><a href='?display=submissions&pgr=".urlencode($gn)."'>Problem Group : ".eregi_replace("^#[0-9]+ ","",($gn==""?"Unclassified":$gn))."</a></h2>";
				echo "<table><th>Problem ID</th><th>Problem Name</th><th>Problem Code</th><th>Problem Type</th><th>Score</th><th>Statistics</th></tr></table></div>";
				$data = mysql_query("SELECT * FROM problems WHERE status='Active' and pgroup='".$gn."' ORDER BY pid");
				while($problem = mysql_fetch_array($data)){
					$t = mysql_query("SELECT (SELECT count(*) FROM runs WHERE pid=$problem[pid] AND result='AC' AND access!='deleted') as ac, (SELECT count(*) FROM runs WHERE pid=$problem[pid] AND access!='deleted') as tot");
					if(is_resource($t) && mysql_num_rows($t) && $t=mysql_fetch_array($t)) $statistics = "<a title='Accepted Solutions / Total Submissions' href='?display=submissions&pid=$problem[pid]'>".$t["ac"]." / ".$t["tot"]."</a>"; else $statistics = "NA";
					echo "<div class='problem'><table class='submission'><tr class='".($_SESSION["tid"]==0?"UT":(in_array($problem["pid"],$s)?"AC":"UT"))."'><td><a href='?display=problem&pid=$problem[pid]'>$problem[pid]</a></td><td><a href='?display=problem&pid=$problem[pid]'>".stripslashes($problem["name"])."</a></td><td><a href='?display=problem&pid=$problem[pid]'>".stripslashes($problem["code"])."</a></td>";
					if($admin["mode"]!="Active"||$_SESSION["status"]=="Admin") echo "<td><a href='#' onClick=\"$('input#query').attr('value','".$problem["type"]."'); problem_search();\">".stripslashes($problem["type"])."</td>"; else echo "<td>NA</td>";
					echo "<td><a href='?display=problem&pid=$problem[pid]'>$problem[score]</a></td><td>$statistics</td></tr></table></div>";
					}
				echo "</span>";
				}
			}
		echo "</div>";
		return;
		}
	$data = mysql_fetch_array($data);
	$statement = filter(stripslashes($data["statement"]));
	$statement = eregi_replace("\n","<br>",$statement);
	foreach(array("b","u","i","br","code","ul","ol","li") as $tag){
		$statement = eregi_replace(filter("<$tag>"),"<$tag>",$statement);
		$statement = eregi_replace(filter("</$tag>"),"</$tag>",$statement);
		}
	$statement = eregi_replace(filter("<image ?/?>"),"<img src='?image=$pid' />",$statement);
	$t = mysql_query("SELECT (SELECT count(*) FROM runs WHERE pid=$pid AND result='AC' AND access!='deleted') as ac, (SELECT count(*) FROM runs WHERE pid=$pid AND access!='deleted') as tot");
	if(is_resource($t) && mysql_num_rows($t) && $t=mysql_fetch_array($t)) $statistics = "<a title='Accepted Solutions / Total Submissions' href='?display=submissions&pid=$pid'>".$t["ac"]."/".$t["tot"]."</a>"; else $statistics = "NA";
	echo "<center><h2>Problem : $data[name] (".eregi_replace("^#[0-9]+ ","",$data["pgroup"])." Group)</h2><table width=100%>
		<tr><th>Problem ID</th><th>$pid</th><th>Input File Size</th><th>".display_filesize(strlen($data["input"]))."</th><th><a href='?display=submissions&pid=$pid'>Submissions</a></th><th>$statistics</th></tr>
		<tr><th>Problem Code</th><th>$data[code]</th><th>Time Limit</th><th>$data[timelimit] sec</th><th>Points</th><th>$data[score]</th></tr>
		<tr><td colspan=20 style='text-align:left;padding:20;'>".$statement."</td></tr>
		<tr><td colspan=20 style='text-align:left;padding:20;'><b>Language(s) Allowed</b> : ";
	echo eregi_replace("Brain","Brainf**k",eregi_replace(",",", ",$data["languages"]));
	echo "</td></tr>";
	
	$languages="";
	if(isset($data["languages"]))
		foreach(explode(",",$data["languages"]) as $l)
			if($l=="Brain"){
				if($l==$defaultlang) $languages.="<option value='Brain' selected='selected'>Brainf**k</option>";
				else $languages.="<option value='Brain'>Brainf**k</option>";
				}
			else if($l==$defaultlang) $languages.="<option selected='selected'>".$defaultlang."</option>";
			else $languages.="<option>$l</option>";
	
	$data = mysql_query("SELECT * FROM clar WHERE access='Public' and clar.pid=$pid ORDER BY time ASC");
	if(is_resource($data) && mysql_num_rows($data)>0) if(mysql_num_rows($data)){
		echo "<tr><th colspan=20><a href='?display=clarifications'>Clarifications</a></th></tr><tr><td colspan=20 style='text-align:left;padding:20;'>";
		while($temp = mysql_fetch_array($data)){
			$teamname = mysql_query("SELECT teamname FROM teams WHERE tid=".$temp["tid"]); if(is_resource($teamname) && mysql_num_rows($teamname)==1){ $teamname = mysql_fetch_array($teamname); $teamname=$teamname["teamname"]; } else $teamname="Anonymous";
			echo "<p><b><a href='?display=submissions&tid=".$temp["tid"]."'>".filter($teamname)."</a></b> : $temp[query]";
			if(!empty($temp["reply"])) echo "<br><i><b>Response</b> : $temp[reply]</i>";
			echo "</p>";
			}
		echo "</td></tr>";
		}
	echo "</table><br></center>";
	if($_SESSION["tid"]==0) echo "<center>Please login to submit solutions.</center>";
	else if($admin["mode"]!="Active" && $admin["mode"]!="Passive" && $_SESSION["status"]!="Admin") echo "<center>You can not submit solutions at the moment as the contest is not running. Please try again later.</center>";
	else {
		$placeholder = "Paste your code here, or select a file to upload.";
		$editcode = "";
		if(isset($_GET["edit"])){
			$rid = $_GET["edit"]; if(!is_numeric($rid)) $rid=0;
			$t = mysql_query("SELECT tid,language,code,access FROM runs WHERE rid=$rid AND access!='deleted'");
			if(is_resource($t) && mysql_num_rows($t)==1){
				$run = mysql_fetch_array($t);
				if($_SESSION["tid"]==$run["tid"] || $run["access"]=="public" || $_SESSION["status"]=="Admin") $editcode=eregi_replace("<","&lt;",$run["code"]);
				if($run["language"]=="Brain") $run["language"]="Brainf**k";
				$languages = str_replace(">$run[language]</option>"," selected='selected'>$run[language]</option>",str_replace(" selected='selected'","",$languages));
				}
			}
		global $extension,$codemirror; $extcompare="";
		foreach($extension as $lang=>$ext) $extcompare.="if(ext=='$ext'){ $('select#code_lang').attr('value','".($lang)."'); } ";
		echo "<center><h2>Submit Solution : $data[name]</h2>
			<script>function code_validate(){ if(document.forms['submitcode'].code_file.value=='' && document.forms['submitcode'].code_text.value==''){ alert('Code file not specified and textarea empty. Cannot submit nothing.'); return false; } if(document.forms['submitcode'].code_lang.value=='Java' && document.forms['submitcode'].code_file.value=='' && document.forms['submitcode'].code_text.value!=''){ x = prompt('You are copy-pasting Java code here. Please enter the class name you have used so\\nthat the server can create a source file of the same name while evaluating your code :\\n '); if(!x) return false; else $('input#code_name').val(x); } return true; }</script>
			<form action='?action=submitcode' method='post' name='submitcode' enctype='multipart/form-data' onSubmit=\"return code_validate();\"><input type='hidden' name='code_pid' value='$pid'>
			<table width=100%><tr><th>Language</th><th><select id='code_lang' name='code_lang'>".$languages."</select></th><input type='hidden' name='MAX_FILE_SIZE' value='$maxcodesize' />";
		echo "<th>Code File</th><th><input type='file' name='code_file' style='width:200px;' onChange=\"if(this.value!=''){ filename = this.value.split('.'); ext = filename[filename.length-1]; $extcompare }\" /></th></tr>
			<tr><td colspan=20 style='text-align:left;'><textarea id='code_text' name='code_text' class='code' placeholder=\"$placeholder\" onChange=\"if(this.value!='') $('select#code_mode').attr('value','Text');\">$editcode</textarea></td></tr></table>
			<table width=100%> <input type='hidden' name='code_name' id='code_name' value='code'>
			<tr><th><div class='small'>If you submit both File and Text (copy-pasted in the above textarea), the Text will be ignored.</div></th><th><input type='submit' value='Submit Code'></th></tr>
			</table></form></center>";
		}
	}
	
	
	
	
	
	
function action_makeproblem(){
	global $sessionid,$invalidchars,$maxfilesize;
	foreach($_POST as $key=>$value) if(eregi("^make_",$key)) if(empty($_POST[$key]) && $key!="make_type") { $_SESSION["message"][] = "Problem Creation Error : Insufficient (Text) Data"; return; }
	if( !isset($_FILES["make_file_statement"]) || !isset($_FILES["make_file_input"]) || !isset($_FILES["make_file_output"]) ){ $_SESSION["message"][] = "Problem Creation Error : Insufficient (File) Data"; return; }
	foreach($_POST as $key=>$value) if(eregi("^make_",$key) && eregi($invalidchars,$value) ){ $_SESSION["message"][] = "Problem Creation Error : Value of $key contains invalid characters."; return; }
	foreach($_POST as $key=>$value) if(eregi("^make_",$key) && strlen($value)>30 ){ $_SESSION["message"][] = "Problem Creation Error : Value of $key too long."; return; }
	$temp1 = $temp2 = array(); foreach($_POST as $key=>$value) if(eregi("^make_",$key) && !eregi("^make_file_",$key)){ $temp1[]=eregi_replace("^make_","",$key); $temp2[]=filter($value); }
	foreach(array("statement","input","output") as $item){
		$ext = file_upload("make_file_$item","sys/temp/".$sessionid."_$item","text/plain",$maxfilesize);
		if($ext==-1) { $_SESSION["message"][] = "Problem Creation Error : Could not upload $item File"; return; }
		$temp1[]=$item; $temp2[]=addslashes(file_get("sys/temp/".$sessionid."_$item.$ext")); unlink("sys/temp/".$sessionid."_$item.$ext");
		}
	$ext = file_upload("make_file_image","sys/temp/image","image/jpeg,image/gif,image/png",$maxfilesize);
	if($ext!=-1){
		$f = fopen("sys/temp/image.$ext","rb"); $temp1[]="image"; $temp2[] = base64_encode(fread($f,filesize("sys/temp/image.$ext"))); fclose($f);
		$temp1[] = "imgext"; $temp2[] = $ext;
		}
	//echo "INSERT INTO problems (".implode($temp1,",").",status) VALUES ('".implode($temp2,"','")."','Inactive')";
	mysql_query("INSERT INTO problems (".implode($temp1,",").",status) VALUES ('".implode($temp2,"','")."','Inactive')");
	//$pid = mysql_insert_id();
	{ $_SESSION["message"][] = "Problem Creation Successful"; return; }
	}
	
	
	
	
	
	
function action_updateproblem(){
	global $sessionid,$invalidchars,$maxfilesize;
	if(!isset($_POST["update_pid"]) || empty($_POST["update_pid"])){ $_SESSION["message"][] = "Problem Updation Error : Insufficient Data"; return; }
	foreach($_POST as $key=>$value) if(eregi("^update_",$key) && eregi($invalidchars,$value) ){ $_SESSION["message"][] = "Problem Updation Error : Value of $key contains invalid characters."; return; }
	foreach($_POST as $key=>$value) if(eregi("^update_",$key) && strlen($value)>30 && $key!="update_languages"){ $_SESSION["message"][] = "Problem Updation Error : Value of $key too long."; return; }

	$pid = $_POST["update_pid"];
	foreach($_POST as $key=>$value) if(eregi("^update_",$key) && !eregi("^update_file_",$key) && $key!="update_pid" && $key!="update_delete"){
		mysql_query("UPDATE problems SET ".eregi_replace("^update_","",$key)."='".addslashes(eregi_replace("\"","\'",$value))."' WHERE pid=$pid");
		}

	foreach(array("statement","input","output") as $item){
		$ext = file_upload("update_file_$item","sys/temp/".$sessionid."_$item","text/plain",$maxfilesize);
		if($ext==-1) continue;
		mysql_query("UPDATE problems SET $item='".addslashes(file_get("sys/temp/".$sessionid."_$item.$ext"))."' WHERE pid=$pid");
		unlink("sys/temp/".$sessionid."_$item.$ext");
		}
	
	$ext = file_upload("update_file_image","sys/temp/image","image/jpeg,image/gif,image/png",$maxfilesize);
	if($ext!=-1){
		$f = fopen("sys/temp/image.$ext","rb"); $img = base64_encode(fread($f,filesize("sys/temp/image.$ext"))); fclose($f);
		mysql_query("UPDATE problems SET image='$img', imgext='$ext' WHERE pid=$pid");
		}
		
	if(0)if(isset($_POST["update_status"]) && $_POST["update_status"]=="Delete") mysql_query("DELETE FROM problems WHERE pid=$pid");
	{ $_SESSION["message"][] = "Problem Updation Successful"; return; }
	}

	
	
	
	
	
	
	
	
	

function action_makeproblemactive(){
	if($_SESSION["status"]!="Admin"){ $_SESSION["message"][] = "Access Denied : You need to be an Administrator to perform this action."; return; }
	if(isset($_GET["pid"]) && !empty($_GET["pid"]) && is_numeric($_GET["pid"])) mysql_query("UPDATE problems SET status='Active' WHERE pid=".$_GET["pid"]);
	else { $_SESSION["message"][] = "Problem Status Updation Error : Insufficient Data"; return; }
	}

function action_makeprobleminactive(){
	if($_SESSION["status"]!="Admin"){ $_SESSION["message"][] = "Access Denied : You need to be an Administrator to perform this action."; return; }
	if(isset($_GET["pid"]) && !empty($_GET["pid"]) && is_numeric($_GET["pid"])) mysql_query("UPDATE problems SET status='Inactive' WHERE pid=".$_GET["pid"]);
	else { $_SESSION["message"][] = "Problem Status Updation Error : Insufficient Data"; return; }
	}
	
?>