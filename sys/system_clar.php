<?php

function action_requestclar(){
	global $invalidchars;
	if(!isset($_POST["query"]) || empty($_POST["query"]) || !isset($_POST["problem"])){ $_SESSION["message"][] = "Clarification Request Error : Insufficient Data"; return; }
	if($_SESSION["tid"]==0){ $_SESSION["message"][] = "Clarification Request Error : You must be logged in to post your queries here."; return; }
	if(eregi($invalidchars,$_POST["query"])){ $_SESSION["message"][] = "Clarification Request Error : Invalid characters in Query."; return; }
	mysql_query("INSERT INTO clar VALUES (".time().",".$_SESSION["tid"].",".$_POST["problem"].",\"".filter($_POST["query"])."\",'','Private',".time().");");
	//$_SESSION["message"][] = "Clarification Request Successful";
	action_clarcache();
	}
	
function action_clarcache(){
	global $admin;
	$team=array(); $data = mysql_query("SELECT tid,teamname FROM teams"); if(is_resource($data)) while($temp = mysql_fetch_array($data)) $team[$temp["tid"]] = filter($temp["teamname"]);
	$prob=array(0=>"General"); $data = mysql_query("SELECT pid,name FROM problems WHERE status='Active'"); if(is_resource($data)) while($temp = mysql_fetch_array($data)) $prob[$temp["pid"]] = filter($temp["name"]);
	if(isset($admin["clarpublic"]) && $admin["clarpublic"]>=0) $limit=$admin["clarpublic"]; else $limit=2;
	$data = mysql_query("SELECT * FROM (SELECT * FROM clar WHERE clar.access='Public' and (clar.pid=0 or (SELECT status FROM problems WHERE problems.pid=clar.pid)='Active') ORDER BY time DESC LIMIT 0,$limit) as latest ORDER BY time ASC ");
	$filedata="";
	if(is_resource($data) && mysql_num_rows($data)>0) while($temp = mysql_fetch_array($data)){
		$filedata.="<table><tr><td style='text-align:left;'><b><a href='?display=submissions&tid=$temp[tid]'>".$team[$temp["tid"]]."</a> (";
		$filedata.=($temp["pid"]==0)?"General":"<a href='?display=problem&pid=$temp[pid]'>".$prob[$temp["pid"]]."</a>";
		$filedata.=")</b> : ".($temp["query"])."";
		if(!empty($temp["reply"])) $filedata.="</td></tr><tr><td style='text-align:left;'><i><b>Response</b> : ".($temp["reply"])."</i>";
		$filedata.="</td></tr></table>";
		}
	else $filedata.="<table><tr><td>Not Available</td></tr></table>";
	$admin["cache-clarlatest"] = $filedata;
	}

function action_updateclar(){
	global $invalidchars;
	if($_SESSION["status"]!="Admin"){ $_SESSION["message"][] = "Clarification Update Error : You need to be an Administrator to perform this action."; return; }
	if(!isset($_POST["field"]) || empty($_POST["field"])){ $_SESSION["message"][] = "Clarification Update Error : Insufficient Data."; return; }
	if($_POST["field"]=="Reply"){
		if(!isset($_POST["time"]) || empty($_POST["time"])){ $_SESSION["message"][] = "Clarification Update Error : Insufficient Data."; return; }
		if(eregi($invalidchars,$_POST["value"])){ $_SESSION["message"][] = "Clarification Update Error : Invalid characters in Reply."; return; }
		if(!empty($_POST["value"])) mysql_query("UPDATE clar SET time=".time().",reply='".addslashes(filter($_POST["value"]))."' WHERE time=".$_POST["time"]);
		else mysql_query("UPDATE clar SET reply='' WHERE time=".$_POST["time"]);
		}
	if($_POST["field"]=="Status"){
		if(!isset($_POST["time"]) || empty($_POST["time"]) || !isset($_POST["value"]) || empty($_POST["value"])){ $_SESSION["message"][] = "Clarification Update Error : Insufficient Data."; return; }
		mysql_query("UPDATE clar SET access='".$_POST["value"]."',time=".time()." WHERE time=".$_POST["time"]);
		}
	if($_POST["field"]=="Clear") mysql_query("UPDATE clar SET access='Delete'");
	action_clarcache();
	}
	
function display_clarifications(){
	global $admin,$invalidchars;
	echo "<center><h2>Clarifications</h2>";
	
	if($_SESSION["status"]=="Admin") $total = mysql_query("SELECT count(*) as total FROM clar WHERE access!='Delete'");
	else $total = mysql_query("SELECT count(*) as total FROM clar WHERE access!='Delete' AND (access='Public' OR tid=".$_SESSION["tid"].")");
	$total = mysql_fetch_array($total); $total = $total["total"];
	if(isset($admin["clarpage"])) $limit = $admin["clarpage"]; else $limit = 10;
	if(isset($_GET["page"]) && is_numeric($_GET["page"])) $page = max(0,$_GET["page"]); else $page = ceil($total/$limit)-1;
	$pagenav="";
	if($page>0) $pagenav.= "<a href='?display=clarifications&page=".($page-1)."'>Previous Page</a>"; else $pagenav.="Previous Page";
	$pagenav.=" : ".($page+1)."/".ceil($total/$limit)." : ";
	if(($page+1)*$limit<$total) $pagenav.= "<a href='?display=clarifications&page=".($page+1)."'>Next Page</a>"; else $pagenav.="Next Page";
	echo $pagenav."<br><br>";
	
	$team=array(); $data = mysql_query("SELECT tid,teamname FROM teams"); if(is_resource($data)) while($temp = mysql_fetch_array($data)) $team[$temp["tid"]] = filter($temp["teamname"]);
	$prob=array(0=>"General"); $data = mysql_query("SELECT pid,name FROM problems WHERE status='Active'"); if(is_resource($data)) while($temp = mysql_fetch_array($data)) $prob[$temp["pid"]] = filter($temp["name"]);
	echo "<table width=100%><tr><th>Query / Response</th>".($_SESSION["status"]=="Admin"?"<th width='120px'>Status</th>":"")."</tr>";
	if($_SESSION["status"]=="Admin") $data = mysql_query("SELECT time,clar.tid,pid,query,reply,access,status FROM clar,teams WHERE clar.tid=teams.tid AND clar.access!='Delete' ORDER BY time ASC LIMIT ".($page*$limit).",$limit");
	else $data = mysql_query("SELECT time,clar.tid,pid,query,reply,access,status FROM clar,teams WHERE clar.tid=teams.tid AND clar.access!='Delete' AND (clar.access='Public' OR clar.tid=".$_SESSION["tid"].") ORDER BY time ASC LIMIT ".($page*$limit).",$limit");
	#if($limit!="" && $count>$clarpage){ echo "<tr><td><a href='?display=clarifications&all=1'>Show older Clarifications ... </a></td>".($_SESSION["status"]=="Admin"?"<td></td>":"")."</tr><tr><td colspan=2></td></tr>"; }
	if(is_resource($data)) while($temp = mysql_fetch_array($data)){
		if(!isset($temp["tid"])) continue; if(!isset($temp["pid"])) continue;
		if($_SESSION["status"]=="Admin") $highlight = (($temp["reply"]=="" && $temp["status"]!="Admin")?" class='highlight' ":"");
		else $highlight = (($temp["tid"]==$_SESSION["tid"])?" class='highlight' ":"");
		echo "<tr><td style='text-align:left;' ".($highlight)."><b><a href='?display=submissions&tid=$temp[tid]'>".$team[$temp["tid"]]."</a> (".($temp["pid"]==0?"General":"<a href='?display=problem&pid=$temp[pid]'>".$prob[$temp["pid"]]."</a>").")</b> : ".$temp["query"]."</td>";
		if($_SESSION["status"]=="Admin"){
			echo "<td ".($highlight)." rowspan=".(empty($temp["reply"])?1:2)."><input type='button' value='Reply' style='padding:0px;' onClick=\"reply=prompt('Enter response (previous response will be overwritten) : ','".$temp["reply"]."'); if(reply.match(/".eregi_replace("\n","\\n",$invalidchars)."/)!=null){ alert('Reply contains invalid characters.'); } else if(reply!=null){ f=document.forms['updateclar']; f.field.value='Reply'; f.time.value=$temp[time]; f.value.value=reply; f.submit(); }\"> ";
			echo "<select onChange=\"if(confirm('Are you sure you wish to perform this operation?')){ f=document.forms['updateclar']; f.field.value='Status'; f.time.value=$temp[time]; f.value.value=this.value; f.submit(); }\">";
			echo ($temp["access"]=="Public"?"<option selected='selected'>Public</option><option>Private</option>":"<option>Public</option><option selected='selected'>Private</option>");
			echo "<option>Delete</option></select>";
			}
		echo "</tr>";
		if(!empty($temp["reply"])) echo "<tr><td style='text-align:left;'><i>".($temp["reply"]!=""?"<b>Judge's Response</b> : ":"").$temp["reply"]."</i></td></tr>";
		echo "<tr><td colspan=2></td></tr>";
		if($_SESSION["status"]=="Admin") echo "<form name='updateclar' action='?action=updateclar' method='post'><input type='hidden' name='field'><input type='hidden' name='time'><input type='hidden' name='value'></form>";
		}
	echo "</table><br>$pagenav<br><br>";
	
	if($_SESSION["status"]=="Admin") echo "<input type='button' value='Delete All Clarification Requests' onClick=\"if(confirm('Are you sure you wish to Delete All Clarification Requests?')){ f=document.forms['updateclar']; f.field.value='Clear'; f.submit(); }\"><br><br>";
	if($_SESSION["tid"]){
		echo "<script>function validate_clar(){ var str=\"\";
			if($(\"textarea[name='query']\").val().match(/".eregi_replace("\n","\\n",$invalidchars)."/)!=null) str+=\"Query contains invalid characters.\\n\";
			if(str==\"\") return true; alert(str); return false;
			}</script>";
		echo "<form action='?action=requestclar' method='post' onSubmit='return validate_clar();'>";
		echo "<table><tr><th>Team Name</th><td style='text-align:left;'>".$_SESSION["teamname"]."</td></tr>";
		echo "<tr><th>Select Problem</th><td><select name='problem' style='width:300px;'><option value=0>General (No Specific Problem)</option>";
		$data = mysql_query("SELECT * FROM problems WHERE status='Active' ORDER BY pid");
		if(is_resource($data)) while($problem = mysql_fetch_array($data)) echo "<option value='".$problem["pid"]."'>".filter($problem["name"])."</option>";
		echo "</select></td></tr>";
		echo "<tr><th>Query</th><td><textarea name='query' placeholder=\"Type your query here\" style='width:300;min-width:300;max-width:300;height:100;min-height:100;'></textarea></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='Submit' style='width:100%;'></td></tr></table></form>";
		}
	echo "<div class='small'>This feature exists only to provide contestants a way to communicate with the judges in case of any ambiguity regarding problems or the contest itself.
		<br>The Query Text cannot contain single or double quotes.
		<br>Please refrain from using this feature unless absolutely necessary. Ensure that your problem has not already been answered in the <a href='?display=faq'>FAQ Section</a>.
		</div>";
	echo "</center>";
	}
	
?>