<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 4.3.1
*/$qc=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($qc||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Dg=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Dg)$$X=$Dg;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection(){global$e;return$e;}function
adminer(){global$b;return$b;}function
idf_unescape($Lc){$id=substr($Lc,-1);return
str_replace($id.$id,$id,substr($Lc,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
remove_slashes($Qe,$qc=false){if(get_magic_quotes_gpc()){while(list($y,$X)=each($Qe)){foreach($X
as$bd=>$W){unset($Qe[$y][$bd]);if(is_array($W)){$Qe[$y][stripslashes($bd)]=$W;$Qe[]=&$Qe[$y][stripslashes($bd)];}else$Qe[$y][stripslashes($bd)]=($qc?$W:stripslashes($W));}}}}function
bracket_escape($Lc,$_a=false){static$sg=array(':'=>':1',']'=>':2','['=>':3','"'=>':4');return
strtr($Lc,($_a?array_flip($sg):$sg));}function
charset($e){return(version_compare($e->server_info,"5.5.3")>=0?"utf8mb4":"utf8");}function
h($Mf){return
str_replace("\0","&#0;",htmlspecialchars($Mf,ENT_QUOTES,'utf-8'));}function
nbsp($Mf){return(trim($Mf)!=""?h($Mf):"&nbsp;");}function
nl_br($Mf){return
str_replace("\n","<br>",$Mf);}function
checkbox($E,$Y,$Na,$fd="",$be="",$Ra="",$gd=""){$K="<input type='checkbox' name='$E' value='".h($Y)."'".($Na?" checked":"").($gd?" aria-labelledby='$gd'":"").($be?' onclick="'.h($be).'"':'').">";return($fd!=""||$Ra?"<label".($Ra?" class='$Ra'":"").">$K".h($fd)."</label>":$K);}function
optionlist($fe,$uf=null,$Kg=false){$K="";foreach($fe
as$bd=>$W){$ge=array($bd=>$W);if(is_array($W)){$K.='<optgroup label="'.h($bd).'">';$ge=$W;}foreach($ge
as$y=>$X)$K.='<option'.($Kg||is_string($y)?' value="'.h($y).'"':'').(($Kg||is_string($y)?(string)$y:$X)===$uf?' selected':'').'>'.h($X);if(is_array($W))$K.='</optgroup>';}return$K;}function
html_select($E,$fe,$Y="",$ae=true,$gd=""){if($ae)return"<select name='".h($E)."'".(is_string($ae)?' onchange="'.h($ae).'"':"").($gd?" aria-labelledby='$gd'":"").">".optionlist($fe,$Y)."</select>";$K="";foreach($fe
as$y=>$X)$K.="<label><input type='radio' name='".h($E)."' value='".h($y)."'".($y==$Y?" checked":"").">".h($X)."</label>";return$K;}function
select_input($wa,$fe,$Y="",$De=""){return($fe?"<select$wa><option value=''>$De".optionlist($fe,$Y,true)."</select>":"<input$wa size='10' value='".h($Y)."' placeholder='$De'>");}function
confirm(){return" onclick=\"return confirm('".'Are you sure?'."');\"";}function
print_fieldset($t,$nd,$Sg=false,$be=""){echo"<fieldset><legend><a href='#fieldset-$t' onclick=\"".h($be)."return !toggle('fieldset-$t');\">$nd</a></legend><div id='fieldset-$t'".($Sg?"":" class='hidden'").">\n";}function
bold($Ga,$Ra=""){return($Ga?" class='active $Ra'":($Ra?" class='$Ra'":""));}function
odd($K=' class="odd"'){static$s=0;if(!$K)$s=-1;return($s++%2?$K:'');}function
js_escape($Mf){return
addcslashes($Mf,"\r\n'\\/");}function
json_row($y,$X=null){static$rc=true;if($rc)echo"{";if($y!=""){echo($rc?"":",")."\n\t\"".addcslashes($y,"\r\n\t\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'null');$rc=false;}else{echo"\n}\n";$rc=true;}}function
ini_bool($Pc){$X=ini_get($Pc);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$K;if($K===null)$K=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$K;}function
set_password($Pg,$O,$V,$G){$_SESSION["pwds"][$Pg][$O][$V]=($_COOKIE["adminer_key"]&&is_string($G)?array(encrypt_string($G,$_COOKIE["adminer_key"])):$G);}function
get_password(){$K=get_session("pwds");if(is_array($K))$K=($_COOKIE["adminer_key"]?decrypt_string($K[0],$_COOKIE["adminer_key"]):false);return$K;}function
q($Mf){global$e;return$e->quote($Mf);}function
get_vals($I,$c=0){global$e;$K=array();$J=$e->query($I);if(is_object($J)){while($L=$J->fetch_row())$K[]=$L[$c];}return$K;}function
get_key_vals($I,$f=null,$ig=0){global$e;if(!is_object($f))$f=$e;$K=array();$f->timeout=$ig;$J=$f->query($I);$f->timeout=0;if(is_object($J)){while($L=$J->fetch_row())$K[$L[0]]=$L[1];}return$K;}function
get_rows($I,$f=null,$j="<p class='error'>"){global$e;$eb=(is_object($f)?$f:$e);$K=array();$J=$eb->query($I);if(is_object($J)){while($L=$J->fetch_assoc())$K[]=$L;}elseif(!$J&&!is_object($f)&&$j&&defined("PAGE_HEADER"))echo$j.error()."\n";return$K;}function
unique_array($L,$v){foreach($v
as$u){if(preg_match("~PRIMARY|UNIQUE~",$u["type"])){$K=array();foreach($u["columns"]as$y){if(!isset($L[$y]))continue
2;$K[$y]=$L[$y];}return$K;}}}function
escape_key($y){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$y,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($y);}function
where($Z,$l=array()){global$e,$x;$K=array();foreach((array)$Z["where"]as$y=>$X){$y=bracket_escape($y,1);$c=escape_key($y);$K[]=$c.($x=="sql"&&preg_match('~^[0-9]*\\.[0-9]*$~',$X)?" LIKE ".q(addcslashes($X,"%_\\")):($x=="mssql"?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($l[$y],q($X))));if($x=="sql"&&preg_match('~char|text~',$l[$y]["type"])&&preg_match("~[^ -@]~",$X))$K[]="$c = ".q($X)." COLLATE ".charset($e)."_bin";}foreach((array)$Z["null"]as$y)$K[]=escape_key($y)." IS NULL";return
implode(" AND ",$K);}function
where_check($X,$l=array()){parse_str($X,$Ma);remove_slashes(array(&$Ma));return
where($Ma,$l);}function
where_link($s,$c,$Y,$ce="="){return"&where%5B$s%5D%5Bcol%5D=".urlencode($c)."&where%5B$s%5D%5Bop%5D=".urlencode(($Y!==null?$ce:"IS NULL"))."&where%5B$s%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($d,$l,$N=array()){$K="";foreach($d
as$y=>$X){if($N&&!in_array(idf_escape($y),$N))continue;$ua=convert_field($l[$y]);if($ua)$K.=", $ua AS ".idf_escape($y);}return$K;}function
cookie($E,$Y,$qd=2592000){global$ba;return
header("Set-Cookie: $E=".urlencode($Y).($qd?"; expires=".gmdate("D, d M Y H:i:s",time()+$qd)." GMT":"")."; path=".preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]).($ba?"; secure":"")."; HttpOnly; SameSite=lax",false);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session(){if(!ini_bool("session.use_cookies"))session_write_close();}function&get_session($y){return$_SESSION[$y][DRIVER][SERVER][$_GET["username"]];}function
set_session($y,$X){$_SESSION[$y][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($Pg,$O,$V,$i=null){global$Gb;preg_match('~([^?]*)\\??(.*)~',remove_from_uri(implode("|",array_keys($Gb))."|username|".($i!==null?"db|":"").session_name()),$B);return"$B[1]?".(sid()?SID."&":"").($Pg!="server"||$O!=""?urlencode($Pg)."=".urlencode($O)."&":"")."username=".urlencode($V).($i!=""?"&db=".urlencode($i):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($A,$C=null){if($C!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$C;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($I,$A,$C,$Ye=true,$ec=true,$kc=false,$hg=""){global$e,$j,$b;if($ec){$If=microtime(true);$kc=!$e->query($I);$hg=format_time($If);}$Hf="";if($I)$Hf=$b->messageQuery($I,$hg);if($kc){$j=error().$Hf;return
false;}if($Ye)redirect($A,$C.$Hf);return
true;}function
queries($I){global$e;static$Te=array();static$If;if(!$If)$If=microtime(true);if($I===null)return
array(implode("\n",$Te),format_time($If));$Te[]=(preg_match('~;$~',$I)?"DELIMITER ;;\n$I;\nDELIMITER ":$I).";";return$e->query($I);}function
apply_queries($I,$S,$ac='table'){foreach($S
as$Q){if(!queries("$I ".$ac($Q)))return
false;}return
true;}function
queries_redirect($A,$C,$Ye){list($Te,$hg)=queries(null);return
query_redirect($Te,$A,$C,$Ye,false,!$Ye,$hg);}function
format_time($If){return
sprintf('%.3f s',max(0,microtime(true)-$If));}function
remove_from_uri($te=""){return
substr(preg_replace("~(?<=[?&])($te".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($F,$nb){return" ".($F==$nb?$F+1:'<a href="'.h(remove_from_uri("page").($F?"&page=$F".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($F+1)."</a>");}function
get_file($y,$vb=false){$oc=$_FILES[$y];if(!$oc)return
null;foreach($oc
as$y=>$X)$oc[$y]=(array)$X;$K='';foreach($oc["error"]as$y=>$j){if($j)return$j;$E=$oc["name"][$y];$pg=$oc["tmp_name"][$y];$fb=file_get_contents($vb&&preg_match('~\\.gz$~',$E)?"compress.zlib://$pg":$pg);if($vb){$If=substr($fb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$If,$ef))$fb=iconv("utf-16","utf-8",$fb);elseif($If=="\xEF\xBB\xBF")$fb=substr($fb,3);$K.=$fb."\n\n";}else$K.=$fb;}return$K;}function
upload_error($j){$_d=($j==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($j?'Unable to upload a file.'.($_d?" ".sprintf('Maximum allowed file size is %sB.',$_d):""):'File does not exist.');}function
repeat_pattern($Be,$od){return
str_repeat("$Be{0,65535}",$od/65535)."$Be{0,".($od%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\\0-\\x8\\xB\\xC\\xE-\\x1F]~',$X));}function
shorten_utf8($Mf,$od=80,$Qf=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$od).")($)?)u",$Mf,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$od).")($)?)",$Mf,$B);return
h($B[1]).$Qf.(isset($B[2])?"":"<i>...</i>");}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($Qe,$Mc=array()){while(list($y,$X)=each($Qe)){if(!in_array($y,$Mc)){if(is_array($X)){foreach($X
as$bd=>$W)$Qe[$y."[$bd]"]=$W;}else
echo'<input type="hidden" name="'.h($y).'" value="'.h($X).'">';}}}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$lc=false){$K=table_status($Q,$lc);return($K?$K:array("Name"=>$Q));}function
column_foreign_keys($Q){global$b;$K=array();foreach($b->foreignKeys($Q)as$m){foreach($m["source"]as$X)$K[$X][]=$m;}return$K;}function
enum_input($U,$wa,$k,$Y,$Ub=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$k["length"],$vd);$K=($Ub!==null?"<label><input type='$U'$wa value='$Ub'".((is_array($Y)?in_array($Ub,$Y):$Y===0)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($vd[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$Na=(is_int($Y)?$Y==$s+1:(is_array($Y)?in_array($s+1,$Y):$Y===$X));$K.=" <label><input type='$U'$wa value='".($s+1)."'".($Na?' checked':'').'>'.h($b->editVal($X,$k)).'</label>';}return$K;}function
input($k,$Y,$p){global$e,$zg,$b,$x;$E=h(bracket_escape($k["field"]));echo"<td class='function'>";if(is_array($Y)&&!$p){$ta=array($Y);if(version_compare(PHP_VERSION,5.4)>=0)$ta[]=JSON_PRETTY_PRINT;$Y=call_user_func_array('json_encode',$ta);$p="json";}$gf=($x=="mssql"&&$k["auto_increment"]);if($gf&&!$_POST["save"])$p=null;$zc=(isset($_GET["select"])||$gf?array("orig"=>'original'):array())+$b->editFunctions($k);$wa=" name='fields[$E]'";if($k["type"]=="enum")echo
nbsp($zc[""])."<td>".$b->editInput($_GET["edit"],$k,$wa,$Y);else{$rc=0;foreach($zc
as$y=>$X){if($y===""||!$X)break;$rc++;}$ae=($rc?" onchange=\"var f = this.form['function[".h(js_escape(bracket_escape($k["field"])))."]']; if ($rc > f.selectedIndex) f.selectedIndex = $rc;\" onkeyup='keyupChange.call(this);'":"");$wa.=$ae;$Ec=(in_array($p,$zc)||isset($zc[$p]));echo(count($zc)>1?"<select name='function[$E]' onchange='functionChange(this);'".on_help("getTarget(event).value.replace(/^SQL\$/, '')",1).">".optionlist($zc,$p===null||$Ec?$p:"")."</select>":nbsp(reset($zc))).'<td>';$Rc=$b->editInput($_GET["edit"],$k,$wa,$Y);if($Rc!="")echo$Rc;elseif(preg_match('~bool~',$k["type"]))echo"<input type='hidden'$wa value='0'>"."<input type='checkbox'".(in_array(strtolower($Y),array('1','t','true','y','yes','on'))?" checked='checked'":"")."$wa value='1'>";elseif($k["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$k["length"],$vd);foreach($vd[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$Na=(is_int($Y)?($Y>>$s)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$E][$s]' value='".(1<<$s)."'".($Na?' checked':'')."$ae>".h($b->editVal($X,$k)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$k["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$E'$ae>";elseif(($fg=preg_match('~text|lob~',$k["type"]))||preg_match("~\n~",$Y)){if($fg&&$x!="sqlite")$wa.=" cols='50' rows='12'";else{$M=min(12,substr_count($Y,"\n")+1);$wa.=" cols='30' rows='$M'".($M==1?" style='height: 1.2em;'":"");}echo"<textarea$wa>".h($Y).'</textarea>';}elseif($p=="json"||preg_match('~^jsonb?$~',$k["type"]))echo"<textarea$wa cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';else{$Bd=(!preg_match('~int~',$k["type"])&&preg_match('~^(\\d+)(,(\\d+))?$~',$k["length"],$B)?((preg_match("~binary~",$k["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$k["unsigned"]?1:0)):($zg[$k["type"]]?$zg[$k["type"]]+($k["unsigned"]?0:1):0));if($x=='sql'&&$e->server_info>=5.6&&preg_match('~time~',$k["type"]))$Bd+=7;echo"<input".((!$Ec||$p==="")&&preg_match('~(?<!o)int~',$k["type"])&&!preg_match('~\[\]~',$k["full_type"])?" type='number'":"")." value='".h($Y)."'".($Bd?" data-maxlength='$Bd'":"").(preg_match('~char|binary~',$k["type"])&&$Bd>20?" size='40'":"")."$wa>";}}}function
process_input($k){global$b;$Lc=bracket_escape($k["field"]);$p=$_POST["function"][$Lc];$Y=$_POST["fields"][$Lc];if($k["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($k["auto_increment"]&&$Y=="")return
null;if($p=="orig")return($k["on_update"]=="CURRENT_TIMESTAMP"?idf_escape($k["field"]):false);if($p=="NULL")return"NULL";if($k["type"]=="set")return
array_sum((array)$Y);if($p=="json"){$p="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$k["type"])&&ini_bool("file_uploads")){$oc=get_file("fields-$Lc");if(!is_string($oc))return
false;return
q($oc);}return$b->processInput($k,$Y,$p);}function
fields_from_edit(){global$Fb;$K=array();foreach((array)$_POST["field_keys"]as$y=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$y];$_POST["fields"][$X]=$_POST["field_vals"][$y];}}foreach((array)$_POST["fields"]as$y=>$X){$E=bracket_escape($y,1);$K[$E]=array("field"=>$E,"privileges"=>array("insert"=>1,"update"=>1),"null"=>1,"auto_increment"=>($y==$Fb->primary),);}return$K;}function
search_tables(){global$b,$e;$_GET["where"][0]["op"]="LIKE %%";$_GET["where"][0]["val"]=$_POST["query"];$wc=false;foreach(table_status('',true)as$Q=>$R){$E=$b->tableName($R);if(isset($R["Engine"])&&$E!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$J=$e->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($Q),array())),1));if(!$J||$J->fetch_row()){if(!$wc){echo"<ul>\n";$wc=true;}echo"<li>".($J?"<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$E</a>\n":"$E: <span class='error'>".error()."</span>\n");}}}echo($wc?"</ul>":"<p class='message'>".'No tables.')."\n";}function
dump_headers($Kc,$Id=false){global$b;$K=$b->dumpHeaders($Kc,$Id);$re=$_POST["output"];if($re!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($Kc).".$K".($re!="file"&&!preg_match('~[^0-9a-z]~',$re)?".$re":""));session_write_close();ob_flush();flush();return$K;}function
dump_csv($L){foreach($L
as$y=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$L[$y]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$L)."\r\n";}function
apply_sql_function($p,$c){return($p?($p=="unixepoch"?"DATETIME($c, '$p')":($p=="count distinct"?"COUNT(DISTINCT ":strtoupper("$p("))."$c)"):$c);}function
get_temp_dir(){$K=ini_get("upload_tmp_dir");if(!$K){if(function_exists('sys_get_temp_dir'))$K=sys_get_temp_dir();else{$pc=@tempnam("","");if(!$pc)return
false;$K=dirname($pc);unlink($pc);}}return$K;}function
password_file($g){$pc=get_temp_dir()."/adminer.key";$K=@file_get_contents($pc);if($K||!$g)return$K;$o=@fopen($pc,"w");if($o){chmod($pc,0660);$K=rand_string();fwrite($o,$K);fclose($o);}return$K;}function
rand_string(){return
md5(uniqid(mt_rand(),true));}function
select_value($X,$_,$k,$gg){global$b,$ba;if(is_array($X)){$K="";foreach($X
as$bd=>$W)$K.="<tr>".($X!=array_values($X)?"<th>".h($bd):"")."<td>".select_value($W,$_,$k,$gg);return"<table cellspacing='0'>$K</table>";}if(!$_)$_=$b->selectLink($X,$k);if($_===null){if(is_mail($X))$_="mailto:$X";if($Se=is_url($X))$_=(($Se=="http"&&$ba)||preg_match('~WebKit|Firefox~i',$_SERVER["HTTP_USER_AGENT"])?$X:"https://www.adminer.org/redirect/?url=".urlencode($X));}$K=$b->editVal($X,$k);if($K!==null){if($K==="")$K="&nbsp;";elseif(!is_utf8($K))$K="\0";elseif($gg!=""&&is_shortable($k))$K=shorten_utf8($K,max(0,+$gg));else$K=h($K);}return$b->selectVal($K,$_,$k,$X);}function
is_mail($Rb){$va='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$Eb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$Be="$va+(\\.$va+)*@($Eb?\\.)+$Eb";return
is_string($Rb)&&preg_match("(^$Be(,\\s*$Be)*\$)i",$Rb);}function
is_url($Mf){$Eb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return(preg_match("~^(https?)://($Eb?\\.)+$Eb(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$Mf,$B)?strtolower($B[1]):"");}function
is_shortable($k){return
preg_match('~char|text|lob|geometry|point|linestring|polygon|string|bytea~',$k["type"]);}function
count_rows($Q,$Z,$Xc,$r){global$x;$I=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($Xc&&($x=="sql"||count($r)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$r).")$I":"SELECT COUNT(*)".($Xc?" FROM (SELECT 1$I$Ac) x":$I));}function
slow_query($I){global$b,$T;$i=$b->database();$ig=$b->queryTimeout();if(support("kill")&&is_object($f=connect())&&($i==""||$f->select_db($i))){$dd=$f->result(connection_id());echo'<script type="text/javascript">
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'token=',$T,'&kill=',$dd,'\');
}, ',1000*$ig,');
</script>
';}else$f=null;ob_flush();flush();$K=@get_key_vals($I,$f,$ig);if($f){echo"<script type='text/javascript'>clearTimeout(timeout);</script>\n";ob_flush();flush();}return
array_keys($K);}function
get_token(){$We=rand(1,1e6);return($We^$_SESSION["token"]).":$We";}function
verify_token(){list($T,$We)=explode(":",$_POST["token"]);return($We^$_SESSION["token"])==$T;}function
lzw_decompress($Da){$Ab=256;$Ea=8;$Ta=array();$hf=0;$if=0;for($s=0;$s<strlen($Da);$s++){$hf=($hf<<8)+ord($Da[$s]);$if+=8;if($if>=$Ea){$if-=$Ea;$Ta[]=$hf>>$if;$hf&=(1<<$if)-1;$Ab++;if($Ab>>$Ea)$Ea++;}}$_b=range("\0","\xFF");$K="";foreach($Ta
as$s=>$Sa){$Qb=$_b[$Sa];if(!isset($Qb))$Qb=$Wg.$Wg[0];$K.=$Qb;if($s)$_b[]=$Wg.$Qb[0];$Wg=$Qb;}return$K;}function
on_help($Za,$Af=0){return" onmouseover='helpMouseover(this, event, ".h($Za).", $Af);' onmouseout='helpMouseout(this, event);'";}function
edit_form($a,$l,$L,$Gg){global$b,$x,$T,$j;$Vf=$b->tableName(table_status1($a,true));page_header(($Gg?'Edit':'Insert'),$j,array("select"=>array($a,$Vf)),$Vf);if($L===false)echo"<p class='error'>".'No rows.'."\n";echo'<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$l)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table cellspacing='0' onkeydown='return editingKeydown(event);'>\n";foreach($l
as$E=>$k){echo"<tr><th>".$b->fieldName($k);$wb=$_GET["set"][bracket_escape($E)];if($wb===null){$wb=$k["default"];if($k["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$wb,$ef))$wb=$ef[1];}$Y=($L!==null?($L[$E]!=""&&$x=="sql"&&preg_match("~enum|set~",$k["type"])?(is_array($L[$E])?array_sum($L[$E]):+$L[$E]):$L[$E]):(!$Gg&&$k["auto_increment"]?"":(isset($_GET["select"])?false:$wb)));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$k);$p=($_POST["save"]?(string)$_POST["function"][$E]:($Gg&&$k["on_update"]=="CURRENT_TIMESTAMP"?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(preg_match("~time~",$k["type"])&&$Y=="CURRENT_TIMESTAMP"){$Y="";$p="now";}input($k,$Y,$p);echo"\n";}if(!support("table"))echo"<tr>"."<th><input name='field_keys[]' onkeyup='keyupChange.call(this);' onchange='fieldChange(this);' value=''>"."<td class='function'>".html_select("field_funs[]",$b->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($l){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Gg?'Save and continue edit'."' onclick='return !ajaxForm(this.form, \"".'Saving'.'...", this)':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n";}echo($Gg?"<input type='submit' name='delete' value='".'Delete'."'".confirm().">\n":($_POST||!$l?"":"<script type='text/javascript'>focus(document.getElementById('form').getElementsByTagName('td')[1].firstChild);</script>\n"));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$T,'">
</form>
';}global$b,$e,$Gb,$Nb,$Xb,$j,$zc,$Bc,$ba,$Qc,$x,$ca,$hd,$Zd,$Ce,$Nf,$Fc,$T,$ug,$zg,$Fg,$ga;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";$ba=$_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off");@ini_set("session.use_trans_sid",false);session_cache_limiter("");if(!defined("SID")){session_name("adminer_sid");$ue=array(0,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$ue[]=true;call_user_func_array('session_set_cookie_params',$ue);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$qc);if(get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",20);function
get_lang(){return'en';}function
lang($tg,$Qd=null){if(is_array($tg)){$Fe=($Qd==1?0:1);$tg=$tg[$Fe];}$tg=str_replace("%d","%s",$tg);$Qd=format_number($Qd);return
sprintf($tg,$Qd);}if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$errno,$error;function
__construct(){global$b;$Fe=array_search("SQL",$b->operators);if($Fe!==false)unset($b->operators[$Fe]);}function
dsn($Kb,$V,$G){try{parent::__construct($Kb,$V,$G);}catch(Exception$cc){auth_error(h($cc->getMessage()));}$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=@$this->getAttribute(4);}function
query($I,$_g=false){$J=parent::query($I);$this->error="";if(!$J){list(,$this->errno,$this->error)=$this->errorInfo();return
false;}$this->store_result($J);return$J;}function
multi_query($I){return$this->_result=$this->query($I);}function
store_result($J=null){if(!$J){$J=$this->_result;if(!$J)return
false;}if($J->columnCount()){$J->num_rows=$J->rowCount();return$J;}$this->affected_rows=$J->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($I,$k=0){$J=$this->query($I);if(!$J)return
false;$L=$J->fetch();return$L[$k];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$L=(object)$this->getColumnMeta($this->_offset++);$L->orgtable=$L->table;$L->orgname=$L->name;$L->charsetnr=(in_array("blob",(array)$L->flags)?63:0);return$L;}}}$Gb=array();class
Min_SQL{var$_conn;function
__construct($e){$this->_conn=$e;}function
select($Q,$N,$Z,$r,$he=array(),$z=1,$F=0,$Me=false){global$b,$x;$Xc=(count($r)<count($N));$I=$b->selectQueryBuild($N,$Z,$r,$he,$z,$F);if(!$I)$I="SELECT".limit(($_GET["page"]!="last"&&+$z&&$r&&$Xc&&$x=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$N)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($r&&$Xc?"\nGROUP BY ".implode(", ",$r):"").($he?"\nORDER BY ".implode(", ",$he):""),($z!=""?+$z:null),($F?$z*$F:0),"\n");$If=microtime(true);$K=$this->_conn->query($I);if($Me)echo$b->selectQuery($I,format_time($If));return$K;}function
delete($Q,$Ue,$z=0){$I="FROM ".table($Q);return
queries("DELETE".($z?limit1($I,$Ue):" $I$Ue"));}function
update($Q,$P,$Ue,$z=0,$wf="\n"){$Ng=array();foreach($P
as$y=>$X)$Ng[]="$y = $X";$I=table($Q)." SET$wf".implode(",$wf",$Ng);return
queries("UPDATE".($z?limit1($I,$Ue):" $I$Ue"));}function
insert($Q,$P){return
queries("INSERT INTO ".table($Q).($P?" (".implode(", ",array_keys($P)).")\nVALUES (".implode(", ",$P).")":" DEFAULT VALUES"));}function
insertUpdate($Q,$M,$Le){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}}$Gb=array("server"=>"MySQL")+$Gb;if(!defined("DRIVER")){$Ie=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
__construct(){parent::init();}function
connect($O="",$V="",$G="",$rb=null,$Ee=null,$Ef=null){mysqli_report(MYSQLI_REPORT_OFF);list($Ic,$Ee)=explode(":",$O,2);$K=@$this->real_connect(($O!=""?$Ic:ini_get("mysqli.default_host")),($O.$V!=""?$V:ini_get("mysqli.default_user")),($O.$V.$G!=""?$G:ini_get("mysqli.default_pw")),$rb,(is_numeric($Ee)?$Ee:ini_get("mysqli.default_port")),(!is_numeric($Ee)?$Ee:$Ef));return$K;}function
set_charset($La){if(parent::set_charset($La))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $La");}function
result($I,$k=0){$J=$this->query($I);if(!$J)return
false;$L=$J->fetch_array();return$L[$k];}function
quote($Mf){return"'".$this->escape_string($Mf)."'";}}}elseif(extension_loaded("mysql")&&!(ini_get("sql.safe_mode")&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($O,$V,$G){$this->_link=@mysql_connect(($O!=""?$O:ini_get("mysql.default_host")),("$O$V"!=""?$V:ini_get("mysql.default_user")),("$O$V$G"!=""?$G:ini_get("mysql.default_password")),true,131072);if($this->_link)$this->server_info=mysql_get_server_info($this->_link);else$this->error=mysql_error();return(bool)$this->_link;}function
set_charset($La){if(function_exists('mysql_set_charset')){if(mysql_set_charset($La,$this->_link))return
true;mysql_set_charset('utf8',$this->_link);}return$this->query("SET NAMES $La");}function
quote($Mf){return"'".mysql_real_escape_string($Mf,$this->_link)."'";}function
select_db($rb){return
mysql_select_db($rb,$this->_link);}function
query($I,$_g=false){$J=@($_g?mysql_unbuffered_query($I,$this->_link):mysql_query($I,$this->_link));$this->error="";if(!$J){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($J===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($J);}function
multi_query($I){return$this->_result=$this->query($I);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($I,$k=0){$J=$this->query($I);if(!$J||!$J->num_rows)return
false;return
mysql_result($J->_result,0,$k);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
__construct($J){$this->_result=$J;$this->num_rows=mysql_num_rows($J);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$K=mysql_fetch_field($this->_result,$this->_offset++);$K->orgtable=$K->table;$K->orgname=$K->name;$K->charsetnr=($K->blob?63:0);return$K;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($O,$V,$G){$this->dsn("mysql:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\\d)~',';port=\\1',$O)),$V,$G);return
true;}function
set_charset($La){$this->query("SET NAMES $La");}function
select_db($rb){return$this->query("USE ".idf_escape($rb));}function
query($I,$_g=false){$this->setAttribute(1000,!$_g);return
parent::query($I,$_g);}}}class
Min_Driver
extends
Min_SQL{function
insert($Q,$P){return($P?parent::insert($Q,$P):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,$M,$Le){$d=array_keys(reset($M));$Je="INSERT INTO ".table($Q)." (".implode(", ",$d).") VALUES\n";$Ng=array();foreach($d
as$y)$Ng[$y]="$y = VALUES($y)";$Qf="\nON DUPLICATE KEY UPDATE ".implode(", ",$Ng);$Ng=array();$od=0;foreach($M
as$P){$Y="(".implode(", ",$P).")";if($Ng&&(strlen($Je)+$od+strlen($Y)+strlen($Qf)>1e6)){if(!queries($Je.implode(",\n",$Ng).$Qf))return
false;$Ng=array();$od=0;}$Ng[]=$Y;$od+=strlen($Y)+2;}return
queries($Je.implode(",\n",$Ng).$Qf);}}function
idf_escape($Lc){return"`".str_replace("`","``",$Lc)."`";}function
table($Lc){return
idf_escape($Lc);}function
connect(){global$b,$zg,$Nf;$e=new
Min_DB;$mb=$b->credentials();if($e->connect($mb[0],$mb[1],$mb[2])){$e->set_charset(charset($e));$e->query("SET sql_quote_show_create = 1, autocommit = 1");if(version_compare($e->server_info,'5.7.8')>=0){$Nf['Strings'][]="json";$zg["json"]=4294967295;}return$e;}$K=$e->error;if(function_exists('iconv')&&!is_utf8($K)&&strlen($pf=iconv("windows-1250","utf-8",$K))>strlen($K))$K=$pf;return$K;}function
get_databases($sc){global$e;$K=get_session("dbs");if($K===null){$I=($e->server_info>=5?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA":"SHOW DATABASES");$K=($sc?slow_query($I):get_vals($I));restart_session();set_session("dbs",$K);stop_session();}return$K;}function
limit($I,$Z,$z,$Sd=0,$wf=" "){return" $I$Z".($z!==null?$wf."LIMIT $z".($Sd?" OFFSET $Sd":""):"");}function
limit1($I,$Z){return
limit($I,$Z,1);}function
db_collation($i,$Xa){global$e;$K=null;$g=$e->result("SHOW CREATE DATABASE ".idf_escape($i),1);if(preg_match('~ COLLATE ([^ ]+)~',$g,$B))$K=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$g,$B))$K=$Xa[$B[1]][-1];return$K;}function
engines(){$K=array();foreach(get_rows("SHOW ENGINES")as$L){if(preg_match("~YES|DEFAULT~",$L["Support"]))$K[]=$L["Engine"];}return$K;}function
logged_user(){global$e;return$e->result("SELECT USER()");}function
tables_list(){global$e;return
get_key_vals($e->server_info>=5?"SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME":"SHOW TABLES");}function
count_tables($h){$K=array();foreach($h
as$i)$K[$i]=count(get_vals("SHOW TABLES IN ".idf_escape($i)));return$K;}function
table_status($E="",$lc=false){global$e;$K=array();foreach(get_rows($lc&&$e->server_info>=5?"SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($E!=""?"AND TABLE_NAME = ".q($E):"ORDER BY Name"):"SHOW TABLE STATUS".($E!=""?" LIKE ".q(addcslashes($E,"%_\\")):""))as$L){if($L["Engine"]=="InnoDB")$L["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$L["Comment"]);if(!isset($L["Engine"]))$L["Comment"]="";if($E!="")return$L;$K[$L["Name"]]=$L;}return$K;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){global$e;return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"])||(preg_match('~NDB~i',$R["Engine"])&&version_compare($e->server_info,'5.6')>=0);}function
fields($Q){$K=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$L){preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~',$L["Type"],$B);$K[$L["Field"]]=array("field"=>$L["Field"],"full_type"=>$L["Type"],"type"=>$B[1],"length"=>$B[2],"unsigned"=>ltrim($B[3].$B[4]),"default"=>($L["Default"]!=""||preg_match("~char|set~",$B[1])?$L["Default"]:null),"null"=>($L["Null"]=="YES"),"auto_increment"=>($L["Extra"]=="auto_increment"),"on_update"=>(preg_match('~^on update (.+)~i',$L["Extra"],$B)?$B[1]:""),"collation"=>$L["Collation"],"privileges"=>array_flip(preg_split('~, *~',$L["Privileges"])),"comment"=>$L["Comment"],"primary"=>($L["Key"]=="PRI"),);}return$K;}function
indexes($Q,$f=null){$K=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$f)as$L){$E=$L["Key_name"];$K[$E]["type"]=($E=="PRIMARY"?"PRIMARY":($L["Index_type"]=="FULLTEXT"?"FULLTEXT":($L["Non_unique"]?($L["Index_type"]=="SPATIAL"?"SPATIAL":"INDEX"):"UNIQUE")));$K[$E]["columns"][]=$L["Column_name"];$K[$E]["lengths"][]=($L["Index_type"]=="SPATIAL"?null:$L["Sub_part"]);$K[$E]["descs"][]=null;}return$K;}function
foreign_keys($Q){global$e,$Zd;static$Be='`(?:[^`]|``)+`';$K=array();$kb=$e->result("SHOW CREATE TABLE ".table($Q),1);if($kb){preg_match_all("~CONSTRAINT ($Be) FOREIGN KEY ?\\(((?:$Be,? ?)+)\\) REFERENCES ($Be)(?:\\.($Be))? \\(((?:$Be,? ?)+)\\)(?: ON DELETE ($Zd))?(?: ON UPDATE ($Zd))?~",$kb,$vd,PREG_SET_ORDER);foreach($vd
as$B){preg_match_all("~$Be~",$B[2],$Ff);preg_match_all("~$Be~",$B[5],$bg);$K[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('idf_unescape',$Ff[0]),"target"=>array_map('idf_unescape',$bg[0]),"on_delete"=>($B[6]?$B[6]:"RESTRICT"),"on_update"=>($B[7]?$B[7]:"RESTRICT"),);}}return$K;}function
view($E){global$e;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\\s+AS\\s+~isU','',$e->result("SHOW CREATE VIEW ".table($E),1)));}function
collations(){$K=array();foreach(get_rows("SHOW COLLATION")as$L){if($L["Default"])$K[$L["Charset"]][-1]=$L["Collation"];else$K[$L["Charset"]][]=$L["Collation"];}ksort($K);foreach($K
as$y=>$X)asort($K[$y]);return$K;}function
information_schema($i){global$e;return($e->server_info>=5&&$i=="information_schema")||($e->server_info>=5.5&&$i=="performance_schema");}function
error(){global$e;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$e->error));}function
create_database($i,$Wa){return
queries("CREATE DATABASE ".idf_escape($i).($Wa?" COLLATE ".q($Wa):""));}function
drop_databases($h){$K=apply_queries("DROP DATABASE",$h,'idf_escape');restart_session();set_session("dbs",null);return$K;}function
rename_database($E,$Wa){$K=false;if(create_database($E,$Wa)){$ff=array();foreach(tables_list()as$Q=>$U)$ff[]=table($Q)." TO ".idf_escape($E).".".table($Q);$K=(!$ff||queries("RENAME TABLE ".implode(", ",$ff)));if($K)queries("DROP DATABASE ".idf_escape(DB));restart_session();set_session("dbs",null);}return$K;}function
auto_increment(){$za=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$u){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$u["columns"],true)){$za="";break;}if($u["type"]=="PRIMARY")$za=" UNIQUE";}}return" AUTO_INCREMENT$za";}function
alter_table($Q,$E,$l,$tc,$bb,$Vb,$Wa,$ya,$ye){$sa=array();foreach($l
as$k)$sa[]=($k[1]?($Q!=""?($k[0]!=""?"CHANGE ".idf_escape($k[0]):"ADD"):" ")." ".implode($k[1]).($Q!=""?$k[2]:""):"DROP ".idf_escape($k[0]));$sa=array_merge($sa,$tc);$Jf=($bb!==null?" COMMENT=".q($bb):"").($Vb?" ENGINE=".q($Vb):"").($Wa?" COLLATE ".q($Wa):"").($ya!=""?" AUTO_INCREMENT=$ya":"");if($Q=="")return
queries("CREATE TABLE ".table($E)." (\n".implode(",\n",$sa)."\n)$Jf$ye");if($Q!=$E)$sa[]="RENAME TO ".table($E);if($Jf)$sa[]=ltrim($Jf);return($sa||$ye?queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$sa).$ye):true);}function
alter_indexes($Q,$sa){foreach($sa
as$y=>$X)$sa[$y]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$sa));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Rg){return
queries("DROP VIEW ".implode(", ",array_map('table',$Rg)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Rg,$bg){$ff=array();foreach(array_merge($S,$Rg)as$Q)$ff[]=table($Q)." TO ".idf_escape($bg).".".table($Q);return
queries("RENAME TABLE ".implode(", ",$ff));}function
copy_tables($S,$Rg,$bg){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$E=($bg==DB?table("copy_$Q"):idf_escape($bg).".".table($Q));if(!queries("\nDROP TABLE IF EXISTS $E")||!queries("CREATE TABLE $E LIKE ".table($Q))||!queries("INSERT INTO $E SELECT * FROM ".table($Q)))return
false;}foreach($Rg
as$Q){$E=($bg==DB?table("copy_$Q"):idf_escape($bg).".".table($Q));$Qg=view($Q);if(!queries("DROP VIEW IF EXISTS $E")||!queries("CREATE VIEW $E AS $Qg[select]"))return
false;}return
true;}function
trigger($E){if($E=="")return
array();$M=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($E));return
reset($M);}function
triggers($Q){$K=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$L)$K[$L["Trigger"]]=array($L["Timing"],$L["Event"]);return$K;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($E,$U){global$e,$Xb,$Qc,$zg;$qa=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$yg="((".implode("|",array_merge(array_keys($zg),$qa)).")\\b(?:\\s*\\(((?:[^'\")]|$Xb)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";$Be="\\s*(".($U=="FUNCTION"?"":$Qc).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$yg";$g=$e->result("SHOW CREATE $U ".idf_escape($E),2);preg_match("~\\(((?:$Be\\s*,?)*)\\)\\s*".($U=="FUNCTION"?"RETURNS\\s+$yg\\s+":"")."(.*)~is",$g,$B);$l=array();preg_match_all("~$Be\\s*,?~is",$B[1],$vd,PREG_SET_ORDER);foreach($vd
as$te){$E=str_replace("``","`",$te[2]).$te[3];$l[]=array("field"=>$E,"type"=>strtolower($te[5]),"length"=>preg_replace_callback("~$Xb~s",'normalize_enum',$te[6]),"unsigned"=>strtolower(preg_replace('~\\s+~',' ',trim("$te[8] $te[7]"))),"null"=>1,"full_type"=>$te[4],"inout"=>strtoupper($te[1]),"collation"=>strtolower($te[9]),);}if($U!="FUNCTION")return
array("fields"=>$l,"definition"=>$B[11]);return
array("fields"=>$l,"returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
last_id(){global$e;return$e->result("SELECT LAST_INSERT_ID()");}function
explain($e,$I){return$e->query("EXPLAIN ".($e->server_info>=5.1?"PARTITIONS ":"").$I);}function
found_rows($R,$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($rf){return
true;}function
create_sql($Q,$ya){global$e;$K=$e->result("SHOW CREATE TABLE ".table($Q),1);if(!$ya)$K=preg_replace('~ AUTO_INCREMENT=\\d+~','',$K);return$K;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($rb){return"USE ".idf_escape($rb);}function
trigger_sql($Q,$Of){$K="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$L)$K.="\n".($Of=='CREATE+ALTER'?"DROP TRIGGER IF EXISTS ".idf_escape($L["Trigger"]).";;\n":"")."CREATE TRIGGER ".idf_escape($L["Trigger"])." $L[Timing] $L[Event] ON ".table($L["Table"])." FOR EACH ROW\n$L[Statement];;\n";return$K;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
replication_status($U){return
get_rows("SHOW $U STATUS");}function
convert_field($k){if(preg_match("~binary~",$k["type"]))return"HEX(".idf_escape($k["field"]).")";if($k["type"]=="bit")return"BIN(".idf_escape($k["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$k["type"]))return"AsWKT(".idf_escape($k["field"]).")";}function
unconvert_field($k,$K){if(preg_match("~binary~",$k["type"]))$K="UNHEX($K)";if($k["type"]=="bit")$K="CONV($K, 2, 10) + 0";if(preg_match("~geometry|point|linestring|polygon~",$k["type"]))$K="GeomFromText($K)";return$K;}function
support($mc){global$e;return!preg_match("~scheme|sequence|type|view_trigger|materializedview".($e->server_info<5.1?"|event|partitioning".($e->server_info<5?"|routine|trigger|view":""):"")."~",$mc);}function
kill_process($X){return
queries("KILL ".number($X));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){global$e;return$e->result("SELECT @@max_connections");}$x="sql";$zg=array();$Nf=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$y=>$X){$zg+=$X;$Nf[$y]=array_keys($X);}$Fg=array("unsigned","zerofill","unsigned zerofill");$de=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$zc=array("char_length","date","from_unixtime","lower","round","sec_to_time","time_to_sec","upper");$Bc=array("avg","count","count distinct","group_concat","max","min","sum");$Nb=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array("(^|[^o])int|float|double|decimal"=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ga="4.3.1";class
Adminer{var$operators;function
name(){return"<a href='https://www.adminer.org/' target='_blank' id='h1'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
permanentLogin($g=false){return
password_file($g);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
database(){return
DB;}function
databases($sc=true){return
get_databases($sc);}function
schemas(){return
schemas();}function
queryTimeout(){return
5;}function
headers(){return
true;}function
head(){return
true;}function
loginForm(){global$Gb;?>
<table cellspacing="0">
<tr><th>System<td><input type='hidden' name='auth[driver]' value='server'>MySQL
<tr><th>Server<td><input name="auth[server]" value="<?php echo
h(SERVER),'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">
<tr><th>Username<td><input name="auth[username]" id="username" value="',h($_GET["username"]),'" autocapitalize="off">
<tr><th>Password<td><input type="password" name="auth[password]">
<tr><th>Database<td><input name="auth[db]" value="',h($_GET["db"]),'" autocapitalize="off">
</table>
<script type="text/javascript">
focus(document.getElementById(\'username\'));
</script>
',"<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
login($sd,$G){global$x;if($x=="sqlite")return
sprintf('<a href="https://www.adminer.org/en/extension/" target="_blank">Implement</a> %s method to use SQLite.','<code>login()</code>');return
true;}function
tableName($Uf){return
h($Uf["Name"]);}function
fieldName($k,$he=0){return'<span title="'.h($k["full_type"]).'">'.h($k["field"]).'</span>';}function
selectLinks($Uf,$P=""){echo'<p class="links">';$rd=array("select"=>'Select data');if(support("table")||support("indexes"))$rd["table"]='Show structure';if(support("table")){if(is_view($Uf))$rd["view"]='Alter view';else$rd["create"]='Alter table';}if($P!==null)$rd["edit"]='New item';foreach($rd
as$y=>$X)echo" <a href='".h(ME)."$y=".urlencode($Uf["Name"]).($y=="edit"?$P:"")."'".bold(isset($_GET[$y])).">$X</a>";echo"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Tf){return
array();}function
backwardKeysPrint($Aa,$L){}function
selectQuery($I,$hg){global$x;return"<p><code class='jush-$x'>".h(str_replace("\n"," ",$I))."</code> <span class='time'>($hg)</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($I)."'>".'Edit'."</a>":"")."</p>";}function
sqlCommandQuery($I){return
shorten_utf8(trim($I),1000);}function
rowDescription($Q){return"";}function
rowDescriptions($M,$uc){return$M;}function
selectLink($X,$k){}function
selectVal($X,$_,$k,$pe){$K=($X===null?"<i>NULL</i>":(preg_match("~char|binary~",$k["type"])&&!preg_match("~var~",$k["type"])?"<code>$X</code>":$X));if(preg_match('~blob|bytea|raw|file~',$k["type"])&&!is_utf8($X))$K="<i>".lang(array('%d byte','%d bytes'),strlen($pe))."</i>";if(preg_match('~json~',$k["type"]))$K="<code class='jush-js'>$K</code>";return($_?"<a href='".h($_)."'".(is_url($_)?" rel='noreferrer'":"").">$K</a>":$K);}function
editVal($X,$k){return$X;}function
tableStructurePrint($l){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";foreach($l
as$k){echo"<tr".odd()."><th>".h($k["field"]),"<td><span title='".h($k["collation"])."'>".h($k["full_type"])."</span>",($k["null"]?" <i>NULL</i>":""),($k["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($k["default"])?" <span title='".'Default value'."'>[<b>".h($k["default"])."</b>]</span>":""),(support("comment")?"<td>".nbsp($k["comment"]):""),"\n";}echo"</table>\n";}function
tableIndexesPrint($v){echo"<table cellspacing='0'>\n";foreach($v
as$E=>$u){ksort($u["columns"]);$Me=array();foreach($u["columns"]as$y=>$X)$Me[]="<i>".h($X)."</i>".($u["lengths"][$y]?"(".$u["lengths"][$y].")":"").($u["descs"][$y]?" DESC":"");echo"<tr title='".h($E)."'><th>$u[type]<td>".implode(", ",$Me)."\n";}echo"</table>\n";}function
selectColumnsPrint($N,$d){global$zc,$Bc;print_fieldset("select",'Select',$N);$s=0;$N[""]=array();foreach($N
as$y=>$X){$X=$_GET["columns"][$y];$c=select_input(" name='columns[$s][col]' onchange='".($y!==""?"selectFieldChange(this.form)":"selectAddRow(this)").";'",$d,$X["col"]);echo"<div>".($zc||$Bc?"<select name='columns[$s][fun]' onchange='helpClose();".($y!==""?"":" this.nextSibling.nextSibling.onchange();")."'".on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'",1).">".optionlist(array(-1=>"")+array_filter(array('Functions'=>$zc,'Aggregation'=>$Bc)),$X["fun"])."</select>"."($c)":$c)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint($Z,$d,$v){print_fieldset("search",'Search',$Z);foreach($v
as$s=>$u){if($u["type"]=="FULLTEXT"){echo"(<i>".implode("</i>, <i>",array_map('h',$u["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$s]' value='".h($_GET["fulltext"][$s])."' onchange='selectFieldChange(this.form);'>",checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"),"<br>\n";}}$_GET["where"]=(array)$_GET["where"];reset($_GET["where"]);$Ka="this.nextSibling.onchange();";for($s=0;$s<=count($_GET["where"]);$s++){list(,$X)=each($_GET["where"]);if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div>".select_input(" name='where[$s][col]' onchange='$Ka'",$d,$X["col"],"(".'anywhere'.")"),html_select("where[$s][op]",$this->operators,$X["op"],$Ka),"<input type='search' name='where[$s][val]' value='".h($X["val"])."' onchange='".($X?"selectFieldChange(this.form)":"selectAddRow(this)").";' onkeydown='selectSearchKeydown(this, event);' onsearch='selectSearchSearch(this);'></div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($he,$d,$v){print_fieldset("sort",'Sort',$he);$s=0;foreach((array)$_GET["order"]as$y=>$X){if($X!=""){echo"<div>".select_input(" name='order[$s]' onchange='selectFieldChange(this.form);'",$d,$X),checkbox("desc[$s]",1,isset($_GET["desc"][$y]),'descending')."</div>\n";$s++;}}echo"<div>".select_input(" name='order[$s]' onchange='selectAddRow(this);'",$d),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>";echo"<input type='number' name='limit' class='size' value='".h($z)."' onchange='selectFieldChange(this.form);'>","</div></fieldset>\n";}function
selectLengthPrint($gg){if($gg!==null){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($gg)."'>","</div></fieldset>\n";}}function
selectActionPrint($v){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script type='text/javascript'>\n","var indexColumns = ";$d=array();foreach($v
as$u){$ob=reset($u["columns"]);if($u["type"]!="FULLTEXT"&&$ob)$d[$ob]=1;}$d[""]=1;foreach($d
as$y=>$X)json_row($y);echo";\n","selectFieldChange(document.getElementById('form'));\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint($Sb,$d){}function
selectColumnsProcess($d,$v){global$zc,$Bc;$N=array();$r=array();foreach((array)$_GET["columns"]as$y=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],$zc)||in_array($X["fun"],$Bc)))){$N[$y]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$Bc))$r[]=$N[$y];}}return
array($N,$r);}function
selectSearchProcess($l,$v){global$e,$x;$K=array();foreach($v
as$s=>$u){if($u["type"]=="FULLTEXT"&&$_GET["fulltext"][$s]!="")$K[]="MATCH (".implode(", ",array_map('idf_escape',$u["columns"])).") AGAINST (".q($_GET["fulltext"][$s]).(isset($_GET["boolean"][$s])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$db=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Nc=process_length($X["val"]);$db.=" ".($Nc!=""?$Nc:"(NULL)");}elseif($X["op"]=="SQL")$db=" $X[val]";elseif($X["op"]=="LIKE %%")$db=" LIKE ".$this->processInput($l[$X["col"]],"%$X[val]%");elseif($X["op"]=="ILIKE %%")$db=" ILIKE ".$this->processInput($l[$X["col"]],"%$X[val]%");elseif(!preg_match('~NULL$~',$X["op"]))$db.=" ".$this->processInput($l[$X["col"]],$X["val"]);if($X["col"]!="")$K[]=idf_escape($X["col"]).$db;else{$Ya=array();foreach($l
as$E=>$k){$Zc=preg_match('~char|text|enum|set~',$k["type"]);if((is_numeric($X["val"])||!preg_match('~(^|[^o])int|float|double|decimal|bit~',$k["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||$Zc)){$E=idf_escape($E);$Ya[]=($x=="sql"&&$Zc&&!preg_match("~^utf8_~",$k["collation"])?"CONVERT($E USING ".charset($e).")":$E);}}$K[]=($Ya?"(".implode("$db OR ",$Ya)."$db)":"0");}}}return$K;}function
selectOrderProcess($l,$v){$K=array();foreach((array)$_GET["order"]as$y=>$X){if($X!="")$K[]=(preg_match('~^((COUNT\\(DISTINCT |[A-Z0-9_]+\\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\\)|COUNT\\(\\*\\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$y])?" DESC":"");}return$K;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$uc){return
false;}function
selectQueryBuild($N,$Z,$r,$he,$z,$F){return"";}function
messageQuery($I,$hg){global$x;restart_session();$Gc=&get_session("queries");$t="sql-".count($Gc[$_GET["db"]]);if(strlen($I)>1e6)$I=preg_replace('~[\x80-\xFF]+$~','',substr($I,0,1e6))."\n...";$Gc[$_GET["db"]][]=array($I,time(),$hg);return" <span class='time'>".@date("H:i:s")."</span> <a href='#$t' onclick=\"return !toggle('$t');\">".'SQL command'."</a>"."<div id='$t' class='hidden'><pre><code class='jush-$x'>".shorten_utf8($I,1000).'</code></pre>'.($hg?" <span class='time'>($hg)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($Gc[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
editFunctions($k){global$Nb;$K=($k["null"]?"NULL/":"");foreach($Nb
as$y=>$zc){if(!$y||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($zc
as$Be=>$X){if(!$Be||preg_match("~$Be~",$k["type"]))$K.="/$X";}if($y&&!preg_match('~set|blob|bytea|raw|file~',$k["type"]))$K.="/SQL";}}if($k["auto_increment"]&&!isset($_GET["select"])&&!where($_GET))$K='Auto Increment';return
explode("/",$K);}function
editInput($Q,$k,$wa,$Y){if($k["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$wa value='-1' checked><i>".'original'."</i></label> ":"").($k["null"]?"<label><input type='radio'$wa value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$wa,$k,$Y,0);return"";}function
processInput($k,$Y,$p=""){if($p=="SQL")return$Y;$E=$k["field"];$K=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$p))$K="$p()";elseif(preg_match('~^current_(date|timestamp)$~',$p))$K=$p;elseif(preg_match('~^([+-]|\\|\\|)$~',$p))$K=idf_escape($E)." $p $K";elseif(preg_match('~^[+-] interval$~',$p))$K=idf_escape($E)." $p ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+$~i",$Y)?$Y:$K);elseif(preg_match('~^(addtime|subtime|concat)$~',$p))$K="$p(".idf_escape($E).", $K)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$p))$K="$p($K)";return
unconvert_field($k,$K);}function
dumpOutput(){$K=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$K['gz']='gzip';return$K;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($i){}function
dumpTable($Q,$Of,$ad=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Of)dump_csv(array_keys(fields($Q)));}else{if($ad==2){$l=array();foreach(fields($Q)as$E=>$k)$l[]=idf_escape($E)." $k[full_type]";$g="CREATE TABLE ".table($Q)." (".implode(", ",$l).")";}else$g=create_sql($Q,$_POST["auto_increment"]);set_utf8mb4($g);if($Of&&$g){if($Of=="DROP+CREATE"||$ad==1)echo"DROP ".($ad==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($ad==1)$g=remove_definer($g);echo"$g;\n\n";}}}function
dumpData($Q,$Of,$I){global$e,$x;$xd=($x=="sqlite"?0:1048576);if($Of){if($_POST["format"]=="sql"){if($Of=="TRUNCATE+INSERT")echo
truncate_sql($Q).";\n";$l=fields($Q);}$J=$e->query($I,1);if($J){$Sc="";$Ia="";$cd=array();$Qf="";$nc=($Q!=''?'fetch_assoc':'fetch_row');while($L=$J->$nc()){if(!$cd){$Ng=array();foreach($L
as$X){$k=$J->fetch_field();$cd[]=$k->name;$y=idf_escape($k->name);$Ng[]="$y = VALUES($y)";}$Qf=($Of=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Ng):"").";\n";}if($_POST["format"]!="sql"){if($Of=="table"){dump_csv($cd);$Of="INSERT";}dump_csv($L);}else{if(!$Sc)$Sc="INSERT INTO ".table($Q)." (".implode(", ",array_map('idf_escape',$cd)).") VALUES";foreach($L
as$y=>$X){$k=$l[$y];$L[$y]=($X!==null?unconvert_field($k,preg_match('~(^|[^o])int|float|double|decimal~',$k["type"])&&$X!=''?$X:q($X)):"NULL");}$pf=($xd?"\n":" ")."(".implode(",\t",$L).")";if(!$Ia)$Ia=$Sc.$pf;elseif(strlen($Ia)+4+strlen($pf)+strlen($Qf)<$xd)$Ia.=",$pf";else{echo$Ia.$Qf;$Ia=$Sc.$pf;}}}if($Ia)echo$Ia.$Qf;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$e->error)."\n";}}function
dumpFilename($Kc){return
friendly_url($Kc!=""?$Kc:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($Kc,$Id=false){$re=$_POST["output"];$ic=(preg_match('~sql~',$_POST["format"])?"sql":($Id?"tar":"csv"));header("Content-Type: ".($re=="gz"?"application/x-gzip":($ic=="tar"?"application/x-tar":($ic=="sql"||$re!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($re=="gz")ob_start('ob_gzencode',1e6);return$ic;}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");return
true;}function
navigation($Hd){global$ga,$x,$Gb,$e;echo'<h1>
',$this->name(),' <span class="version">',$ga,'</span>
<a href="https://www.adminer.org/#download" target="_blank" id="version">',(version_compare($ga,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($Hd=="auth"){$rc=true;foreach((array)$_SESSION["pwds"]as$Pg=>$zf){foreach($zf
as$O=>$Lg){foreach($Lg
as$V=>$G){if($G!==null){if($rc){echo"<p id='logins' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";$rc=false;}$ub=$_SESSION["db"][$Pg][$O][$V];foreach(($ub?array_keys($ub):array(""))as$i)echo"<a href='".h(auth_url($Pg,$O,$V,$i))."'>($Gb[$Pg]) ".h($V.($O!=""?"@$O":"").($i!=""?" - $i":""))."</a><br>\n";}}}}}else{if($_GET["ns"]!==""&&!$Hd&&DB!=""){$e->select_db(DB);$S=table_status('',true);}echo'<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=jush.js&amp;version=4.3.1&amp;driver=mysql",'"></script>
';if(support("sql")){echo'<script type="text/javascript">
';if($S){$rd=array();foreach($S
as$Q=>$U)$rd[]=preg_quote($Q,'/');echo"var jushLinks = { $x: [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$rd).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$x;\n";}echo'bodyLoad(\'',(is_object($e)?substr($e->server_info,0,3):""),'\');
</script>
';}$this->databasesPrint($Hd);if(DB==""||!$Hd){echo"<p class='links'>".(support("sql")?"<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>\n<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>\n":"")."";if(support("dump"))echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>\n";}if($_GET["ns"]!==""&&!$Hd&&DB!=""){echo'<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>\n";if(!$S)echo"<p class='message'>".'No tables.'."\n";else$this->tablesPrint($S);}}}function
databasesPrint($Hd){global$b,$e;$h=$this->databases();echo'<form action="">
<p id="dbs">
';hidden_fields_get();$sb=" onmousedown='dbMouseDown(event, this);' onchange='dbChange(this);'";echo"<span title='".'database'."'>DB</span>: ".($h?"<select name='db'$sb>".optionlist(array(""=>"")+$h,DB)."</select>":'<input name="db" value="'.h(DB).'" autocapitalize="off">'),"<input type='submit' value='".'Use'."'".($h?" class='hidden'":"").">\n";if($Hd!="db"&&DB!=""&&$e->select_db(DB)){}echo(isset($_GET["sql"])?'<input type="hidden" name="sql" value="">':(isset($_GET["schema"])?'<input type="hidden" name="schema" value="">':(isset($_GET["dump"])?'<input type="hidden" name="dump" value="">':(isset($_GET["privileges"])?'<input type="hidden" name="privileges" value="">':"")))),"</p></form>\n";}function
tablesPrint($S){echo"<ul id='tables' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";foreach($S
as$Q=>$Jf){echo'<li><a href="'.h(ME).'select='.urlencode($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q,"select").">".'select'."</a> ";$E=$this->tableName($Jf);echo(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"])),(is_view($Jf)?"view":"structure"))." title='".'Show structure'."'>$E</a>":"<span>$E</span>")."\n";}echo"</ul>\n";}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);if($b->operators===null)$b->operators=$de;function
page_header($kg,$j="",$Ha=array(),$lg=""){global$ca,$ga,$b,$Gb,$x;page_headers();if(is_ajax()&&$j){page_messages($j);exit;}$mg=$kg.($lg!=""?": $lg":"");$ng=strip_tags($mg.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="robots" content="noindex">
<meta name="referrer" content="origin-when-crossorigin">
<title>',$ng,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME))."?file=default.css&amp;version=4.3.1&amp;driver=mysql",'">
<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=functions.js&amp;version=4.3.1&amp;driver=mysql",'"></script>
';if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=4.3.1&amp;driver=mysql",'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=4.3.1&amp;driver=mysql",'">
';if(file_exists("adminer.css")){echo'<link rel="stylesheet" type="text/css" href="adminer.css">
';}}echo'
<body class="ltr nojs" onkeydown="bodyKeydown(event);" onclick="bodyClick(event);"',(isset($_COOKIE["adminer_version"])?"":" onload=\"verifyVersion('$ga');\"");?>>
<script type="text/javascript">
document.body.className = document.body.className.replace(/ nojs/, ' js');
var offlineMessage = '<?php echo
js_escape('You are offline.'),'\';
</script>

<div id="help" class="jush-',$x,' jsonly hidden" onmouseover="helpOpen = 1;" onmouseout="helpMouseout(this, event);"></div>

<div id="content">
';if($Ha!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?$_:".").'">'.$Gb[DRIVER].'</a> &raquo; ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$O=(SERVER!=""?h(SERVER):'Server');if($Ha===false)echo"$O\n";else{echo"<a href='".($_?h($_):".")."' accesskey='1' title='Alt+Shift+1'>$O</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Ha)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Ha)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Ha
as$y=>$X){$yb=(is_array($X)?$X[1]:h($X));if($yb!="")echo"<a href='".h(ME."$y=").urlencode(is_array($X)?$X[0]:$X)."'>$yb</a> &raquo; ";}}echo"$kg\n";}}echo"<h2>$mg</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($j);$h=&get_session("dbs");if(DB!=""&&$h&&!in_array(DB,$h,true))$h=null;stop_session();define("PAGE_HEADER",1);}function
page_headers(){global$b;header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");if($b->headers()){header("X-Frame-Options: deny");header("X-XSS-Protection: 0");}}function
page_messages($j){$Hg=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Fd=$_SESSION["messages"][$Hg];if($Fd){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Fd)."</div>\n";unset($_SESSION["messages"][$Hg]);}if($j)echo"<div class='error'>$j</div>\n";}function
page_footer($Hd=""){global$b,$T;echo'</div>

';if($Hd!="auth"){echo'<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="Logout" id="logout">
<input type="hidden" name="token" value="',$T,'">
</p>
</form>
';}echo'<div id="menu">
';$b->navigation($Hd);echo'</div>
<script type="text/javascript">setupSubmitHighlight(document);</script>
';}function
int32($D){while($D>=2147483648)$D-=4294967296;while($D<=-2147483649)$D+=4294967296;return(int)$D;}function
long2str($W,$Tg){$pf='';foreach($W
as$X)$pf.=pack('V',$X);if($Tg)return
substr($pf,0,end($W));return$pf;}function
str2long($pf,$Tg){$W=array_values(unpack('V*',str_pad($pf,4*ceil(strlen($pf)/4),"\0")));if($Tg)$W[]=strlen($pf);return$W;}function
xxtea_mx($Yg,$Xg,$Rf,$bd){return
int32((($Yg>>5&0x7FFFFFF)^$Xg<<2)+(($Xg>>3&0x1FFFFFFF)^$Yg<<4))^int32(($Rf^$Xg)+($bd^$Yg));}function
encrypt_string($Lf,$y){if($Lf=="")return"";$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($Lf,true);$D=count($W)-1;$Yg=$W[$D];$Xg=$W[0];$H=floor(6+52/($D+1));$Rf=0;while($H-->0){$Rf=int32($Rf+0x9E3779B9);$Mb=$Rf>>2&3;for($se=0;$se<$D;$se++){$Xg=$W[$se+1];$Jd=xxtea_mx($Yg,$Xg,$Rf,$y[$se&3^$Mb]);$Yg=int32($W[$se]+$Jd);$W[$se]=$Yg;}$Xg=$W[0];$Jd=xxtea_mx($Yg,$Xg,$Rf,$y[$se&3^$Mb]);$Yg=int32($W[$D]+$Jd);$W[$D]=$Yg;}return
long2str($W,false);}function
decrypt_string($Lf,$y){if($Lf=="")return"";if(!$y)return
false;$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($Lf,false);$D=count($W)-1;$Yg=$W[$D];$Xg=$W[0];$H=floor(6+52/($D+1));$Rf=int32($H*0x9E3779B9);while($Rf){$Mb=$Rf>>2&3;for($se=$D;$se>0;$se--){$Yg=$W[$se-1];$Jd=xxtea_mx($Yg,$Xg,$Rf,$y[$se&3^$Mb]);$Xg=int32($W[$se]-$Jd);$W[$se]=$Xg;}$Yg=$W[$D];$Jd=xxtea_mx($Yg,$Xg,$Rf,$y[$se&3^$Mb]);$Xg=int32($W[0]-$Jd);$W[0]=$Xg;$Rf=int32($Rf-0x9E3779B9);}return
long2str($W,true);}$e='';$Fc=$_SESSION["token"];if(!$Fc)$_SESSION["token"]=rand(1,1e6);$T=get_token();$Ce=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($y)=explode(":",$X);$Ce[$y]=$X;}}function
add_invalid_login(){global$b;$pc=get_temp_dir()."/adminer.invalid";$o=@fopen($pc,"r+");if(!$o){$o=@fopen($pc,"w");if(!$o)return;}flock($o,LOCK_EX);$Vc=unserialize(stream_get_contents($o));$hg=time();if($Vc){foreach($Vc
as$Wc=>$X){if($X[0]<$hg)unset($Vc[$Wc]);}}$Uc=&$Vc[$b->bruteForceKey()];if(!$Uc)$Uc=array($hg+30*60,0);$Uc[1]++;$xf=serialize($Vc);rewind($o);fwrite($o,$xf);ftruncate($o,strlen($xf));flock($o,LOCK_UN);fclose($o);}$xa=$_POST["auth"];if($xa){$Vc=unserialize(@file_get_contents(get_temp_dir()."/adminer.invalid"));$Uc=$Vc[$b->bruteForceKey()];$Od=($Uc[1]>30?$Uc[0]-time():0);if($Od>0)auth_error(lang(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($Od/60)));session_regenerate_id();$Pg=$xa["driver"];$O=$xa["server"];$V=$xa["username"];$G=(string)$xa["password"];$i=$xa["db"];set_password($Pg,$O,$V,$G);$_SESSION["db"][$Pg][$O][$V][$i]=true;if($xa["permanent"]){$y=base64_encode($Pg)."-".base64_encode($O)."-".base64_encode($V)."-".base64_encode($i);$Ne=$b->permanentLogin(true);$Ce[$y]="$y:".base64_encode($Ne?encrypt_string($G,$Ne):"");cookie("adminer_permanent",implode(" ",$Ce));}if(count($_POST)==1||DRIVER!=$Pg||SERVER!=$O||$_GET["username"]!==$V||DB!=$i)redirect(auth_url($Pg,$O,$V,$i));}elseif($_POST["logout"]){if($Fc&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}else{foreach(array("pwds","db","dbs","queries")as$y)set_session($y,null);unset_permanent();redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.');}}elseif($Ce&&!$_SESSION["pwds"]){session_regenerate_id();$Ne=$b->permanentLogin();foreach($Ce
as$y=>$X){list(,$Qa)=explode(":",$X);list($Pg,$O,$V,$i)=array_map('base64_decode',explode("-",$y));set_password($Pg,$O,$V,decrypt_string(base64_decode($Qa),$Ne));$_SESSION["db"][$Pg][$O][$V][$i]=true;}}function
unset_permanent(){global$Ce;foreach($Ce
as$y=>$X){list($Pg,$O,$V,$i)=array_map('base64_decode',explode("-",$y));if($Pg==DRIVER&&$O==SERVER&&$V==$_GET["username"]&&$i==DB)unset($Ce[$y]);}cookie("adminer_permanent",implode(" ",$Ce));}function
auth_error($j){global$b,$Fc;$_f=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$_f]||$_GET[$_f])&&!$Fc)$j='Session expired, please login again.';else{add_invalid_login();$G=get_password();if($G!==null){if($G===false)$j.='<br>'.sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/" target="_blank">Implement</a> %s method to make it permanent.','<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent();}}if(!$_COOKIE[$_f]&&$_GET[$_f]&&ini_bool("session.use_only_cookies"))$j='Session support must be enabled.';$ue=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?$_COOKIE["adminer_key"]:rand_string()),$ue["lifetime"]);page_header('Login',$j,null);echo"<form action='' method='post'>\n";$b->loginForm();echo"<div>";hidden_fields($_POST,array("auth"));echo"</div>\n","</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])){if(!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",$Ie)),false);page_footer("auth");exit;}$e=connect();}$Fb=new
Min_Driver($e);if(!is_object($e)||($sd=$b->login($_GET["username"],get_password()))!==true)auth_error((is_string($e)?h($e):(is_string($sd)?$sd:'Invalid credentials.')));if($xa&&$_POST["token"])$_POST["token"]=$T;$j='';if($_POST){if(!verify_token()){$Pc="max_input_vars";$Ad=ini_get($Pc);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$y){$X=ini_get($y);if($X&&(!$Ad||$X<$Ad)){$Pc=$y;$Ad=$X;}}}$j=(!$_POST["token"]&&$Ad?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$Pc'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$j=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$j.=' '.'You can upload a big SQL file via FTP and import it from server.';}if(!ini_bool("session.use_cookies")||@ini_set("session.use_cookies",false)!==false)session_write_close();function
select($J,$f=null,$ke=array(),$z=0){global$x;$rd=array();$v=array();$d=array();$Fa=array();$zg=array();$K=array();odd('');for($s=0;(!$z||$s<$z)&&($L=$J->fetch_row());$s++){if(!$s){echo"<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($w=0;$w<count($L);$w++){$k=$J->fetch_field();$E=$k->name;$je=$k->orgtable;$ie=$k->orgname;$K[$k->table]=$je;if($ke&&$x=="sql")$rd[$w]=($E=="table"?"table=":($E=="possible_keys"?"indexes=":null));elseif($je!=""){if(!isset($v[$je])){$v[$je]=array();foreach(indexes($je,$f)as$u){if($u["type"]=="PRIMARY"){$v[$je]=array_flip($u["columns"]);break;}}$d[$je]=$v[$je];}if(isset($d[$je][$ie])){unset($d[$je][$ie]);$v[$je][$ie]=$w;$rd[$w]=$je;}}if($k->charsetnr==63)$Fa[$w]=true;$zg[$w]=$k->type;echo"<th".($je!=""||$k->name!=$ie?" title='".h(($je!=""?"$je.":"").$ie)."'":"").">".h($E).($ke?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($E))):"");}echo"</thead>\n";}echo"<tr".odd().">";foreach($L
as$y=>$X){if($X===null)$X="<i>NULL</i>";elseif($Fa[$y]&&!is_utf8($X))$X="<i>".lang(array('%d byte','%d bytes'),strlen($X))."</i>";elseif(!strlen($X))$X="&nbsp;";else{$X=h($X);if($zg[$y]==254)$X="<code>$X</code>";}if(isset($rd[$y])&&!$d[$rd[$y]]){if($ke&&$x=="sql"){$Q=$L[array_search("table=",$rd)];$_=$rd[$y].urlencode($ke[$Q]!=""?$ke[$Q]:$Q);}else{$_="edit=".urlencode($rd[$y]);foreach($v[$rd[$y]]as$Ua=>$w)$_.="&where".urlencode("[".bracket_escape($Ua)."]")."=".urlencode($L[$w]);}$X="<a href='".h(ME.$_)."'>$X</a>";}echo"<td>$X";}}echo($s?"</table>":"<p class='message'>".'No rows.')."\n";return$K;}function
referencable_primary($vf){$K=array();foreach(table_status('',true)as$Vf=>$Q){if($Vf!=$vf&&fk_support($Q)){foreach(fields($Vf)as$k){if($k["primary"]){if($K[$Vf]){unset($K[$Vf]);break;}$K[$Vf]=$k;}}}}return$K;}function
textarea($E,$Y,$M=10,$Ya=80){global$x;echo"<textarea name='$E' rows='$M' cols='$Ya' class='sqlarea jush-$x' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
edit_type($y,$k,$Xa,$n=array()){global$Nf,$zg,$Fg,$Zd;$U=$k["type"];echo'<td><select name="',h($y),'[type]" class="type" onfocus="lastType = selectValue(this);" onchange="editingTypeChange(this);"',on_help("getTarget(event).value",1),' aria-labelledby="label-type">';if($U&&!isset($zg[$U])&&!isset($n[$U]))array_unshift($Nf,$U);if($n)$Nf['Foreign keys']=$n;echo
optionlist($Nf,$U),'</select>
<td><input name="',h($y),'[length]" value="',h($k["length"]),'" size="3" onfocus="editingLengthFocus(this);"',(!$k["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":""),' onchange="editingLengthChange(this);" onkeyup="this.onchange();" aria-labelledby="label-length"><td class="options">';echo"<select name='".h($y)."[collation]'".(preg_match('~(char|text|enum|set)$~',$U)?"":" class='hidden'").'><option value="">('.'collation'.')'.optionlist($Xa,$k["collation"]).'</select>',($Fg?"<select name='".h($y)."[unsigned]'".(!$U||preg_match('~((^|[^o])int|float|double|decimal)$~',$U)?"":" class='hidden'").'><option>'.optionlist($Fg,$k["unsigned"]).'</select>':''),(isset($k['on_update'])?"<select name='".h($y)."[on_update]'".(preg_match('~timestamp|datetime~',$U)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),$k["on_update"]).'</select>':''),($n?"<select name='".h($y)."[on_delete]'".(preg_match("~`~",$U)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",$Zd),$k["on_delete"])."</select> ":" ");}function
process_length($od){global$Xb;return(preg_match("~^\\s*\\(?\\s*$Xb(?:\\s*,\\s*$Xb)*+\\s*\\)?\\s*\$~",$od)&&preg_match_all("~$Xb~",$od,$vd)?"(".implode(",",$vd[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$od)));}function
process_type($k,$Va="COLLATE"){global$Fg;return" $k[type]".process_length($k["length"]).(preg_match('~(^|[^o])int|float|double|decimal~',$k["type"])&&in_array($k["unsigned"],$Fg)?" $k[unsigned]":"").(preg_match('~char|text|enum|set~',$k["type"])&&$k["collation"]?" $Va ".q($k["collation"]):"");}function
process_field($k,$xg){global$x;$wb=$k["default"];return
array(idf_escape(trim($k["field"])),process_type($xg),($k["null"]?" NULL":" NOT NULL"),(isset($wb)?" DEFAULT ".((preg_match('~time~',$k["type"])&&preg_match('~^CURRENT_TIMESTAMP$~i',$wb))||($x=="sqlite"&&preg_match('~^CURRENT_(TIME|TIMESTAMP|DATE)$~i',$wb))||($k["type"]=="bit"&&preg_match("~^([0-9]+|b'[0-1]+')\$~",$wb))||($x=="pgsql"&&preg_match("~^[a-z]+\\(('[^']*')+\\)\$~",$wb))?$wb:q($wb)):""),(preg_match('~timestamp|datetime~',$k["type"])&&$k["on_update"]?" ON UPDATE $k[on_update]":""),(support("comment")&&$k["comment"]!=""?" COMMENT ".q($k["comment"]):""),($k["auto_increment"]?auto_increment():null),);}function
type_class($U){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$y=>$X){if(preg_match("~$y|$X~",$U))return" class='$y'";}}function
edit_fields($l,$Xa,$U="TABLE",$n=array(),$cb=false){global$e,$Qc;$l=array_values($l);echo'<thead><tr class="wrap">
';if($U=="PROCEDURE"){echo'<td>&nbsp;';}echo'<th id="label-name">',($U=="TABLE"?'Column name':'Parameter name'),'<td id="label-type">Type<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;" onblur="editingLengthBlur(this);"></textarea>
<td id="label-length">Length
<td>','Options';if($U=="TABLE"){echo'<td id="label-null">NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym id="label-ai" title="Auto Increment">AI</acronym>',doc_link(array('sql'=>"example-auto-increment.html",'sqlite'=>"autoinc.html",'pgsql'=>"datatype.html#DATATYPE-SERIAL",'mssql'=>"ms186775.aspx",)),'<td id="label-default">Default value
',(support("comment")?"<td id='label-comment'".($cb?"":" class='hidden'").">".'Comment':"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($l))."]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.3.1&amp;driver=mysql' alt='+' title='".'Add next'."'>",'<script type="text/javascript">row_count = ',count($l),';</script>
</thead>
<tbody onkeydown="return editingKeydown(event);">
';foreach($l
as$s=>$k){$s++;$le=$k[($_POST?"orig":"field")];$Bb=(isset($_POST["add"][$s-1])||(isset($k["field"])&&!$_POST["drop_col"][$s]))&&(support("drop_col")||$le=="");echo'<tr',($Bb?"":" style='display: none;'"),'>
',($U=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",$Qc),$k["inout"]):""),'<th>';if($Bb){echo'<input name="fields[',$s,'][field]" value="',h($k["field"]),'" onchange="editingNameChange(this);',($k["field"]!=""||count($l)>1?'':' editingAddRow(this);" onkeyup="if (this.value) editingAddRow(this);'),'" maxlength="64" autocapitalize="off" aria-labelledby="label-name">';}echo'<input type="hidden" name="fields[',$s,'][orig]" value="',h($le),'">
';edit_type("fields[$s]",$k,$Xa,$n);if($U=="TABLE"){echo'<td>',checkbox("fields[$s][null]",1,$k["null"],"","","block","label-null"),'<td><label class="block"><input type="radio" name="auto_increment_col" value="',$s,'"';if($k["auto_increment"]){echo' checked';}?> onclick="var field = this.form['fields[' + this.value + '][field]']; if (!field.value) { field.value = 'id'; field.onchange(); }" aria-labelledby="label-ai"></label><td><?php
echo
checkbox("fields[$s][has_default]",1,$k["has_default"],"","","","label-default"),'<input name="fields[',$s,'][default]" value="',h($k["default"]),'" onkeyup="keyupChange.call(this);" onchange="this.previousSibling.checked = true;" aria-labelledby="label-default">
',(support("comment")?"<td".($cb?"":" class='hidden'")."><input name='fields[$s][comment]' value='".h($k["comment"])."' maxlength='".($e->server_info>=5.5?1024:255)."' aria-labelledby='label-comment'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.3.1&amp;driver=mysql' alt='+' title='".'Add next'."' onclick='return !editingAddRow(this, 1);'>&nbsp;"."<input type='image' class='icon' name='up[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=up.gif&amp;version=4.3.1&amp;driver=mysql' alt='^' title='".'Move up'."' onclick='return !editingMoveRow(this, 1);'>&nbsp;"."<input type='image' class='icon' name='down[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=down.gif&amp;version=4.3.1&amp;driver=mysql' alt='v' title='".'Move down'."' onclick='return !editingMoveRow(this, 0);'>&nbsp;":""),($le==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=4.3.1&amp;driver=mysql' alt='x' title='".'Remove'."' onclick=\"return !editingRemoveRow(this, 'fields\$1[field]');\">":""),"\n";}}function
process_fields(&$l){$Sd=0;if($_POST["up"]){$id=0;foreach($l
as$y=>$k){if(key($_POST["up"])==$y){unset($l[$y]);array_splice($l,$id,0,array($k));break;}if(isset($k["field"]))$id=$Sd;$Sd++;}}elseif($_POST["down"]){$wc=false;foreach($l
as$y=>$k){if(isset($k["field"])&&$wc){unset($l[key($_POST["down"])]);array_splice($l,$Sd,0,array($wc));break;}if(key($_POST["down"])==$y)$wc=$k;$Sd++;}}elseif($_POST["add"]){$l=array_values($l);array_splice($l,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum($B){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($B[0][0].$B[0][0],$B[0][0],substr($B[0],1,-1))),'\\'))."'";}function
grant($q,$Pe,$d,$Yd){if(!$Pe)return
true;if($Pe==array("ALL PRIVILEGES","GRANT OPTION"))return($q=="GRANT"?queries("$q ALL PRIVILEGES$Yd WITH GRANT OPTION"):queries("$q ALL PRIVILEGES$Yd")&&queries("$q GRANT OPTION$Yd"));return
queries("$q ".preg_replace('~(GRANT OPTION)\\([^)]*\\)~','\\1',implode("$d, ",$Pe).$d).$Yd);}function
drop_create($Hb,$g,$Ib,$eg,$Jb,$A,$Ed,$Cd,$Dd,$Vd,$Md){if($_POST["drop"])query_redirect($Hb,$A,$Ed);elseif($Vd=="")query_redirect($g,$A,$Dd);elseif($Vd!=$Md){$lb=queries($g);queries_redirect($A,$Cd,$lb&&queries($Hb));if($lb)queries($Ib);}else
queries_redirect($A,$Cd,queries($eg)&&queries($Jb)&&queries($Hb)&&queries($g));}function
create_trigger($Yd,$L){global$x;$jg=" $L[Timing] $L[Event]".($L["Event"]=="UPDATE OF"?" ".idf_escape($L["Of"]):"");return"CREATE TRIGGER ".idf_escape($L["Trigger"]).($x=="mssql"?$Yd.$jg:$jg.$Yd).rtrim(" $L[Type]\n$L[Statement]",";").";";}function
create_routine($mf,$L){global$Qc;$P=array();$l=(array)$L["fields"];ksort($l);foreach($l
as$k){if($k["field"]!="")$P[]=(preg_match("~^($Qc)\$~",$k["inout"])?"$k[inout] ":"").idf_escape($k["field"]).process_type($k,"CHARACTER SET");}return"CREATE $mf ".idf_escape(trim($L["name"]))." (".implode(", ",$P).")".(isset($_GET["function"])?" RETURNS".process_type($L["returns"],"CHARACTER SET"):"").($L["language"]?" LANGUAGE $L[language]":"").rtrim("\n$L[definition]",";").";";}function
remove_definer($I){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\\1)',logged_user()).'`~','\\1',$I);}function
format_foreign_key($m){global$Zd;return" FOREIGN KEY (".implode(", ",array_map('idf_escape',$m["source"])).") REFERENCES ".table($m["table"])." (".implode(", ",array_map('idf_escape',$m["target"])).")".(preg_match("~^($Zd)\$~",$m["on_delete"])?" ON DELETE $m[on_delete]":"").(preg_match("~^($Zd)\$~",$m["on_update"])?" ON UPDATE $m[on_update]":"");}function
tar_file($pc,$og){$K=pack("a100a8a8a8a12a12",$pc,644,0,0,decoct($og->size),decoct(time()));$Pa=8*32;for($s=0;$s<strlen($K);$s++)$Pa+=ord($K[$s]);$K.=sprintf("%06o",$Pa)."\0 ";echo$K,str_repeat("\0",512-strlen($K));$og->send();echo
str_repeat("\0",511-($og->size+511)%512);}function
ini_bytes($Pc){$X=ini_get($Pc);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}function
doc_link($Ae){global$x,$e;$Ig=array('sql'=>"http://dev.mysql.com/doc/refman/".substr($e->server_info,0,3)."/en/",'sqlite'=>"http://www.sqlite.org/",'pgsql'=>"http://www.postgresql.org/docs/".substr($e->server_info,0,3)."/static/",'mssql'=>"http://msdn.microsoft.com/library/",'oracle'=>"http://download.oracle.com/docs/cd/B19306_01/server.102/b14200/",);return($Ae[$x]?"<a href='$Ig[$x]$Ae[$x]' target='_blank' rel='noreferrer'><sup>?</sup></a>":"");}function
ob_gzencode($Mf){return
gzencode($Mf);}function
db_size($i){global$e;if(!$e->select_db($i))return"?";$K=0;foreach(table_status()as$R)$K+=$R["Data_length"]+$R["Index_length"];return
format_number($K);}function
set_utf8mb4($g){global$e;static$P=false;if(!$P&&preg_match('~\butf8mb4~i',$g)){$P=true;echo"SET NAMES ".charset($e).";\n\n";}}function
connect_error(){global$b,$e,$T,$j,$Gb;if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$j)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$j,false);echo"<p class='links'>\n";foreach(array('database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status','replication'=>'Replication',)as$y=>$X){if(support($y))echo"<a href='".h(ME)."$y='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',$Gb[DRIVER],"<b>".h($e->server_info)."</b>","<b>$e->extension</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$h=$b->databases();if($h){$sf=support("scheme");$Xa=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);'>\n","<thead><tr>".(support("database")?"<td>&nbsp;":"")."<th>".'Database'." - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>"."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1' onclick=\"return !ajaxSetHtml('".h(js_escape(ME))."script=connect');\">".'Compute'."</a>"."</thead>\n";$h=($_GET["dbsize"]?count_tables($h):array_flip($h));foreach($h
as$i=>$S){$lf=h(ME)."db=".urlencode($i);$t=h("Db-".$i);echo"<tr".odd().">".(support("database")?"<td>".checkbox("db[]",$i,in_array($i,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$lf' id='$i'>".h($i)."</a>";$Wa=nbsp(db_collation($i,$Xa));echo"<td>".(support("database")?"<a href='$lf".($sf?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$Wa</a>":$Wa),"<td align='right'><a href='$lf&amp;schema=' id='tables-".h($i)."' title='".'Database schema'."'>".($_GET["dbsize"]?$S:"?")."</a>","<td align='right' id='size-".h($i)."'>".($_GET["dbsize"]?db_size($i):"?"),"\n";}echo"</table>\n",(support("database")?"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value='' onclick=\"selectCount('selected', formChecked(this, /^db/));\">\n"."<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n"."</div></fieldset>\n":""),"<script type='text/javascript'>tableCheck();</script>\n","<input type='hidden' name='token' value='$T'>\n","</form>\n";}}page_footer("db");}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?$e->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["replication"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}connect_error();exit;}$Zd="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";class
TmpFile{var$handler;var$size;function
__construct(){$this->handler=tmpfile();}function
write($gb){$this->size+=strlen($gb);fwrite($this->handler,$gb);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}$Xb="'(?:''|[^'\\\\]|\\\\.)*'";$Qc="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$l=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$N=array(idf_escape($_GET["field"]));$J=$Fb->select($a,$N,array(where($_GET,$l)),$N);$L=($J?$J->fetch_row():array());echo$L[0];exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$l=fields($a);if(!$l)$j=error();$R=table_status1($a,true);page_header(($l&&is_view($R)?$R['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".h($a),$j);$b->selectLinks($R);$bb=$R["Comment"];if($bb!="")echo"<p>".'Comment'.": ".h($bb)."\n";if($l)$b->tableStructurePrint($l);if(!is_view($R)){if(support("indexes")){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$v=indexes($a);if($v)$b->tableIndexesPrint($v);echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(fk_support($R)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$n=foreign_keys($a);if($n){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td>&nbsp;</thead>\n";foreach($n
as$E=>$m){echo"<tr title='".h($E)."'>","<th><i>".implode("</i>, <i>",array_map('h',$m["source"]))."</i>","<td><a href='".h($m["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($m["db"]),ME):($m["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($m["ns"]),ME):ME))."table=".urlencode($m["table"])."'>".($m["db"]!=""?"<b>".h($m["db"])."</b>.":"").($m["ns"]!=""?"<b>".h($m["ns"])."</b>.":"").h($m["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$m["target"]))."</i>)","<td>".nbsp($m["on_delete"])."\n","<td>".nbsp($m["on_update"])."\n",'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($E)).'">'.'Alter'.'</a>';}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}}if(support(is_view($R)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$wg=triggers($a);if($wg){echo"<table cellspacing='0'>\n";foreach($wg
as$y=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($y)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($y))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Wf=array();$Xf=array();$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$vd,PREG_SET_ORDER);foreach($vd
as$s=>$B){$Wf[$B[1]]=array($B[2],$B[3]);$Xf[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$qg=0;$Ca=-1;$rf=array();$cf=array();$md=array();foreach(table_status('',true)as$Q=>$R){if(is_view($R))continue;$Fe=0;$rf[$Q]["fields"]=array();foreach(fields($Q)as$E=>$k){$Fe+=1.25;$k["pos"]=$Fe;$rf[$Q]["fields"][$E]=$k;}$rf[$Q]["pos"]=($Wf[$Q]?$Wf[$Q]:array($qg,0));foreach($b->foreignKeys($Q)as$X){if(!$X["db"]){$kd=$Ca;if($Wf[$Q][1]||$Wf[$X["table"]][1])$kd=min(floatval($Wf[$Q][1]),floatval($Wf[$X["table"]][1]))-1;else$Ca-=.1;while($md[(string)$kd])$kd-=.0001;$rf[$Q]["references"][$X["table"]][(string)$kd]=array($X["source"],$X["target"]);$cf[$X["table"]][$Q][(string)$kd]=$X["target"];$md[(string)$kd]=true;}}$qg=max($qg,$rf[$Q]["pos"][0]+2.5+$Fe);}echo'<div id="schema" style="height: ',$qg,'em;" onselectstart="return false;">
<script type="text/javascript">
var tablePos = {',implode(",",$Xf)."\n",'};
var em = document.getElementById(\'schema\').offsetHeight / ',$qg,';
document.onmousemove = schemaMousemove;
document.onmouseup = function (ev) {
	schemaMouseup(ev, \'',js_escape(DB),'\');
};
</script>
';foreach($rf
as$E=>$Q){echo"<div class='table' style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em;' onmousedown='schemaMousedown(this, event);'>",'<a href="'.h(ME).'table='.urlencode($E).'"><b>'.h($E)."</b></a>";foreach($Q["fields"]as$k){$X='<span'.type_class($k["type"]).' title="'.h($k["full_type"].($k["null"]?" NULL":'')).'">'.h($k["field"]).'</span>';echo"<br>".($k["primary"]?"<i>$X</i>":$X);}foreach((array)$Q["references"]as$cg=>$df){foreach($df
as$kd=>$Ze){$ld=$kd-$Wf[$E][1];$s=0;foreach($Ze[0]as$Ff)echo"\n<div class='references' title='".h($cg)."' id='refs$kd-".($s++)."' style='left: $ld"."em; top: ".$Q["fields"][$Ff]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$ld)."em;'></div></div>";}}foreach((array)$cf[$E]as$cg=>$df){foreach($df
as$kd=>$d){$ld=$kd-$Wf[$E][1];$s=0;foreach($d
as$bg)echo"\n<div class='references' title='".h($cg)."' id='refd$kd-".($s++)."' style='left: $ld"."em; top: ".$Q["fields"][$bg]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME))."?file=arrow.gif) no-repeat right center;&amp;version=4.3.1&amp;driver=mysql'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$ld)."em;'></div></div>";}}echo"\n</div>\n";}foreach($rf
as$E=>$Q){foreach((array)$Q["references"]as$cg=>$df){foreach($df
as$kd=>$Ze){$Gd=$qg;$zd=-10;foreach($Ze[0]as$y=>$Ff){$Ge=$Q["pos"][0]+$Q["fields"][$Ff]["pos"];$He=$rf[$cg]["pos"][0]+$rf[$cg]["fields"][$Ze[1][$y]]["pos"];$Gd=min($Gd,$Ge,$He);$zd=max($zd,$Ge,$He);}echo"<div class='references' id='refl$kd' style='left: $kd"."em; top: $Gd"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($zd-$Gd)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$j){$jb="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$y)$jb.="&$y=".urlencode($_POST[$y]);cookie("adminer_export",substr($jb,1));$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$ic=dump_headers((count($S)==1?key($S):DB),(DB==""||count($S)>1));$Yc=preg_match('~sql~',$_POST["format"]);if($Yc){echo"-- Adminer $ga ".$Gb[DRIVER]." dump\n\n";if($x=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
".($_POST["data_style"]?"SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";$e->query("SET time_zone = '+00:00';");}}$Of=$_POST["db_style"];$h=array(DB);if(DB==""){$h=$_POST["databases"];if(is_string($h))$h=explode("\n",rtrim(str_replace("\r","",$h),"\n"));}foreach((array)$h
as$i){$b->dumpDatabase($i);if($e->select_db($i)){if($Yc&&preg_match('~CREATE~',$Of)&&($g=$e->result("SHOW CREATE DATABASE ".idf_escape($i),1))){set_utf8mb4($g);if($Of=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($i).";\n";echo"$g;\n";}if($Yc){if($Of)echo
use_sql($i).";\n\n";$qe="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$mf){foreach(get_rows("SHOW $mf STATUS WHERE Db = ".q($i),null,"-- ")as$L){$g=remove_definer($e->result("SHOW CREATE $mf ".idf_escape($L["Name"]),2));set_utf8mb4($g);$qe.=($Of!='DROP+CREATE'?"DROP $mf IF EXISTS ".idf_escape($L["Name"]).";;\n":"")."$g;;\n\n";}}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$L){$g=remove_definer($e->result("SHOW CREATE EVENT ".idf_escape($L["Name"]),3));set_utf8mb4($g);$qe.=($Of!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($L["Name"]).";;\n":"")."$g;;\n\n";}}if($qe)echo"DELIMITER ;;\n\n$qe"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$Rg=array();foreach(table_status('',true)as$E=>$R){$Q=(DB==""||in_array($E,(array)$_POST["tables"]));$pb=(DB==""||in_array($E,(array)$_POST["data"]));if($Q||$pb){if($ic=="tar"){$og=new
TmpFile;ob_start(array($og,'write'),1e5);}$b->dumpTable($E,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$Rg[]=$E;elseif($pb){$l=fields($E);$b->dumpData($E,$_POST["data_style"],"SELECT *".convert_fields($l,$l)." FROM ".table($E));}if($Yc&&$_POST["triggers"]&&$Q&&($wg=trigger_sql($E,$_POST["table_style"])))echo"\nDELIMITER ;;\n$wg\nDELIMITER ;\n";if($ic=="tar"){ob_end_flush();tar_file((DB!=""?"":"$i/")."$E.csv",$og);}elseif($Yc)echo"\n";}}foreach($Rg
as$Qg)$b->dumpTable($Qg,$_POST["table_style"],1);if($ic=="tar")echo
pack("x512");}}}if($Yc)echo"-- ".$e->result("SELECT NOW()")."\n";exit;}page_header('Export',$j,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table cellspacing="0">
';$tb=array('','USE','DROP+CREATE','CREATE');$Yf=array('','DROP+CREATE','CREATE');$qb=array('','TRUNCATE+INSERT','INSERT');if($x=="sql")$qb[]='INSERT+UPDATE';parse_str($_COOKIE["adminer_export"],$L);if(!$L)$L=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($L["events"])){$L["routines"]=$L["events"]=($_GET["dump"]=="");$L["triggers"]=$L["table_style"];}echo"<tr><th>".'Output'."<td>".html_select("output",$b->dumpOutput(),$L["output"],0)."\n";echo"<tr><th>".'Format'."<td>".html_select("format",$b->dumpFormat(),$L["format"],0)."\n";echo($x=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$tb,$L["db_style"]).(support("routine")?checkbox("routines",1,$L["routines"],'Routines'):"").(support("event")?checkbox("events",1,$L["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$Yf,$L["table_style"]).checkbox("auto_increment",1,$L["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$L["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$qb,$L["data_style"]),'</table>
<p><input type="submit" value="Export">
<input type="hidden" name="token" value="',$T,'">

<table cellspacing="0">
';$Ke=array();if(DB!=""){$Na=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$Na onclick='formCheck(this, /^tables\\[/);'>".'Tables'."</label>","<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$Na onclick='formCheck(this, /^data\\[/);'></label>","</thead>\n";$Rg="";$Zf=tables_list();foreach($Zf
as$E=>$U){$Je=preg_replace('~_.*~','',$E);$Na=($a==""||$a==(substr($a,-1)=="%"?"$Je%":$E));$Me="<tr><td>".checkbox("tables[]",$E,$Na,$E,"checkboxClick(event, this); formUncheck('check-tables');","block");if($U!==null&&!preg_match('~table~i',$U))$Rg.="$Me\n";else
echo"$Me<td align='right'><label class='block'><span id='Rows-".h($E)."'></span>".checkbox("data[]",$E,$Na,"","checkboxClick(event, this); formUncheck('check-data');")."</label>\n";$Ke[$Je]++;}echo$Rg;if($Zf)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}else{echo"<thead><tr><th style='text-align: left;'><label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"")." onclick='formCheck(this, /^databases\\[/);'>".'Database'."</label></thead>\n";$h=$b->databases();if($h){foreach($h
as$i){if(!information_schema($i)){$Je=preg_replace('~_.*~','',$i);echo"<tr><td>".checkbox("databases[]",$i,$a==""||$a=="$Je%",$i,"formUncheck('check-databases');","block")."\n";$Ke[$Je]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$rc=true;foreach($Ke
as$y=>$X){if($y!=""&&$X>1){echo($rc?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$y%")."'>".h($y)."</a>";$rc=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$J=$e->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$q=$J;if(!$J)$J=$e->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($q?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th>&nbsp;</thead>\n";while($L=$J->fetch_assoc())echo'<tr'.odd().'><td>'.h($L["User"])."<td>".h($L["Host"]).'<td><a href="'.h(ME.'user='.urlencode($L["User"]).'&host='.urlencode($L["Host"])).'">'.'Edit'."</a>\n";if(!$q||DB!="")echo"<tr".odd()."><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$j&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$Hc=&get_session("queries");$Gc=&$Hc[DB];if(!$j&&$_POST["clear"]){$Gc=array();redirect(remove_from_uri("history"));}page_header((isset($_GET["import"])?'Import':'SQL command'),$j);if(!$j&&$_POST){$o=false;if(!isset($_GET["import"]))$I=$_POST["query"];elseif($_POST["webfile"]){$o=@fopen((file_exists("adminer.sql")?"adminer.sql":"compress.zlib://adminer.sql.gz"),"rb");$I=($o?fread($o,1e6):false);}else$I=get_file("sql_file",true);if(is_string($I)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($I)+memory_get_usage()+8e6));if($I!=""&&strlen($I)<1e6){$H=$I.(preg_match("~;[ \t\r\n]*\$~",$I)?"":";");if(!$Gc||reset(end($Gc))!=$H){restart_session();$Gc[]=array($H,time());set_session("queries",$Hc);stop_session();}}$Gf="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$xb=";";$Sd=0;$Ub=true;$f=connect();if(is_object($f)&&DB!="")$f->select_db(DB);$ab=0;$Zb=array();$ve='[\'"'.($x=="sql"?'`#':($x=="sqlite"?'`[':($x=="mssql"?'[':''))).']|/\\*|-- |$'.($x=="pgsql"?'|\\$[^$]*\\$':'');$rg=microtime(true);parse_str($_COOKIE["adminer_export"],$la);$Lb=$b->dumpFormat();unset($Lb["sql"]);while($I!=""){if(!$Sd&&preg_match("~^$Gf*+DELIMITER\\s+(\\S+)~i",$I,$B)){$xb=$B[1];$I=substr($I,strlen($B[0]));}else{preg_match('('.preg_quote($xb)."\\s*|$ve)",$I,$B,PREG_OFFSET_CAPTURE,$Sd);list($wc,$Fe)=$B[0];if(!$wc&&$o&&!feof($o))$I.=fread($o,1e5);else{if(!$wc&&rtrim($I)=="")break;$Sd=$Fe+strlen($wc);if($wc&&rtrim($wc)!=$xb){while(preg_match('('.($wc=='/*'?'\\*/':($wc=='['?']':(preg_match('~^-- |^#~',$wc)?"\n":preg_quote($wc)."|\\\\."))).'|$)s',$I,$B,PREG_OFFSET_CAPTURE,$Sd)){$pf=$B[0][0];if(!$pf&&$o&&!feof($o))$I.=fread($o,1e5);else{$Sd=$B[0][1]+strlen($pf);if($pf[0]!="\\")break;}}}else{$Ub=false;$H=substr($I,0,$Fe);$ab++;$Me="<pre id='sql-$ab'><code class='jush-$x'>".$b->sqlCommandQuery($H)."</code></pre>\n";if($x=="sqlite"&&preg_match("~^$Gf*+ATTACH\\b~i",$H,$B)){echo$Me,"<p class='error'>".'ATTACH queries are not supported.'."\n";$Zb[]=" <a href='#sql-$ab'>$ab</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$Me;ob_flush();flush();}$If=microtime(true);if($e->multi_query($H)&&is_object($f)&&preg_match("~^$Gf*+USE\\b~i",$H))$f->query($H);do{$J=$e->store_result();$hg=" <span class='time'>(".format_time($If).")</span>".(strlen($H)<1000?" <a href='".h(ME)."sql=".urlencode(trim($H))."'>".'Edit'."</a>":"");if($e->error){echo($_POST["only_errors"]?$Me:""),"<p class='error'>".'Error in query'.($e->errno?" ($e->errno)":"").": ".error()."\n";$Zb[]=" <a href='#sql-$ab'>$ab</a>";if($_POST["error_stops"])break
2;}elseif(is_object($J)){$z=$_POST["limit"];$ke=select($J,$f,array(),$z);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$Pd=$J->num_rows;echo"<p>".($Pd?($z&&$Pd>$z?sprintf('%d / ',$z):"").lang(array('%d row','%d rows'),$Pd):""),$hg;$t="export-$ab";$hc=", <a href='#$t' onclick=\"return !toggle('$t');\">".'Export'."</a><span id='$t' class='hidden'>: ".html_select("output",$b->dumpOutput(),$la["output"])." ".html_select("format",$Lb,$la["format"])."<input type='hidden' name='query' value='".h($H)."'>"." <input type='submit' name='export' value='".'Export'."'><input type='hidden' name='token' value='$T'></span>\n";if($f&&preg_match("~^($Gf|\\()*+SELECT\\b~i",$H)&&($gc=explain($f,$H))){$t="explain-$ab";echo", <a href='#$t' onclick=\"return !toggle('$t');\">EXPLAIN</a>$hc","<div id='$t' class='hidden'>\n";select($gc,$f,$ke);echo"</div>\n";}else
echo$hc;echo"</form>\n";}}else{if(preg_match("~^$Gf*+(CREATE|DROP|ALTER)$Gf++(DATABASE|SCHEMA)\\b~i",$H)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($e->info)."'>".lang(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$e->affected_rows)."$hg\n";}$If=microtime(true);}while($e->next_result());}$I=substr($I,$Sd);$Sd=0;}}}}if($Ub)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"]){echo"<p class='message'>".lang(array('%d query executed OK.','%d queries executed OK.'),$ab-count($Zb))," <span class='time'>(".format_time($rg).")</span>\n";}elseif($Zb&&$ab>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Zb)."\n";}else
echo"<p class='error'>".upload_error($I)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$ec="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$H=$_GET["sql"];if($_POST)$H=$_POST["query"];elseif($_GET["history"]=="all")$H=$Gc;elseif($_GET["history"]!="")$H=$Gc[$_GET["history"]][0];echo"<p>";textarea("query",$H,20);echo($_POST?"":"<script type='text/javascript'>document.getElementsByTagName('textarea')[0].focus();</script>\n"),"<p>$ec\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{echo"<fieldset><legend>".'File upload'."</legend><div>",(ini_bool("file_uploads")?"SQL (&lt; ".ini_get("upload_max_filesize")."B): <input type='file' name='sql_file[]' multiple>\n$ec":'File uploads are disabled.'),"</div></fieldset>\n","<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>adminer.sql".(extension_loaded("zlib")?"[.gz]":"")."</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n","<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])),'Show only errors')."\n","<input type='hidden' name='token' value='$T'>\n";if(!isset($_GET["import"])&&$Gc){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($Gc);$X;$X=prev($Gc)){$y=key($Gc);list($H,$hg,$Pb)=$X;echo'<a href="'.h(ME."sql=&history=$y").'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$hg)."'>".@date("H:i:s",$hg)."</span>"." <code class='jush-$x'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$H)))),80,"</code>").($Pb?" <span class='time'>($Pb)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$l=fields($a);$Z=(isset($_GET["select"])?(count($_POST["check"])==1?where_check($_POST["check"][0],$l):""):where($_GET,$l));$Gg=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($l
as$E=>$k){if(!isset($k["privileges"][$Gg?"update":"insert"])||$b->fieldName($k)=="")unset($l[$E]);}if($_POST&&!$j&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($Gg?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".urlencode($a);$v=indexes($a);$Bg=unique_array($_GET["where"],$v);$Ve="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,'Item has been deleted.',$Fb->delete($a,$Ve,!$Bg));else{$P=array();foreach($l
as$E=>$k){$X=process_input($k);if($X!==false&&$X!==null)$P[idf_escape($E)]=$X;}if($Gg){if(!$P)redirect($A);queries_redirect($A,'Item has been updated.',$Fb->update($a,$P,$Ve,!$Bg));if(is_ajax()){page_headers();page_messages($j);exit;}}else{$J=$Fb->insert($a,$P);$jd=($J?last_id():0);queries_redirect($A,sprintf('Item%s has been inserted.',($jd?" $jd":"")),$J);}}}$L=null;if($_POST["save"])$L=(array)$_POST["fields"];elseif($Z){$N=array();foreach($l
as$E=>$k){if(isset($k["privileges"]["select"])){$ua=convert_field($k);if($_POST["clone"]&&$k["auto_increment"])$ua="''";if($x=="sql"&&preg_match("~enum|set~",$k["type"]))$ua="1*".idf_escape($E);$N[]=($ua?"$ua AS ":"").idf_escape($E);}}$L=array();if(!support("table"))$N=array("*");if($N){$J=$Fb->select($a,$N,array($Z),$N,array(),(isset($_GET["select"])?2:1));$L=$J->fetch_assoc();if(!$L)$L=false;if(isset($_GET["select"])&&(!$L||$J->fetch_assoc()))$L=null;}}if(!support("table")&&!$l){if(!$Z){$J=$Fb->select($a,array("*"),$Z,array("*"));$L=($J?$J->fetch_assoc():false);if(!$L)$L=array($Fb->primary=>"");}if($L){foreach($L
as$y=>$X){if(!$Z)$L[$y]=null;$l[$y]=array("field"=>$y,"null"=>($y!=$Fb->primary),"auto_increment"=>($y==$Fb->primary));}}}edit_form($a,$l,$L,$Gg);}elseif(isset($_GET["create"])){$a=$_GET["create"];$we=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$y)$we[$y]=$y;$bf=referencable_primary($a);$n=array();foreach($bf
as$Vf=>$k)$n[str_replace("`","``",$Vf)."`".str_replace("`","``",$k["field"])]=$Vf;$ne=array();$R=array();if($a!=""){$ne=fields($a);$R=table_status($a);if(!$R)$j='No tables.';}$L=$_POST;$L["fields"]=(array)$L["fields"];if($L["auto_increment_col"])$L["fields"][$L["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!process_fields($L["fields"])&&!$j){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$l=array();$ra=array();$Jg=false;$tc=array();$me=reset($ne);$pa=" FIRST";foreach($L["fields"]as$y=>$k){$m=$n[$k["type"]];$xg=($m!==null?$bf[$m]:$k);if($k["field"]!=""){if(!$k["has_default"])$k["default"]=null;if($y==$L["auto_increment_col"])$k["auto_increment"]=true;$Re=process_field($k,$xg);$ra[]=array($k["orig"],$Re,$pa);if($Re!=process_field($me,$me)){$l[]=array($k["orig"],$Re,$pa);if($k["orig"]!=""||$pa)$Jg=true;}if($m!==null)$tc[idf_escape($k["field"])]=($a!=""&&$x!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$n[$k["type"]],'source'=>array($k["field"]),'target'=>array($xg["field"]),'on_delete'=>$k["on_delete"],));$pa=" AFTER ".idf_escape($k["field"]);}elseif($k["orig"]!=""){$Jg=true;$l[]=array($k["orig"]);}if($k["orig"]!=""){$me=next($ne);if(!$me)$pa="";}}$ye="";if($we[$L["partition_by"]]){$ze=array();if($L["partition_by"]=='RANGE'||$L["partition_by"]=='LIST'){foreach(array_filter($L["partition_names"])as$y=>$X){$Y=$L["partition_values"][$y];$ze[]="\n  PARTITION ".idf_escape($X)." VALUES ".($L["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$ye.="\nPARTITION BY $L[partition_by]($L[partition])".($ze?" (".implode(",",$ze)."\n)":($L["partitions"]?" PARTITIONS ".(+$L["partitions"]):""));}elseif(support("partitioning")&&preg_match("~partitioned~",$R["Create_options"]))$ye.="\nREMOVE PARTITIONING";$C='Table has been altered.';if($a==""){cookie("adminer_engine",$L["Engine"]);$C='Table has been created.';}$E=trim($L["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($E),$C,alter_table($a,$E,($x=="sqlite"&&($Jg||$tc)?$ra:$l),$tc,($L["Comment"]!=$R["Comment"]?$L["Comment"]:null),($L["Engine"]&&$L["Engine"]!=$R["Engine"]?$L["Engine"]:""),($L["Collation"]&&$L["Collation"]!=$R["Collation"]?$L["Collation"]:""),($L["Auto_increment"]!=""?number($L["Auto_increment"]):""),$ye));}}page_header(($a!=""?'Alter table':'Create table'),$j,array("table"=>$a),h($a));if(!$_POST){$L=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($zg["int"])?"int":(isset($zg["integer"])?"integer":"")))),"partition_names"=>array(""),);if($a!=""){$L=$R;$L["name"]=$a;$L["fields"]=array();if(!$_GET["auto_increment"])$L["Auto_increment"]="";foreach($ne
as$k){$k["has_default"]=isset($k["default"]);$L["fields"][]=$k;}if(support("partitioning")){$yc="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$J=$e->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $yc ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($L["partition_by"],$L["partitions"],$L["partition"])=$J->fetch_row();$ze=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $yc AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$ze[""]="";$L["partition_names"]=array_keys($ze);$L["partition_values"]=array_values($ze);}}}$Xa=collations();$Wb=engines();foreach($Wb
as$Vb){if(!strcasecmp($Vb,$L["Engine"])){$L["Engine"]=$Vb;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name: <input name="name" maxlength="64" value="',h($L["name"]),'" autocapitalize="off">
';if($a==""&&!$_POST){?><script type='text/javascript'>focus(document.getElementById('form')['name']);</script><?php }echo($Wb?"<select name='Engine' onchange='helpClose();'".on_help("getTarget(event).value",1).">".optionlist(array(""=>"(".'engine'.")")+$Wb,$L["Engine"])."</select>":""),' ',($Xa&&!preg_match("~sqlite|mssql~",$x)?html_select("Collation",array(""=>"(".'collation'.")")+$Xa,$L["Collation"]):""),' <input type="submit" value="Save">
';}echo'
';if(support("columns")){echo'<table cellspacing="0" id="edit-fields" class="nowrap">
';$cb=($_POST?$_POST["comments"]:$L["Comment"]!="");if(!$_POST&&!$cb){foreach($L["fields"]as$k){if($k["comment"]!=""){$cb=true;break;}}}edit_fields($L["fields"],$Xa,"TABLE",$n,$cb);echo'</table>
<p>
Auto Increment: <input type="number" name="Auto_increment" size="6" value="',h($L["Auto_increment"]),'">
',checkbox("defaults",1,true,'Default values',"columnShow(this.checked, 5)","jsonly");if(!$_POST["defaults"]){echo'<script type="text/javascript">editingHideDefaults()</script>';}echo(support("comment")?"<label><input type='checkbox' name='comments' value='1' class='jsonly' onclick=\"columnShow(this.checked, 6); toggle('Comment'); if (this.checked) this.form['Comment'].focus();\"".($cb?" checked":"").">".'Comment'."</label>".' <input name="Comment" id="Comment" value="'.h($L["Comment"]).'" maxlength="'.($e->server_info>=5.5?2048:60).'"'.($cb?'':' class="hidden"').'>':''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}if(support("partitioning")){$xe=preg_match('~RANGE|LIST~',$L["partition_by"]);print_fieldset("partition",'Partition by',$L["partition_by"]);echo'<p>
',"<select name='partition_by' onchange='partitionByChange(this);'".on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')",1).">".optionlist(array(""=>"")+$we,$L["partition_by"])."</select>",'(<input name="partition" value="',h($L["partition"]),'">)
Partitions: <input type="number" name="partitions" class="size',($xe||!$L["partition_by"]?" hidden":""),'" value="',h($L["partitions"]),'">
<table cellspacing="0" id="partition-table"',($xe?"":" class='hidden'"),'>
<thead><tr><th>Partition name<th>Values</thead>
';foreach($L["partition_names"]as$y=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'"'.($y==count($L["partition_names"])-1?' onchange="partitionNameChange(this);"':'').' autocapitalize="off">','<td><input name="partition_values[]" value="'.h($L["partition_values"][$y]).'">';}echo'</table>
</div></fieldset>
';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Oc=array("PRIMARY","UNIQUE","INDEX");$R=table_status($a,true);if(preg_match('~MyISAM|M?aria'.($e->server_info>=5.6?'|InnoDB':'').'~i',$R["Engine"]))$Oc[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.($e->server_info>=5.7?'|InnoDB':'').'~i',$R["Engine"]))$Oc[]="SPATIAL";$v=indexes($a);$Le=array();if($x=="mongo"){$Le=$v["_id_"];unset($Oc[0]);unset($v["_id_"]);}$L=$_POST;if($_POST&&!$j&&!$_POST["add"]&&!$_POST["drop_col"]){$sa=array();foreach($L["indexes"]as$u){$E=$u["name"];if(in_array($u["type"],$Oc)){$d=array();$pd=array();$zb=array();$P=array();ksort($u["columns"]);foreach($u["columns"]as$y=>$c){if($c!=""){$od=$u["lengths"][$y];$yb=$u["descs"][$y];$P[]=idf_escape($c).($od?"(".(+$od).")":"").($yb?" DESC":"");$d[]=$c;$pd[]=($od?$od:null);$zb[]=$yb;}}if($d){$fc=$v[$E];if($fc){ksort($fc["columns"]);ksort($fc["lengths"]);ksort($fc["descs"]);if($u["type"]==$fc["type"]&&array_values($fc["columns"])===$d&&(!$fc["lengths"]||array_values($fc["lengths"])===$pd)&&array_values($fc["descs"])===$zb){unset($v[$E]);continue;}}$sa[]=array($u["type"],$E,$P);}}}foreach($v
as$E=>$fc)$sa[]=array($fc["type"],$E,"DROP");if(!$sa)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$sa));}page_header('Indexes',$j,array("table"=>$a),h($a));$l=array_keys(fields($a));if($_POST["add"]){foreach($L["indexes"]as$y=>$u){if($u["columns"][count($u["columns"])]!="")$L["indexes"][$y]["columns"][]="";}$u=end($L["indexes"]);if($u["type"]||array_filter($u["columns"],'strlen'))$L["indexes"][]=array("columns"=>array(1=>""));}if(!$L){foreach($v
as$y=>$u){$v[$y]["name"]=$y;$v[$y]["columns"][]="";}$v[]=array("columns"=>array(1=>""));$L["indexes"]=$v;}?>

<form action="" method="post">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th><input type="submit" class="wayoff">Column (length)
<th id="label-name">Name
<th><noscript><input type='image' class='icon' name='add[0]' src='" . h(preg_replace("~\\?.*~", "", ME)) . "?file=plus.gif&amp;version=4.3.1&amp;driver=mysql' alt='+' title='Add next'></noscript>&nbsp;
</thead>
<?php
if($Le){echo"<tr><td>PRIMARY<td>";foreach($Le["columns"]as$y=>$c){echo
select_input(" disabled",$l,$c),"<label><input disabled type='checkbox'>".'descending'."</label> ";}echo"<td><td>\n";}$w=1;foreach($L["indexes"]as$u){if(!$_POST["drop_col"]||$w!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$w][type]",array(-1=>"")+$Oc,$u["type"],($w==count($L["indexes"])?"indexesAddRow(this);":1),"label-type"),"<td>";ksort($u["columns"]);$s=1;foreach($u["columns"]as$y=>$c){echo"<span>".select_input(" name='indexes[$w][columns][$s]' onchange=\"".($s==count($u["columns"])?"indexesAddColumn":"indexesChangeColumn")."(this, '".h(js_escape($x=="sql"?"":$_GET["indexes"]."_"))."');\" title='".'Column'."'",($l?array_combine($l,$l):$l),$c),($x=="sql"||$x=="mssql"?"<input type='number' name='indexes[$w][lengths][$s]' class='size' value='".h($u["lengths"][$y])."' title='".'Length'."'>":""),($x!="sql"?checkbox("indexes[$w][descs][$s]",1,$u["descs"][$y],'descending'):"")," </span>";$s++;}echo"<td><input name='indexes[$w][name]' value='".h($u["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n","<td><input type='image' class='icon' name='drop_col[$w]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=4.3.1&amp;driver=mysql' alt='x' title='".'Remove'."' onclick=\"return !editingRemoveRow(this, 'indexes\$1[type]');\">\n";}$w++;}echo'</table>
<p>
<input type="submit" value="Save">
<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["database"])){$L=$_POST;if($_POST&&!$j&&!isset($_POST["add_x"])){$E=trim($L["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$E){if(DB!=""){$_GET["db"]=$E;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($E),'Database has been renamed.',rename_database($E,$L["collation"]));}else{$h=explode("\n",str_replace("\r","",$E));$Pf=true;$id="";foreach($h
as$i){if(count($h)==1||$i!=""){if(!create_database($i,$L["collation"]))$Pf=false;$id=$i;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($id),'Database has been created.',$Pf);}}else{if(!$L["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($E).(preg_match('~^[a-z0-9_]+$~i',$L["collation"])?" COLLATE $L[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$j,array(),h(DB));$Xa=collations();$E=DB;if($_POST)$E=$L["name"];elseif(DB!="")$L["collation"]=db_collation(DB,$Xa);elseif($x=="sql"){foreach(get_vals("SHOW GRANTS")as$q){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\\.\\*)?~',$q,$B)&&$B[1]){$E=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($E,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($E).'</textarea><br>':'<input name="name" id="name" value="'.h($E).'" maxlength="64" autocapitalize="off">')."\n".($Xa?html_select("collation",array(""=>"(".'collation'.")")+$Xa,$L["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mssql'=>"ms187963.aspx",)):"");?>
<script type='text/javascript'>focus(document.getElementById('name'));</script>
<input type="submit" value="Save">
<?php
if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' class='icon' name='add' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.3.1&amp;driver=mysql' alt='+' title='".'Add next'."'>\n";echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["call"])){$da=$_GET["call"];page_header('Call'.": ".h($da),$j);$mf=routine($da,(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Nc=array();$qe=array();foreach($mf["fields"]as$s=>$k){if(substr($k["inout"],-3)=="OUT")$qe[$s]="@".idf_escape($k["field"])." AS ".idf_escape($k["field"]);if(!$k["inout"]||substr($k["inout"],0,2)=="IN")$Nc[]=$s;}if(!$j&&$_POST){$Ja=array();foreach($mf["fields"]as$y=>$k){if(in_array($y,$Nc)){$X=process_input($k);if($X===false)$X="''";if(isset($qe[$y]))$e->query("SET @".idf_escape($k["field"])." = $X");}$Ja[]=(isset($qe[$y])?"@".idf_escape($k["field"]):$X);}$I=(isset($_GET["callf"])?"SELECT":"CALL")." ".table($da)."(".implode(", ",$Ja).")";echo"<p><code class='jush-$x'>".h($I)."</code> <a href='".h(ME)."sql=".urlencode($I)."'>".'Edit'."</a>\n";if(!$e->multi_query($I))echo"<p class='error'>".error()."\n";else{$f=connect();if(is_object($f))$f->select_db(DB);do{$J=$e->store_result();if(is_object($J))select($J,$f);else
echo"<p class='message'>".lang(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$e->affected_rows)."\n";}while($e->next_result());if($qe)select($e->query("SELECT ".implode(", ",$qe)));}}echo'
<form action="" method="post">
';if($Nc){echo"<table cellspacing='0'>\n";foreach($Nc
as$y){$k=$mf["fields"][$y];$E=$k["field"];echo"<tr><th>".$b->fieldName($k);$Y=$_POST["fields"][$E];if($Y!=""){if($k["type"]=="enum")$Y=+$Y;if($k["type"]=="set")$Y=array_sum($Y);}input($k,$Y,(string)$_POST["function"][$E]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$E=$_GET["name"];$L=$_POST;if($_POST&&!$j&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){$C=($_POST["drop"]?'Foreign key has been dropped.':($E!=""?'Foreign key has been altered.':'Foreign key has been created.'));$A=ME."table=".urlencode($a);if(!$_POST["drop"]){$L["source"]=array_filter($L["source"],'strlen');ksort($L["source"]);$bg=array();foreach($L["source"]as$y=>$X)$bg[$y]=$L["target"][$y];$L["target"]=$bg;}if($x=="sqlite")queries_redirect($A,$C,recreate_table($a,$a,array(),array(),array(" $E"=>($_POST["drop"]?"":" ".format_foreign_key($L)))));else{$sa="ALTER TABLE ".table($a);$Hb="\nDROP ".($x=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($E);if($_POST["drop"])query_redirect($sa.$Hb,$A,$C);else{query_redirect($sa.($E!=""?"$Hb,":"")."\nADD".format_foreign_key($L),$A,$C);$j='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.'."<br>$j";}}}page_header('Foreign key',$j,array("table"=>$a),h($a));if($_POST){ksort($L["source"]);if($_POST["add"])$L["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$L["target"]=array();}elseif($E!=""){$n=foreign_keys($a);$L=$n[$E];$L["source"][]="";}else{$L["table"]=$a;$L["source"]=array("");}$Ff=array_keys(fields($a));$bg=($a===$L["table"]?$Ff:array_keys(fields($L["table"])));$af=array_keys(array_filter(table_status('',true),'fk_support'));echo'
<form action="" method="post">
<p>
';if($L["db"]==""&&$L["ns"]==""){echo'Target table:
',html_select("table",$af,$L["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table cellspacing="0">
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';$w=0;foreach($L["source"]as$y=>$X){echo"<tr>","<td>".html_select("source[".(+$y)."]",array(-1=>"")+$Ff,$X,($w==count($L["source"])-1?"foreignAddRow(this);":1),"label-source"),"<td>".html_select("target[".(+$y)."]",$bg,$L["target"][$y],1,"label-target");$w++;}echo'</table>
<p>
ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",$Zd),$L["on_delete"]),' ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",$Zd),$L["on_update"]),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"ms174979.aspx",'oracle'=>"clauses002.htm#sthref2903",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';}if($E!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$L=$_POST;$oe="VIEW";if($x=="pgsql"&&$a!=""){$Jf=table_status($a);$oe=strtoupper($Jf["Engine"]);}if($_POST&&!$j){$E=trim($L["name"]);$ua=" AS\n$L[select]";$A=ME."table=".urlencode($E);$C='View has been altered.';$U=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$E&&$x!="sqlite"&&$U=="VIEW"&&$oe=="VIEW")query_redirect(($x=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($E).$ua,$A,$C);else{$dg=$E."_adminer_".uniqid();drop_create("DROP $oe ".table($a),"CREATE $U ".table($E).$ua,"DROP $U ".table($E),"CREATE $U ".table($dg).$ua,"DROP $U ".table($dg),($_POST["drop"]?substr(ME,0,-1):$A),'View has been dropped.',$C,'View has been created.',$a,$E);}}if(!$_POST&&$a!=""){$L=view($a);$L["name"]=$a;$L["materialized"]=($oe!="VIEW");if(!$j)$j=error();}page_header(($a!=""?'Alter view':'Create view'),$j,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($L["name"]),'" maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$L["materialized"],'Materialized view'):""),'<p>';textarea("select",$L["select"]);echo'<p>
<input type="submit" value="Save">
';if($_GET["view"]!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Tc=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Kf=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$L=$_POST;if($_POST&&!$j){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($L["INTERVAL_FIELD"],$Tc)&&isset($Kf[$L["STATUS"]])){$qf="\nON SCHEDULE ".($L["INTERVAL_VALUE"]?"EVERY ".q($L["INTERVAL_VALUE"])." $L[INTERVAL_FIELD]".($L["STARTS"]?" STARTS ".q($L["STARTS"]):"").($L["ENDS"]?" ENDS ".q($L["ENDS"]):""):"AT ".q($L["STARTS"]))." ON COMPLETION".($L["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$qf.($aa!=$L["EVENT_NAME"]?"\nRENAME TO ".idf_escape($L["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($L["EVENT_NAME"]).$qf)."\n".$Kf[$L["STATUS"]]." COMMENT ".q($L["EVENT_COMMENT"]).rtrim(" DO\n$L[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$j);if(!$L&&$aa!=""){$M=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$L=reset($M);}echo'
<form action="" method="post">
<table cellspacing="0">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($L["EVENT_NAME"]),'" maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$L[EXECUTE_AT]$L[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($L["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($L["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$Tc,$L["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Kf,$L["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($L["EVENT_COMMENT"]),'" maxlength="64">
<tr><th>&nbsp;<td>',checkbox("ON_COMPLETION","PRESERVE",$L["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$L["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=$_GET["procedure"];$mf=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$L=$_POST;$L["fields"]=(array)$L["fields"];if($_POST&&!process_fields($L["fields"])&&!$j){$dg="$L[name]_adminer_".uniqid();drop_create("DROP $mf ".idf_escape($da),create_routine($mf,$L),"DROP $mf ".idf_escape($L["name"]),create_routine($mf,array("name"=>$dg)+$L),"DROP $mf ".idf_escape($dg),substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$da,$L["name"]);}page_header(($da!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($da):(isset($_GET["function"])?'Create function':'Create procedure')),$j);if(!$_POST&&$da!=""){$L=routine($da,$mf);$L["name"]=$da;}$Xa=get_vals("SHOW CHARACTER SET");sort($Xa);$nf=routine_languages();echo'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($L["name"]),'" maxlength="64" autocapitalize="off">
',($nf?'Language'.": ".html_select("language",$nf,$L["language"]):""),'<input type="submit" value="Save">
<table cellspacing="0" class="nowrap">
';edit_fields($L["fields"],$Xa,$mf);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$L["returns"],$Xa);}echo'</table>
<p>';textarea("definition",$L["definition"]);echo'<p>
<input type="submit" value="Save">
';if($da!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$E=$_GET["name"];$vg=trigger_options();$L=(array)trigger($E)+array("Trigger"=>$a."_bi");if($_POST){if(!$j&&in_array($_POST["Timing"],$vg["Timing"])&&in_array($_POST["Event"],$vg["Event"])&&in_array($_POST["Type"],$vg["Type"])){$Yd=" ON ".table($a);$Hb="DROP TRIGGER ".idf_escape($E).($x=="pgsql"?$Yd:"");$A=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($Hb,$A,'Trigger has been dropped.');else{if($E!="")queries($Hb);queries_redirect($A,($E!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($Yd,$_POST)));if($E!="")queries(create_trigger($Yd,$L+array("Type"=>reset($vg["Type"]))));}}$L=$_POST;}page_header(($E!=""?'Alter trigger'.": ".h($E):'Create trigger'),$j,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table cellspacing="0">
<tr><th>Time<td>',html_select("Timing",$vg["Timing"],$L["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',html_select("Event",$vg["Event"],$L["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$vg["Event"])?" <input name='Of' value='".h($L["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',html_select("Type",$vg["Type"],$L["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($L["Trigger"]);?>" maxlength="64" autocapitalize="off">
<script type="text/javascript">document.getElementById('form')['Timing'].onchange();</script>
<p><?php textarea("Statement",$L["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($E!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["user"])){$fa=$_GET["user"];$Pe=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$L){foreach(explode(",",($L["Privilege"]=="Grant option"?"":$L["Context"]))as$hb)$Pe[$hb][$L["Privilege"]]=$L["Comment"];}$Pe["Server Admin"]+=$Pe["File access on server"];$Pe["Databases"]["Create routine"]=$Pe["Procedures"]["Create routine"];unset($Pe["Procedures"]["Create routine"]);$Pe["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$Pe["Columns"][$X]=$Pe["Tables"][$X];unset($Pe["Server Admin"]["Usage"]);foreach($Pe["Tables"]as$y=>$X)unset($Pe["Databases"][$y]);$Ld=array();if($_POST){foreach($_POST["objects"]as$y=>$X)$Ld[$X]=(array)$Ld[$X]+(array)$_POST["grants"][$y];}$_c=array();$Wd="";if(isset($_GET["host"])&&($J=$e->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])))){while($L=$J->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$L[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\\([^)]+\\))?~',$B[1],$vd,PREG_SET_ORDER)){foreach($vd
as$X){if($X[1]!="USAGE")$_c["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$L[0]))$_c["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$L[0],$B))$Wd=$B[1];}}if($_POST&&!$j){$Xd=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $Xd",ME."privileges=",'User has been dropped.');else{$Nd=q($_POST["user"])."@".q($_POST["host"]);$_e=$_POST["pass"];if($_e!=''&&!$_POST["hashed"]){$_e=$e->result("SELECT PASSWORD(".q($_e).")");$j=!$_e;}$lb=false;if(!$j){if($Xd!=$Nd){$lb=queries(($e->server_info<5?"GRANT USAGE ON *.* TO":"CREATE USER")." $Nd IDENTIFIED BY PASSWORD ".q($_e));$j=!$lb;}elseif($_e!=$Wd)queries("SET PASSWORD FOR $Nd = ".q($_e));}if(!$j){$jf=array();foreach($Ld
as$Rd=>$q){if(isset($_GET["grant"]))$q=array_filter($q);$q=array_keys($q);if(isset($_GET["grant"]))$jf=array_diff(array_keys(array_filter($Ld[$Rd],'strlen')),$q);elseif($Xd==$Nd){$Ud=array_keys((array)$_c[$Rd]);$jf=array_diff($Ud,$q);$q=array_diff($q,$Ud);unset($_c[$Rd]);}if(preg_match('~^(.+)\\s*(\\(.*\\))?$~U',$Rd,$B)&&(!grant("REVOKE",$jf,$B[2]," ON $B[1] FROM $Nd")||!grant("GRANT",$q,$B[2]," ON $B[1] TO $Nd"))){$j=true;break;}}}if(!$j&&isset($_GET["host"])){if($Xd!=$Nd)queries("DROP USER $Xd");elseif(!isset($_GET["grant"])){foreach($_c
as$Rd=>$jf){if(preg_match('~^(.+)(\\(.*\\))?$~U',$Rd,$B))grant("REVOKE",array_keys($jf),$B[2]," ON $B[1] FROM $Nd");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$j);if($lb)$e->query("DROP USER $Nd");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$j,array("privileges"=>array('','Privileges')));if($_POST){$L=$_POST;$_c=$Ld;}else{$L=$_GET+array("host"=>$e->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$L["pass"]=$Wd;if($Wd!="")$L["hashed"]=true;$_c[(DB==""||$_c?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0">
<tr><th>Server<td><input name="host" maxlength="60" value="',h($L["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" maxlength="16" value="',h($L["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($L["pass"]),'">
';if(!$L["hashed"]){echo'<script type="text/javascript">typePassword(document.getElementById(\'pass\'));</script>';}echo
checkbox("hashed",1,$L["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$s=0;foreach($_c
as$Rd=>$q){echo'<th>'.($Rd!="*.*"?"<input name='objects[$s]' value='".h($Rd)."' size='10' autocapitalize='off'>":"<input type='hidden' name='objects[$s]' value='*.*' size='10'>*.*");$s++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$hb=>$yb){foreach((array)$Pe[$hb]as$Oe=>$bb){echo"<tr".odd()."><td".($yb?">$yb<td":" colspan='2'").' lang="en" title="'.h($bb).'">'.h($Oe);$s=0;foreach($_c
as$Rd=>$q){$E="'grants[$s][".h(strtoupper($Oe))."]'";$Y=$q[strtoupper($Oe)];if($hb=="Server Admin"&&$Rd!=(isset($_c["*.*"])?"*.*":".*"))echo"<td>&nbsp;";elseif(isset($_GET["grant"]))echo"<td><select name=$E><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'><input type='checkbox' name=$E value='1'".($Y?" checked":"").($Oe=="All privileges"?" id='grants-$s-all'":($Oe=="Grant option"?"":" onclick=\"if (this.checked) formUncheck('grants-$s-all');\""))."></label>";$s++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")&&$_POST&&!$j){$ed=0;foreach((array)$_POST["kill"]as$X){if(kill_process($X))$ed++;}queries_redirect(ME."processlist=",lang(array('%d process has been killed.','%d processes have been killed.'),$ed),$ed||!$_POST["kill"]);}page_header('Process list',$j);echo'
<form action="" method="post">
<table cellspacing="0" onclick="tableClick(event);" ondblclick="tableClick(event, true);" class="nowrap checkable">
';$s=-1;foreach(process_list()as$s=>$L){if(!$s){echo"<thead><tr lang='en'>".(support("kill")?"<th>&nbsp;":"");foreach($L
as$y=>$X)echo"<th>$y".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($y),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"../b14237/dynviews_2088.htm",));echo"</thead>\n";}echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$L[$x=="sql"?"Id":"pid"],0):"");foreach($L
as$y=>$X)echo"<td>".(($x=="sql"&&$y=="Info"&&preg_match("~Query|Killed~",$L["Command"])&&$X!="")||($x=="pgsql"&&$y=="current_query"&&$X!="<IDLE>")||($x=="oracle"&&$y=="sql_text"&&$X!="")?"<code class='jush-$x'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($L["db"]!=""?"db=".urlencode($L["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>':nbsp($X));echo"\n";}echo'</table>
<script type=\'text/javascript\'>tableCheck();</script>
<p>
';if(support("kill")){echo($s+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["replication"])){page_header('Replication');echo"<h3>".'Master status'.doc_link(array("sql"=>"show-master-status.html"))."</h3>\n";$ud=replication_status("MASTER");if(!$ud)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($ud[0]as$y=>$X){echo"<tr>","<th>".h($y),"<td>".nbsp($X);}echo"</table>\n";}$Df=replication_status("SLAVE");if($Df){echo"<h3>".'Slave status'.doc_link(array("sql"=>"show-slave-status.html"))."</h3>\n";foreach($Df[0]as$Cf){echo"<table cellspacing='0'>\n";foreach($Cf
as$y=>$X){echo"<tr>","<th>".h($y),"<td>".nbsp($X);}echo"</table>\n";}}}elseif(isset($_GET["select"])){$a=$_GET["select"];$R=table_status1($a);$v=indexes($a);$l=fields($a);$n=column_foreign_keys($a);$Td="";if($R["Oid"]){$Td=($x=="sqlite"?"rowid":"oid");$v[]=array("type"=>"PRIMARY","columns"=>array($Td));}parse_str($_COOKIE["adminer_import"],$ma);$kf=array();$d=array();$gg=null;foreach($l
as$y=>$k){$E=$b->fieldName($k);if(isset($k["privileges"]["select"])&&$E!=""){$d[$y]=html_entity_decode(strip_tags($E),ENT_QUOTES);if(is_shortable($k))$gg=$b->selectLengthProcess();}$kf+=$k["privileges"];}list($N,$r)=$b->selectColumnsProcess($d,$v);$Xc=count($r)<count($N);$Z=$b->selectSearchProcess($l,$v);$he=$b->selectOrderProcess($l,$v);$z=$b->selectLimitProcess();$yc=($N?implode(", ",$N):"*".($Td?", $Td":"")).convert_fields($d,$l,$N)."\nFROM ".table($a);$Ac=($r&&$Xc?"\nGROUP BY ".implode(", ",$r):"").($he?"\nORDER BY ".implode(", ",$he):"");if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Cg=>$L){$ua=convert_field($l[key($L)]);$N=array($ua?$ua:idf_escape(key($L)));$Z[]=where_check($Cg,$l);$K=$Fb->select($a,$N,$Z,$N);if($K)echo
reset($K->fetch_row());}exit;}if($_POST&&!$j){$Vg=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$Oa=array();foreach($_POST["check"]as$Ma)$Oa[]=where_check($Ma,$l);$Vg[]="((".implode(") OR (",$Oa)."))";}$Vg=($Vg?"\nWHERE ".implode(" AND ",$Vg):"");$Le=$Eg=null;foreach($v
as$u){if($u["type"]=="PRIMARY"){$Le=array_flip($u["columns"]);$Eg=($N?$Le:array());break;}}foreach((array)$Eg
as$y=>$X){if(in_array(idf_escape($y),$N))unset($Eg[$y]);}if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");if(!is_array($_POST["check"])||$Eg===array())$I="SELECT $yc$Vg$Ac";else{$Ag=array();foreach($_POST["check"]as$X)$Ag[]="(SELECT".limit($yc,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$l).$Ac,1).")";$I=implode(" UNION ALL ",$Ag);}$b->dumpData($a,"table",$I);exit;}if(!$b->selectEmailProcess($Z,$n)){if($_POST["save"]||$_POST["delete"]){$J=true;$na=0;$P=array();if(!$_POST["delete"]){foreach($d
as$E=>$X){$X=process_input($l[$E]);if($X!==null&&($_POST["clone"]||$X!==false))$P[idf_escape($E)]=($X!==false?$X:idf_escape($E));}}if($_POST["delete"]||$P){if($_POST["clone"])$I="INTO ".table($a)." (".implode(", ",array_keys($P)).")\nSELECT ".implode(", ",$P)."\nFROM ".table($a);if($_POST["all"]||($Eg===array()&&is_array($_POST["check"]))||$Xc){$J=($_POST["delete"]?$Fb->delete($a,$Vg):($_POST["clone"]?queries("INSERT $I$Vg"):$Fb->update($a,$P,$Vg)));$na=$e->affected_rows;}else{foreach((array)$_POST["check"]as$X){$Ug="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$l);$J=($_POST["delete"]?$Fb->delete($a,$Ug,1):($_POST["clone"]?queries("INSERT".limit1($I,$Ug)):$Fb->update($a,$P,$Ug)));if(!$J)break;$na+=$e->affected_rows;}}}$C=lang(array('%d item has been affected.','%d items have been affected.'),$na);if($_POST["clone"]&&$J&&$na==1){$jd=last_id();if($jd)$C=sprintf('Item%s has been inserted.'," $jd");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$C,$J);if(!$_POST["delete"]){edit_form($a,$l,(array)$_POST["fields"],!$_POST["clone"]);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$j='Ctrl+click on a value to modify it.';else{$J=true;$na=0;foreach($_POST["val"]as$Cg=>$L){$P=array();foreach($L
as$y=>$X){$y=bracket_escape($y,1);$P[idf_escape($y)]=(preg_match('~char|text~',$l[$y]["type"])||$X!=""?$b->processInput($l[$y],$X):"NULL");}$J=$Fb->update($a,$P," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Cg,$l),!($Xc||$Eg===array())," ");if(!$J)break;$na+=$e->affected_rows;}queries_redirect(remove_from_uri(),lang(array('%d item has been affected.','%d items have been affected.'),$na),$J);}}elseif(!is_string($oc=get_file("csv_file",true)))$j=upload_error($oc);elseif(!preg_match('~~u',$oc))$j='File must be in UTF-8 encoding.';else{cookie("adminer_import","output=".urlencode($ma["output"])."&format=".urlencode($_POST["separator"]));$J=true;$Ya=array_keys($l);preg_match_all('~(?>"[^"]*"|[^"\\r\\n]+)+~',$oc,$vd);$na=count($vd[0]);$Fb->begin();$wf=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$M=array();foreach($vd[0]as$y=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$wf]*)$wf~",$X.$wf,$wd);if(!$y&&!array_diff($wd[1],$Ya)){$Ya=$wd[1];$na--;}else{$P=array();foreach($wd[1]as$s=>$Ua)$P[idf_escape($Ya[$s])]=($Ua==""&&$l[$Ya[$s]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$Ua))));$M[]=$P;}}$J=(!$M||$Fb->insertUpdate($a,$M,$Le));if($J)$J=$Fb->commit();queries_redirect(remove_from_uri("page"),lang(array('%d row has been imported.','%d rows have been imported.'),$na),$J);$Fb->rollback();}}}$Vf=$b->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Vf",$j);$P=null;if(isset($kf["insert"])||!support("table")){$P="";foreach((array)$_GET["where"]as$X){if(count($n[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!preg_match('~[_%]~',$X["val"]))))$P.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($R,$P);if(!$d&&support("table"))echo"<p class='error'>".'Unable to select the table'.($l?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">',"</div>\n";$b->selectColumnsPrint($N,$d);$b->selectSearchPrint($Z,$d,$v);$b->selectOrderPrint($he,$d,$v);$b->selectLimitPrint($z);$b->selectLengthPrint($gg);$b->selectActionPrint($v);echo"</form>\n";$F=$_GET["page"];if($F=="last"){$xc=$e->result(count_rows($a,$Z,$Xc,$r));$F=floor(max(0,$xc-1)/$z);}$tf=$N;if(!$tf){$tf[]="*";if($Td)$tf[]=$Td;}$ib=convert_fields($d,$l,$N);if($ib)$tf[]=substr($ib,2);$J=$Fb->select($a,$tf,$Z,$r,$he,$z,$F,true);if(!$J)echo"<p class='error'>".error()."\n";else{if($x=="mssql"&&$F)$J->seek($z*$F);$Tb=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$M=array();while($L=$J->fetch_assoc()){if($F&&$x=="oracle")unset($L["RNUM"]);$M[]=$L;}if($_GET["page"]!="last"&&+$z&&$r&&$Xc&&$x=="sql")$xc=$e->result(" SELECT FOUND_ROWS()");if(!$M)echo"<p class='message'>".'No rows.'."\n";else{$Ba=$b->backwardKeys($a,$Vf);echo"<table id='table' cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);' onkeydown='return editingKeydown(event);'>\n","<thead><tr>".(!$r&&$N?"":"<td><input type='checkbox' id='all-page' onclick='formCheck(this, /check/);' class='jsonly'> <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$Kd=array();$zc=array();reset($N);$Xe=1;foreach($M[0]as$y=>$X){if($y!=$Td){$X=$_GET["columns"][key($N)];$k=$l[$N?($X?$X["col"]:current($N)):$y];$E=($k?$b->fieldName($k,$Xe):($X["fun"]?"*":$y));if($E!=""){$Xe++;$Kd[$y]=$E;$c=idf_escape($y);$Jc=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($y);$yb="&desc%5B0%5D=1";echo'<th onmouseover="columnMouse(this);" onmouseout="columnMouse(this, \' hidden\');">','<a href="'.h($Jc.($he[0]==$c||$he[0]==$y||(!$he&&$Xc&&$r[0]==$c)?$yb:'')).'">';echo
apply_sql_function($X["fun"],$E)."</a>";echo"<span class='column hidden'>","<a href='".h($Jc.$yb)."' title='".'descending'."' class='text'> ↓</a>";if(!$X["fun"])echo'<a href="#fieldset-search" onclick="selectSearch(\''.h(js_escape($y)).'\'); return false;" title="'.'Search'.'" class="text jsonly"> =</a>';echo"</span>";}$zc[$y]=$X["fun"];next($N);}}$pd=array();if($_GET["modify"]){foreach($M
as$L){foreach($L
as$y=>$X)$pd[$y]=max($pd[$y],min(40,strlen(utf8_decode($X))));}}echo($Ba?"<th>".'Relations':"")."</thead>\n";if(is_ajax()){if($z%2==1&&$F%2==1)odd();ob_end_clean();}foreach($b->rowDescriptions($M,$n)as$D=>$L){$Bg=unique_array($M[$D],$v);if(!$Bg){$Bg=array();foreach($M[$D]as$y=>$X){if(!preg_match('~^(COUNT\\((\\*|(DISTINCT )?`(?:[^`]|``)+`)\\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\\(`(?:[^`]|``)+`\\))$~',$y))$Bg[$y]=$X;}}$Cg="";foreach($Bg
as$y=>$X){if(($x=="sql"||$x=="pgsql")&&strlen($X)>64){$y=(strpos($y,'(')?$y:idf_escape($y));$y="MD5(".($x=='sql'&&preg_match("~^utf8_~",$l[$y]["collation"])?$y:"CONVERT($y USING ".charset($e).")").")";$X=md5($X);}$Cg.="&".($X!==null?urlencode("where[".bracket_escape($y)."]")."=".urlencode($X):"null%5B%5D=".urlencode($y));}echo"<tr".odd().">".(!$r&&$N?"":"<td>".checkbox("check[]",substr($Cg,1),in_array(substr($Cg,1),(array)$_POST["check"]),"","this.form['all'].checked = false; formUncheck('all-page');").($Xc||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Cg)."'>".'edit'."</a>"));foreach($L
as$y=>$X){if(isset($Kd[$y])){$k=$l[$y];if($X!=""&&(!isset($Tb[$y])||$Tb[$y]!=""))$Tb[$y]=(is_mail($X)?$Kd[$y]:"");$_="";if(preg_match('~blob|bytea|raw|file~',$k["type"])&&$X!="")$_=ME.'download='.urlencode($a).'&field='.urlencode($y).$Cg;if(!$_&&$X!==null){foreach((array)$n[$y]as$m){if(count($n[$y])==1||end($m["source"])==$y){$_="";foreach($m["source"]as$s=>$Ff)$_.=where_link($s,$m["target"][$s],$M[$D][$Ff]);$_=($m["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\\1'.urlencode($m["db"]),ME):ME).'select='.urlencode($m["table"]).$_;if($m["ns"])$_=preg_replace('~([?&]ns=)[^&]+~','\\1'.urlencode($m["ns"]),$_);if(count($m["source"])==1)break;}}}if($y=="COUNT(*)"){$_=ME."select=".urlencode($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Bg))$_.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($Bg
as$bd=>$W)$_.=where_link($s++,$bd,$W);}$X=select_value($X,$_,$k,$gg);$t=h("val[$Cg][".bracket_escape($y)."]");$Y=$_POST["val"][$Cg][bracket_escape($y)];$Ob=!is_array($L[$y])&&is_utf8($X)&&$M[$D][$y]==$L[$y]&&!$zc[$y];$fg=preg_match('~text|lob~',$k["type"]);if(($_GET["modify"]&&$Ob)||$Y!==null){$Cc=h($Y!==null?$Y:$L[$y]);echo"<td>".($fg?"<textarea name='$t' cols='30' rows='".(substr_count($L[$y],"\n")+1)."'>$Cc</textarea>":"<input name='$t' value='$Cc' size='$pd[$y]'>");}else{$td=strpos($X,"<i>...</i>");echo"<td id='$t' onclick=\"selectClick(this, event, ".($td?2:($fg?1:0)).($Ob?"":", '".h('Use edit link to modify this value.')."'").");\">$X";}}}if($Ba)echo"<td>";$b->backwardKeysPrint($Ba,$M[$D]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n";}if(($M||$F)&&!is_ajax()){$dc=true;if($_GET["page"]!="last"){if(!+$z)$xc=count($M);elseif($x!="sql"||!$Xc){$xc=($Xc?false:found_rows($R,$Z));if($xc<max(1e4,2*($F+1)*$z))$xc=reset(slow_query(count_rows($a,$Z,$Xc,$r)));else$dc=false;}}if(+$z&&($xc===false||$xc>$z||$F)){echo"<p class='pages'>";$yd=($xc===false?$F+(count($M)>=$z?2:1):floor(($xc-1)/$z));if($x!="simpledb"){echo'<a href="'.h(remove_from_uri("page"))."\" onclick=\"pageClick(this.href, +prompt('".'Page'."', '".($F+1)."'), event); return false;\">".'Page'."</a>:",pagination(0,$F).($F>5?" ...":"");for($s=max(1,$F-4);$s<min($yd,$F+5);$s++)echo
pagination($s,$F);if($yd>0){echo($F+5<$yd?" ...":""),($dc&&$xc!==false?pagination($yd,$F):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$yd'>".'last'."</a>");}echo(($xc===false?count($M)+1:$xc-$F*$z)>$z?' <a href="'.h(remove_from_uri("page")."&page=".($F+1)).'" onclick="return !selectLoadMore(this, '.(+$z).', \''.'Loading'.'...\');" class="loadmore">'.'Load more data'.'</a>':'');}else{echo'Page'.":",pagination(0,$F).($F>1?" ...":""),($F?pagination($F,$F):""),($yd>$F?pagination($F+1,$F).($yd>$F+1?" ...":""):"");}}echo"<p class='count'>\n",($xc!==false?"(".($dc?"":"~ ").lang(array('%d row','%d rows'),$xc).") ":"");$Cb=($dc?"":"~ ").$xc;echo
checkbox("all",1,0,'whole result',"var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$Cb' : checked); selectCount('selected2', this.checked || !checked ? '$Cb' : checked);")."\n";if($b->selectCommandPrint()){echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete"',confirm(),'>
</div></fieldset>
';}$vc=$b->dumpFormat();foreach((array)$_GET["columns"]as$c){if($c["fun"]){unset($vc['sql']);break;}}if($vc){print_fieldset("export",'Export'." <span id='selected2'></span>");$re=$b->dumpOutput();echo($re?html_select("output",$re,$ma["output"])." ":""),html_select("format",$vc,$ma["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}echo(!$r&&$N?"":"<script type='text/javascript'>tableCheck();</script>\n");}if($b->selectImportPrint()){print_fieldset("import",'Import',!$M);echo"<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$ma["format"],1);echo" <input type='submit' name='import' value='".'Import'."'>","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($Tb,'strlen'),$d);echo"<p><input type='hidden' name='token' value='$T'></p>\n","</form>\n";}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$Jf=isset($_GET["status"]);page_header($Jf?'Status':'Variables');$Og=($Jf?show_status():show_variables());if(!$Og)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($Og
as$y=>$X){echo"<tr>","<th><code class='jush-".$x.($Jf?"status":"set")."'>".h($y)."</code>","<td>".nbsp($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Sf=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$E=>$R){json_row("Comment-$E",nbsp($R["Comment"]));if(!is_view($R)){foreach(array("Engine","Collation")as$y)json_row("$y-$E",nbsp($R[$y]));foreach($Sf+array("Auto_increment"=>0,"Rows"=>0)as$y=>$X){if($R[$y]!=""){$X=format_number($R[$y]);json_row("$y-$E",($y=="Rows"&&$X&&$R["Engine"]==($Hf=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Sf[$y]))$Sf[$y]+=($R["Engine"]!="InnoDB"||$y!="Data_free"?$R[$y]:0);}elseif(array_key_exists($y,$R))json_row("$y-$E");}}}foreach($Sf
as$y=>$X)json_row("sum-$y",format_number($X));json_row("");}elseif($_GET["script"]=="kill")$e->query("KILL ".number($_POST["kill"]));else{foreach(count_tables($b->databases())as$i=>$X){json_row("tables-$i",$X);json_row("size-$i",db_size($i));}json_row("");}exit;}else{$ag=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($ag&&!$j&&!$_POST["search"]){$J=true;$C="";if($x=="sql"&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$J=truncate_tables($_POST["tables"]);$C='Tables have been truncated.';}elseif($_POST["move"]){$J=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$C='Tables have been moved.';}elseif($_POST["copy"]){$J=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$C='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$J=drop_views($_POST["views"]);if($J&&$_POST["tables"])$J=drop_tables($_POST["tables"]);$C='Tables have been dropped.';}elseif($x!="sql"){$J=($x=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$C='Tables have been optimized.';}elseif(!$_POST["tables"])$C='No tables.';elseif($J=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"])))){while($L=$J->fetch_assoc())$C.="<b>".h($L["Table"])."</b>: ".h($L["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$C,$J);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$j,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Zf=tables_list();if(!$Zf)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'> <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!="")search_tables();}$Db=doc_link(array('sql'=>'show-table-status.html'));echo"<table cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);'>\n",'<thead><tr class="wrap"><td><input id="check-all" type="checkbox" onclick="formCheck(this, /^(tables|views)\[/);" class="jsonly">','<th>'.'Table','<td>'.'Engine'.doc_link(array('sql'=>'storage-engines.html')),'<td>'.'Collation'.doc_link(array('sql'=>'charset-mysql.html')),'<td>'.'Data Length'.$Db,'<td>'.'Index Length'.$Db,'<td>'.'Data Free'.$Db,'<td>'.'Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html')),'<td>'.'Rows'.$Db,(support("comment")?'<td>'.'Comment'.$Db:''),"</thead>\n";$S=0;foreach($Zf
as$E=>$U){$Qg=($U!==null&&!preg_match('~table~i',$U));$t=h("Table-".$E);echo'<tr'.odd().'><td>'.checkbox(($Qg?"views[]":"tables[]"),$E,in_array($E,$ag,true),"","formUncheck('check-all');","",$t),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".urlencode($E)."' title='".'Show structure'."' id='$t'>".h($E).'</a>':h($E));if($Qg){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($E).'" title="'.'Alter view'.'">'.(preg_match('~materialized~i',$U)?'Materialized view':'View').'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($E).'" title="'.'Select data'.'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$y=>$_){$t=" id='$y-".h($E)."'";echo($_?"<td align='right'>".(support("table")||$y=="Rows"||(support("indexes")&&$y!="Data_length")?"<a href='".h(ME."$_[0]=").urlencode($E)."'$t title='$_[1]'>?</a>":"<span$t>?</span>"):"<td id='$y-".h($E)."'>&nbsp;");}$S++;}echo(support("comment")?"<td id='Comment-".h($E)."'>&nbsp;":"");}echo"<tr><td>&nbsp;<th>".sprintf('%d in total',count($Zf)),"<td>".nbsp($x=="sql"?$e->result("SELECT @@storage_engine"):""),"<td>".nbsp(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$y)echo"<td align='right' id='sum-$y'>&nbsp;";echo"</table>\n";if(!information_schema(DB)){$Mg="<input type='submit' value='".'Vacuum'."'".on_help("'VACUUM'")."> ";$ee="<input type='submit' name='optimize' value='".'Optimize'."'".on_help($x=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'")."> ";echo"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>".($x=="sqlite"?$Mg:($x=="pgsql"?$Mg.$ee:($x=="sql"?"<input type='submit' value='".'Analyze'."'".on_help("'ANALYZE TABLE'")."> ".$ee."<input type='submit' name='check' value='".'Check'."'".on_help("'CHECK TABLE'")."> "."<input type='submit' name='repair' value='".'Repair'."'".on_help("'REPAIR TABLE'")."> ":"")))."<input type='submit' name='truncate' value='".'Truncate'."'".confirm().on_help($x=="sqlite"?"'DELETE'":"'TRUNCATE".($x=="pgsql"?"'":" TABLE'"))."> "."<input type='submit' name='drop' value='".'Drop'."'".confirm().on_help("'DROP TABLE'").">\n";$h=(support("scheme")?$b->schemas():$b->databases());if(count($h)!=1&&$x!="sqlite"){$i=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".'Move to other database'.": ",($h?html_select("target",$h,$i):'<input name="target" value="'.h($i).'" autocapitalize="off">')," <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'>":""),"\n";}echo"<input type='hidden' name='all' value='' onclick=\"selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $S);":"")."\">\n";echo"<input type='hidden' name='token' value='$T'>\n","</div></fieldset>\n";}echo"</form>\n","<script type='text/javascript'>tableCheck();</script>\n";}echo'<p class="links"><a href="'.h(ME).'create=">'.'Create table'."</a>\n",(support("view")?'<a href="'.h(ME).'view=">'.'Create view'."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$of=routines();if($of){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td>&nbsp;</thead>\n";odd('');foreach($of
as$L){echo'<tr'.odd().'>','<th><a href="'.h(ME).($L["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($L["ROUTINE_NAME"]).'">'.h($L["ROUTINE_NAME"]).'</a>','<td>'.h($L["ROUTINE_TYPE"]),'<td>'.h($L["DTD_IDENTIFIER"]),'<td><a href="'.h(ME).($L["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($L["ROUTINE_NAME"]).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$M=get_rows("SHOW EVENTS");if($M){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($M
as$L){echo"<tr>","<th>".h($L["Name"]),"<td>".($L["Execute at"]?'At given time'."<td>".$L["Execute at"]:'Every'." ".$L["Interval value"]." ".$L["Interval field"]."<td>$L[Starts]"),"<td>$L[Ends]",'<td><a href="'.h(ME).'event='.urlencode($L["Name"]).'">'.'Alter'.'</a>';}echo"</table>\n";$bc=$e->result("SELECT @@event_scheduler");if($bc&&$bc!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($bc)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}if($Zf)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}}}page_footer();