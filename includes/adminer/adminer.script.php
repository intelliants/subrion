<?php
/** Adminer - Compact database management
* @link http://www.adminer.org/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 4.2.1
*/error_reporting(6135);$oc=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($oc||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$yg=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($yg)$$X=$yg;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
exit;}function
connection(){global$e;return$e;}function
adminer(){global$b;return$b;}function
idf_unescape($Kc){$gd=substr($Kc,-1);return
str_replace($gd.$gd,$gd,substr($Kc,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
remove_slashes($Ne,$oc=false){if(get_magic_quotes_gpc()){while(list($y,$X)=each($Ne)){foreach($X
as$ad=>$W){unset($Ne[$y][$ad]);if(is_array($W)){$Ne[$y][stripslashes($ad)]=$W;$Ne[]=&$Ne[$y][stripslashes($ad)];}else$Ne[$y][stripslashes($ad)]=($oc?$W:stripslashes($W));}}}}function
bracket_escape($Kc,$_a=false){static$mg=array(':'=>':1',']'=>':2','['=>':3');return
strtr($Kc,($_a?array_flip($mg):$mg));}function
charset($e){return(version_compare($e->server_info,"5.5.3")>=0?"utf8mb4":"utf8");}function
h($Gf){return
str_replace("\0","&#0;",htmlspecialchars($Gf,ENT_QUOTES,'utf-8'));}function
nbsp($Gf){return(trim($Gf)!=""?h($Gf):"&nbsp;");}function
nl_br($Gf){return
str_replace("\n","<br>",$Gf);}function
checkbox($E,$Y,$Na,$ed="",$Zd="",$Ra=""){$K="<input type='checkbox' name='$E' value='".h($Y)."'".($Na?" checked":"").($Zd?' onclick="'.h($Zd).'"':'').">";return($ed!=""||$Ra?"<label".($Ra?" class='$Ra'":"").">$K".h($ed)."</label>":$K);}function
optionlist($de,$rf=null,$Eg=false){$K="";foreach($de
as$ad=>$W){$ee=array($ad=>$W);if(is_array($W)){$K.='<optgroup label="'.h($ad).'">';$ee=$W;}foreach($ee
as$y=>$X)$K.='<option'.($Eg||is_string($y)?' value="'.h($y).'"':'').(($Eg||is_string($y)?(string)$y:$X)===$rf?' selected':'').'>'.h($X);if(is_array($W))$K.='</optgroup>';}return$K;}function
html_select($E,$de,$Y="",$Yd=true){if($Yd)return"<select name='".h($E)."'".(is_string($Yd)?' onchange="'.h($Yd).'"':"").">".optionlist($de,$Y)."</select>";$K="";foreach($de
as$y=>$X)$K.="<label><input type='radio' name='".h($E)."' value='".h($y)."'".($y==$Y?" checked":"").">".h($X)."</label>";return$K;}function
select_input($wa,$de,$Y="",$Ae=""){return($de?"<select$wa><option value=''>$Ae".optionlist($de,$Y,true)."</select>":"<input$wa size='10' value='".h($Y)."' placeholder='$Ae'>");}function
confirm(){return" onclick=\"return confirm('".'Are you sure?'."');\"";}function
print_fieldset($t,$ld,$Mg=false,$Zd=""){echo"<fieldset><legend><a href='#fieldset-$t' onclick=\"".h($Zd)."return !toggle('fieldset-$t');\">$ld</a></legend><div id='fieldset-$t'".($Mg?"":" class='hidden'").">\n";}function
bold($Ga,$Ra=""){return($Ga?" class='active $Ra'":($Ra?" class='$Ra'":""));}function
odd($K=' class="odd"'){static$s=0;if(!$K)$s=-1;return($s++%2?$K:'');}function
js_escape($Gf){return
addcslashes($Gf,"\r\n'\\/");}function
json_row($y,$X=null){static$pc=true;if($pc)echo"{";if($y!=""){echo($pc?"":",")."\n\t\"".addcslashes($y,"\r\n\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'undefined');$pc=false;}else{echo"\n}\n";$pc=true;}}function
ini_bool($Oc){$X=ini_get($Oc);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$K;if($K===null)$K=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$K;}function
set_password($Jg,$O,$V,$G){$_SESSION["pwds"][$Jg][$O][$V]=($_COOKIE["adminer_key"]&&is_string($G)?array(encrypt_string($G,$_COOKIE["adminer_key"])):$G);}function
get_password(){$K=get_session("pwds");if(is_array($K))$K=($_COOKIE["adminer_key"]?decrypt_string($K[0],$_COOKIE["adminer_key"]):false);return$K;}function
q($Gf){global$e;return$e->quote($Gf);}function
get_vals($I,$c=0){global$e;$K=array();$J=$e->query($I);if(is_object($J)){while($L=$J->fetch_row())$K[]=$L[$c];}return$K;}function
get_key_vals($I,$f=null,$cg=0){global$e;if(!is_object($f))$f=$e;$K=array();$f->timeout=$cg;$J=$f->query($I);$f->timeout=0;if(is_object($J)){while($L=$J->fetch_row())$K[$L[0]]=$L[1];}return$K;}function
get_rows($I,$f=null,$k="<p class='error'>"){global$e;$eb=(is_object($f)?$f:$e);$K=array();$J=$eb->query($I);if(is_object($J)){while($L=$J->fetch_assoc())$K[]=$L;}elseif(!$J&&!is_object($f)&&$k&&defined("PAGE_HEADER"))echo$k.error()."\n";return$K;}function
unique_array($L,$v){foreach($v
as$u){if(preg_match("~PRIMARY|UNIQUE~",$u["type"])){$K=array();foreach($u["columns"]as$y){if(!isset($L[$y]))continue
2;$K[$y]=$L[$y];}return$K;}}}function
escape_key($y){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$y,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($y);}function
where($Z,$m=array()){global$e,$x;$K=array();foreach((array)$Z["where"]as$y=>$X){$y=bracket_escape($y,1);$c=escape_key($y);$K[]=$c.(($x=="sql"&&preg_match('~^[0-9]*\\.[0-9]*$~',$X))||$x=="mssql"?" LIKE ".q(addcslashes($X,"%_\\")):" = ".unconvert_field($m[$y],q($X)));if($x=="sql"&&preg_match('~char|text~',$m[$y]["type"])&&preg_match("~[^ -@]~",$X))$K[]="$c = ".q($X)." COLLATE ".charset($e)."_bin";}foreach((array)$Z["null"]as$y)$K[]=escape_key($y)." IS NULL";return
implode(" AND ",$K);}function
where_check($X,$m=array()){parse_str($X,$Ma);remove_slashes(array(&$Ma));return
where($Ma,$m);}function
where_link($s,$c,$Y,$ae="="){return"&where%5B$s%5D%5Bcol%5D=".urlencode($c)."&where%5B$s%5D%5Bop%5D=".urlencode(($Y!==null?$ae:"IS NULL"))."&where%5B$s%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($d,$m,$N=array()){$K="";foreach($d
as$y=>$X){if($N&&!in_array(idf_escape($y),$N))continue;$ua=convert_field($m[$y]);if($ua)$K.=", $ua AS ".idf_escape($y);}return$K;}function
cookie($E,$Y,$od=2592000){global$ba;$re=array($E,(preg_match("~\n~",$Y)?"":$Y),($od?time()+$od:0),preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$re[]=true;return
call_user_func_array('setcookie',$re);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session(){if(!ini_bool("session.use_cookies"))session_write_close();}function&get_session($y){return$_SESSION[$y][DRIVER][SERVER][$_GET["username"]];}function
set_session($y,$X){$_SESSION[$y][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($Jg,$O,$V,$i=null){global$Eb;preg_match('~([^?]*)\\??(.*)~',remove_from_uri(implode("|",array_keys($Eb))."|username|".($i!==null?"db|":"").session_name()),$B);return"$B[1]?".(sid()?SID."&":"").($Jg!="server"||$O!=""?urlencode($Jg)."=".urlencode($O)."&":"")."username=".urlencode($V).($i!=""?"&db=".urlencode($i):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($A,$C=null){if($C!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$C;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($I,$A,$C,$Ve=true,$cc=true,$ic=false,$bg=""){global$e,$k,$b;if($cc){$Cf=microtime(true);$ic=!$e->query($I);$bg=format_time($Cf);}$Bf="";if($I)$Bf=$b->messageQuery($I,$bg);if($ic){$k=error().$Bf;return
false;}if($Ve)redirect($A,$C.$Bf);return
true;}function
queries($I){global$e;static$Qe=array();static$Cf;if(!$Cf)$Cf=microtime(true);if($I===null)return
array(implode("\n",$Qe),format_time($Cf));$Qe[]=(preg_match('~;$~',$I)?"DELIMITER ;;\n$I;\nDELIMITER ":$I).";";return$e->query($I);}function
apply_queries($I,$S,$Yb='table'){foreach($S
as$Q){if(!queries("$I ".$Yb($Q)))return
false;}return
true;}function
queries_redirect($A,$C,$Ve){list($Qe,$bg)=queries(null);return
query_redirect($Qe,$A,$C,$Ve,false,!$Ve,$bg);}function
format_time($Cf){return
sprintf('%.3f s',max(0,microtime(true)-$Cf));}function
remove_from_uri($qe=""){return
substr(preg_replace("~(?<=[?&])($qe".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($F,$nb){return" ".($F==$nb?$F+1:'<a href="'.h(remove_from_uri("page").($F?"&page=$F".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($F+1)."</a>");}function
get_file($y,$ub=false){$mc=$_FILES[$y];if(!$mc)return
null;foreach($mc
as$y=>$X)$mc[$y]=(array)$X;$K='';foreach($mc["error"]as$y=>$k){if($k)return$k;$E=$mc["name"][$y];$jg=$mc["tmp_name"][$y];$fb=file_get_contents($ub&&preg_match('~\\.gz$~',$E)?"compress.zlib://$jg":$jg);if($ub){$Cf=substr($fb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Cf,$bf))$fb=iconv("utf-16","utf-8",$fb);elseif($Cf=="\xEF\xBB\xBF")$fb=substr($fb,3);$K.=$fb."\n\n";}else$K.=$fb;}return$K;}function
upload_error($k){$yd=($k==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($k?'Unable to upload a file.'.($yd?" ".sprintf('Maximum allowed file size is %sB.',$yd):""):'File does not exist.');}function
repeat_pattern($ze,$md){return
str_repeat("$ze{0,65535}",$md/65535)."$ze{0,".($md%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\\0-\\x8\\xB\\xC\\xE-\\x1F]~',$X));}function
shorten_utf8($Gf,$md=80,$Kf=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{FFFF}]",$md).")($)?)u",$Gf,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$md).")($)?)",$Gf,$B);return
h($B[1]).$Kf.(isset($B[2])?"":"<i>...</i>");}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($Ne,$Lc=array()){while(list($y,$X)=each($Ne)){if(!in_array($y,$Lc)){if(is_array($X)){foreach($X
as$ad=>$W)$Ne[$y."[$ad]"]=$W;}else
echo'<input type="hidden" name="'.h($y).'" value="'.h($X).'">';}}}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$jc=false){$K=table_status($Q,$jc);return($K?$K:array("Name"=>$Q));}function
column_foreign_keys($Q){global$b;$K=array();foreach($b->foreignKeys($Q)as$n){foreach($n["source"]as$X)$K[$X][]=$n;}return$K;}function
enum_input($U,$wa,$l,$Y,$Sb=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$l["length"],$td);$K=($Sb!==null?"<label><input type='$U'$wa value='$Sb'".((is_array($Y)?in_array($Sb,$Y):$Y===0)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($td[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$Na=(is_int($Y)?$Y==$s+1:(is_array($Y)?in_array($s+1,$Y):$Y===$X));$K.=" <label><input type='$U'$wa value='".($s+1)."'".($Na?' checked':'').'>'.h($b->editVal($X,$l)).'</label>';}return$K;}function
input($l,$Y,$p){global$e,$tg,$b,$x;$E=h(bracket_escape($l["field"]));echo"<td class='function'>";if(is_array($Y)&&!$p){$ta=array($Y);if(version_compare(PHP_VERSION,5.4)>=0)$ta[]=JSON_PRETTY_PRINT;$Y=call_user_func_array('json_encode',$ta);$p="json";}$df=($x=="mssql"&&$l["auto_increment"]);if($df&&!$_POST["save"])$p=null;$yc=(isset($_GET["select"])||$df?array("orig"=>'original'):array())+$b->editFunctions($l);$wa=" name='fields[$E]'";if($l["type"]=="enum")echo
nbsp($yc[""])."<td>".$b->editInput($_GET["edit"],$l,$wa,$Y);else{$pc=0;foreach($yc
as$y=>$X){if($y===""||!$X)break;$pc++;}$Yd=($pc?" onchange=\"var f = this.form['function[".h(js_escape(bracket_escape($l["field"])))."]']; if ($pc > f.selectedIndex) f.selectedIndex = $pc;\" onkeyup='keyupChange.call(this);'":"");$wa.=$Yd;$Dc=(in_array($p,$yc)||isset($yc[$p]));echo(count($yc)>1?"<select name='function[$E]' onchange='functionChange(this);'".on_help("getTarget(event).value.replace(/^SQL\$/, '')",1).">".optionlist($yc,$p===null||$Dc?$p:"")."</select>":nbsp(reset($yc))).'<td>';$Qc=$b->editInput($_GET["edit"],$l,$wa,$Y);if($Qc!="")echo$Qc;elseif($l["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$l["length"],$td);foreach($td[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$Na=(is_int($Y)?($Y>>$s)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$E][$s]' value='".(1<<$s)."'".($Na?' checked':'')."$Yd>".h($b->editVal($X,$l)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$l["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$E'$Yd>";elseif(($Zf=preg_match('~text|lob~',$l["type"]))||preg_match("~\n~",$Y)){if($Zf&&$x!="sqlite")$wa.=" cols='50' rows='12'";else{$M=min(12,substr_count($Y,"\n")+1);$wa.=" cols='30' rows='$M'".($M==1?" style='height: 1.2em;'":"");}echo"<textarea$wa>".h($Y).'</textarea>';}elseif($p=="json")echo"<textarea$wa cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';else{$_d=(!preg_match('~int~',$l["type"])&&preg_match('~^(\\d+)(,(\\d+))?$~',$l["length"],$B)?((preg_match("~binary~",$l["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$l["unsigned"]?1:0)):($tg[$l["type"]]?$tg[$l["type"]]+($l["unsigned"]?0:1):0));if($x=='sql'&&$e->server_info>=5.6&&preg_match('~time~',$l["type"]))$_d+=7;echo"<input".((!$Dc||$p==="")&&preg_match('~(?<!o)int~',$l["type"])?" type='number'":"")." value='".h($Y)."'".($_d?" maxlength='$_d'":"").(preg_match('~char|binary~',$l["type"])&&$_d>20?" size='40'":"")."$wa>";}}}function
process_input($l){global$b;$Kc=bracket_escape($l["field"]);$p=$_POST["function"][$Kc];$Y=$_POST["fields"][$Kc];if($l["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($l["auto_increment"]&&$Y=="")return
null;if($p=="orig")return($l["on_update"]=="CURRENT_TIMESTAMP"?idf_escape($l["field"]):false);if($p=="NULL")return"NULL";if($l["type"]=="set")return
array_sum((array)$Y);if($p=="json"){$p="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$l["type"])&&ini_bool("file_uploads")){$mc=get_file("fields-$Kc");if(!is_string($mc))return
false;return
q($mc);}return$b->processInput($l,$Y,$p);}function
fields_from_edit(){global$j;$K=array();foreach((array)$_POST["field_keys"]as$y=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$y];$_POST["fields"][$X]=$_POST["field_vals"][$y];}}foreach((array)$_POST["fields"]as$y=>$X){$E=bracket_escape($y,1);$K[$E]=array("field"=>$E,"privileges"=>array("insert"=>1,"update"=>1),"null"=>1,"auto_increment"=>($y==$j->primary),);}return$K;}function
search_tables(){global$b,$e;$_GET["where"][0]["op"]="LIKE %%";$_GET["where"][0]["val"]=$_POST["query"];$vc=false;foreach(table_status('',true)as$Q=>$R){$E=$b->tableName($R);if(isset($R["Engine"])&&$E!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$J=$e->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($Q),array())),1));if(!$J||$J->fetch_row()){if(!$vc){echo"<ul>\n";$vc=true;}echo"<li>".($J?"<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$E</a>\n":"$E: <span class='error'>".error()."</span>\n");}}}echo($vc?"</ul>":"<p class='message'>".'No tables.')."\n";}function
dump_headers($Jc,$Gd=false){global$b;$K=$b->dumpHeaders($Jc,$Gd);$oe=$_POST["output"];if($oe!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($Jc).".$K".($oe!="file"&&!preg_match('~[^0-9a-z]~',$oe)?".$oe":""));session_write_close();ob_flush();flush();return$K;}function
dump_csv($L){foreach($L
as$y=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$L[$y]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$L)."\r\n";}function
apply_sql_function($p,$c){return($p?($p=="unixepoch"?"DATETIME($c, '$p')":($p=="count distinct"?"COUNT(DISTINCT ":strtoupper("$p("))."$c)"):$c);}function
get_temp_dir(){$K=ini_get("upload_tmp_dir");if(!$K){if(function_exists('sys_get_temp_dir'))$K=sys_get_temp_dir();else{$nc=@tempnam("","");if(!$nc)return
false;$K=dirname($nc);unlink($nc);}}return$K;}function
password_file($g){$nc=get_temp_dir()."/adminer.key";$K=@file_get_contents($nc);if($K||!$g)return$K;$o=@fopen($nc,"w");if($o){chmod($nc,0660);$K=rand_string();fwrite($o,$K);fclose($o);}return$K;}function
rand_string(){return
md5(uniqid(mt_rand(),true));}function
select_value($X,$_,$l,$ag){global$b,$ba;if(is_array($X)){$K="";foreach($X
as$ad=>$W)$K.="<tr>".($X!=array_values($X)?"<th>".h($ad):"")."<td>".select_value($W,$_,$l,$ag);return"<table cellspacing='0'>$K</table>";}if(!$_)$_=$b->selectLink($X,$l);if($_===null){if(is_mail($X))$_="mailto:$X";if($Pe=is_url($X))$_=(($Pe=="http"&&$ba)||preg_match('~WebKit~i',$_SERVER["HTTP_USER_AGENT"])?$X:"$Pe://www.adminer.org/redirect/?url=".urlencode($X));}$K=$b->editVal($X,$l);if($K!==null){if($K==="")$K="&nbsp;";elseif(!is_utf8($K))$K="\0";elseif($ag!=""&&is_shortable($l))$K=shorten_utf8($K,max(0,+$ag));else$K=h($K);}return$b->selectVal($K,$_,$l,$X);}function
is_mail($Pb){$va='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$Db='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$ze="$va+(\\.$va+)*@($Db?\\.)+$Db";return
is_string($Pb)&&preg_match("(^$ze(,\\s*$ze)*\$)i",$Pb);}function
is_url($Gf){$Db='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return(preg_match("~^(https?)://($Db?\\.)+$Db(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$Gf,$B)?strtolower($B[1]):"");}function
is_shortable($l){return
preg_match('~char|text|lob|geometry|point|linestring|polygon|string~',$l["type"]);}function
count_rows($Q,$Z,$Wc,$r){global$x;$I=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($Wc&&($x=="sql"||count($r)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$r).")$I":"SELECT COUNT(*)".($Wc?" FROM (SELECT 1$I$_c) x":$I));}function
slow_query($I){global$b,$T;$i=$b->database();$cg=$b->queryTimeout();if(support("kill")&&is_object($f=connect())&&($i==""||$f->select_db($i))){$cd=$f->result("SELECT CONNECTION_ID()");echo'<script type="text/javascript">
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'token=',$T,'&kill=',$cd,'\');
}, ',1000*$cg,');
</script>
';}else$f=null;ob_flush();flush();$K=@get_key_vals($I,$f,$cg);if($f){echo"<script type='text/javascript'>clearTimeout(timeout);</script>\n";ob_flush();flush();}return
array_keys($K);}function
get_token(){$Te=rand(1,1e6);return($Te^$_SESSION["token"]).":$Te";}function
verify_token(){list($T,$Te)=explode(":",$_POST["token"]);return($Te^$_SESSION["token"])==$T;}function
lzw_decompress($Da){$_b=256;$Ea=8;$Ta=array();$ef=0;$ff=0;for($s=0;$s<strlen($Da);$s++){$ef=($ef<<8)+ord($Da[$s]);$ff+=8;if($ff>=$Ea){$ff-=$Ea;$Ta[]=$ef>>$ff;$ef&=(1<<$ff)-1;$_b++;if($_b>>$Ea)$Ea++;}}$zb=range("\0","\xFF");$K="";foreach($Ta
as$s=>$Sa){$Ob=$zb[$Sa];if(!isset($Ob))$Ob=$Qg.$Qg[0];$K.=$Ob;if($s)$zb[]=$Qg.$Ob[0];$Qg=$Ob;}return$K;}function
on_help($Za,$yf=0){return" onmouseover='helpMouseover(this, event, ".h($Za).", $yf);' onmouseout='helpMouseout(this, event);'";}function
edit_form($a,$m,$L,$Ag){global$b,$x,$T,$k;$Pf=$b->tableName(table_status1($a,true));page_header(($Ag?'Edit':'Insert'),$k,array("select"=>array($a,$Pf)),$Pf);if($L===false)echo"<p class='error'>".'No rows.'."\n";echo'<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$m)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table cellspacing='0' onkeydown='return editingKeydown(event);'>\n";foreach($m
as$E=>$l){echo"<tr><th>".$b->fieldName($l);$vb=$_GET["set"][bracket_escape($E)];if($vb===null){$vb=$l["default"];if($l["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$vb,$bf))$vb=$bf[1];}$Y=($L!==null?($L[$E]!=""&&$x=="sql"&&preg_match("~enum|set~",$l["type"])?(is_array($L[$E])?array_sum($L[$E]):+$L[$E]):$L[$E]):(!$Ag&&$l["auto_increment"]?"":(isset($_GET["select"])?false:$vb)));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$l);$p=($_POST["save"]?(string)$_POST["function"][$E]:($Ag&&$l["on_update"]=="CURRENT_TIMESTAMP"?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(preg_match("~time~",$l["type"])&&$Y=="CURRENT_TIMESTAMP"){$Y="";$p="now";}input($l,$Y,$p);echo"\n";}if(!support("table"))echo"<tr>"."<th><input name='field_keys[]' onkeyup='keyupChange.call(this);' onchange='fieldChange(this);' value=''>"."<td class='function'>".html_select("field_funs[]",$b->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($m){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Ag?'Save and continue edit'."' onclick='return !ajaxForm(this.form, \"".'Saving'.'...", this)':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n";}echo($Ag?"<input type='submit' name='delete' value='".'Delete'."'".confirm().">\n":($_POST||!$m?"":"<script type='text/javascript'>focus(document.getElementById('form').getElementsByTagName('td')[1].firstChild);</script>\n"));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$T,'">
</form>
';}global$b,$e,$Eb,$Lb,$Vb,$k,$yc,$Ac,$ba,$Pc,$x,$ca,$fd,$Xd,$_e,$Hf,$Ec,$T,$og,$tg,$_g,$ga;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";$ba=$_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off");@ini_set("session.use_trans_sid",false);session_cache_limiter("");if(!defined("SID")){session_name("adminer_sid");$re=array(0,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$re[]=true;call_user_func_array('session_set_cookie_params',$re);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$oc);if(get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",20);function
get_lang(){return'en';}function
lang($ng,$Od=null){if(is_array($ng)){$Ce=($Od==1?0:1);$ng=$ng[$Ce];}$ng=str_replace("%d","%s",$ng);$Od=format_number($Od);return
sprintf($ng,$Od);}if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$errno,$error;function
__construct(){global$b;$Ce=array_search("SQL",$b->operators);if($Ce!==false)unset($b->operators[$Ce]);}function
dsn($Ib,$V,$G){try{parent::__construct($Ib,$V,$G);}catch(Exception$ac){auth_error($ac->getMessage());}$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=$this->getAttribute(4);}function
query($I,$ug=false){$J=parent::query($I);$this->error="";if(!$J){list(,$this->errno,$this->error)=$this->errorInfo();return
false;}$this->store_result($J);return$J;}function
multi_query($I){return$this->_result=$this->query($I);}function
store_result($J=null){if(!$J){$J=$this->_result;if(!$J)return
false;}if($J->columnCount()){$J->num_rows=$J->rowCount();return$J;}$this->affected_rows=$J->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($I,$l=0){$J=$this->query($I);if(!$J)return
false;$L=$J->fetch();return$L[$l];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$L=(object)$this->getColumnMeta($this->_offset++);$L->orgtable=$L->table;$L->orgname=$L->name;$L->charsetnr=(in_array("blob",(array)$L->flags)?63:0);return$L;}}}$Eb=array();class
Min_SQL{var$_conn;function
Min_SQL($e){$this->_conn=$e;}function
select($Q,$N,$Z,$r,$fe=array(),$z=1,$F=0,$Je=false){global$b,$x;$Wc=(count($r)<count($N));$I=$b->selectQueryBuild($N,$Z,$r,$fe,$z,$F);if(!$I)$I="SELECT".limit(($_GET["page"]!="last"&&+$z&&$r&&$Wc&&$x=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$N)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($r&&$Wc?"\nGROUP BY ".implode(", ",$r):"").($fe?"\nORDER BY ".implode(", ",$fe):""),($z!=""?+$z:null),($F?$z*$F:0),"\n");$Cf=microtime(true);$K=$this->_conn->query($I);if($Je)echo$b->selectQuery($I,format_time($Cf));return$K;}function
delete($Q,$Re,$z=0){$I="FROM ".table($Q);return
queries("DELETE".($z?limit1($I,$Re):" $I$Re"));}function
update($Q,$P,$Re,$z=0,$tf="\n"){$Hg=array();foreach($P
as$y=>$X)$Hg[]="$y = $X";$I=table($Q)." SET$tf".implode(",$tf",$Hg);return
queries("UPDATE".($z?limit1($I,$Re):" $I$Re"));}function
insert($Q,$P){return
queries("INSERT INTO ".table($Q).($P?" (".implode(", ",array_keys($P)).")\nVALUES (".implode(", ",$P).")":" DEFAULT VALUES"));}function
insertUpdate($Q,$M,$Ie){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}}$Eb=array("server"=>"MySQL")+$Eb;if(!defined("DRIVER")){$Fe=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
Min_DB(){parent::init();}function
connect($O,$V,$G){mysqli_report(MYSQLI_REPORT_OFF);list($Hc,$Be)=explode(":",$O,2);$K=@$this->real_connect(($O!=""?$Hc:ini_get("mysqli.default_host")),($O.$V!=""?$V:ini_get("mysqli.default_user")),($O.$V.$G!=""?$G:ini_get("mysqli.default_pw")),null,(is_numeric($Be)?$Be:ini_get("mysqli.default_port")),(!is_numeric($Be)?$Be:null));return$K;}function
set_charset($La){if(parent::set_charset($La))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $La");}function
result($I,$l=0){$J=$this->query($I);if(!$J)return
false;$L=$J->fetch_array();return$L[$l];}function
quote($Gf){return"'".$this->escape_string($Gf)."'";}}}elseif(extension_loaded("mysql")&&!(ini_get("sql.safe_mode")&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($O,$V,$G){$this->_link=@mysql_connect(($O!=""?$O:ini_get("mysql.default_host")),("$O$V"!=""?$V:ini_get("mysql.default_user")),("$O$V$G"!=""?$G:ini_get("mysql.default_password")),true,131072);if($this->_link)$this->server_info=mysql_get_server_info($this->_link);else$this->error=mysql_error();return(bool)$this->_link;}function
set_charset($La){if(function_exists('mysql_set_charset')){if(mysql_set_charset($La,$this->_link))return
true;mysql_set_charset('utf8',$this->_link);}return$this->query("SET NAMES $La");}function
quote($Gf){return"'".mysql_real_escape_string($Gf,$this->_link)."'";}function
select_db($qb){return
mysql_select_db($qb,$this->_link);}function
query($I,$ug=false){$J=@($ug?mysql_unbuffered_query($I,$this->_link):mysql_query($I,$this->_link));$this->error="";if(!$J){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($J===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($J);}function
multi_query($I){return$this->_result=$this->query($I);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($I,$l=0){$J=$this->query($I);if(!$J||!$J->num_rows)return
false;return
mysql_result($J->_result,0,$l);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
Min_Result($J){$this->_result=$J;$this->num_rows=mysql_num_rows($J);}function
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
select_db($qb){return$this->query("USE ".idf_escape($qb));}function
query($I,$ug=false){$this->setAttribute(1000,!$ug);return
parent::query($I,$ug);}}}class
Min_Driver
extends
Min_SQL{function
insert($Q,$P){return($P?parent::insert($Q,$P):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,$M,$Ie){$d=array_keys(reset($M));$Ge="INSERT INTO ".table($Q)." (".implode(", ",$d).") VALUES\n";$Hg=array();foreach($d
as$y)$Hg[$y]="$y = VALUES($y)";$Kf="\nON DUPLICATE KEY UPDATE ".implode(", ",$Hg);$Hg=array();$md=0;foreach($M
as$P){$Y="(".implode(", ",$P).")";if($Hg&&(strlen($Ge)+$md+strlen($Y)+strlen($Kf)>1e6)){if(!queries($Ge.implode(",\n",$Hg).$Kf))return
false;$Hg=array();$md=0;}$Hg[]=$Y;$md+=strlen($Y)+2;}return
queries($Ge.implode(",\n",$Hg).$Kf);}}function
idf_escape($Kc){return"`".str_replace("`","``",$Kc)."`";}function
table($Kc){return
idf_escape($Kc);}function
connect(){global$b;$e=new
Min_DB;$mb=$b->credentials();if($e->connect($mb[0],$mb[1],$mb[2])){$e->set_charset(charset($e));$e->query("SET sql_quote_show_create = 1, autocommit = 1");return$e;}$K=$e->error;if(function_exists('iconv')&&!is_utf8($K)&&strlen($mf=iconv("windows-1250","utf-8",$K))>strlen($K))$K=$mf;return$K;}function
get_databases($qc){global$e;$K=get_session("dbs");if($K===null){$I=($e->server_info>=5?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA":"SHOW DATABASES");$K=($qc?slow_query($I):get_vals($I));restart_session();set_session("dbs",$K);stop_session();}return$K;}function
limit($I,$Z,$z,$Qd=0,$tf=" "){return" $I$Z".($z!==null?$tf."LIMIT $z".($Qd?" OFFSET $Qd":""):"");}function
limit1($I,$Z){return
limit($I,$Z,1);}function
db_collation($i,$Xa){global$e;$K=null;$g=$e->result("SHOW CREATE DATABASE ".idf_escape($i),1);if(preg_match('~ COLLATE ([^ ]+)~',$g,$B))$K=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$g,$B))$K=$Xa[$B[1]][-1];return$K;}function
engines(){$K=array();foreach(get_rows("SHOW ENGINES")as$L){if(preg_match("~YES|DEFAULT~",$L["Support"]))$K[]=$L["Engine"];}return$K;}function
logged_user(){global$e;return$e->result("SELECT USER()");}function
tables_list(){global$e;return
get_key_vals($e->server_info>=5?"SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME":"SHOW TABLES");}function
count_tables($h){$K=array();foreach($h
as$i)$K[$i]=count(get_vals("SHOW TABLES IN ".idf_escape($i)));return$K;}function
table_status($E="",$jc=false){global$e;$K=array();foreach(get_rows($jc&&$e->server_info>=5?"SELECT TABLE_NAME AS Name, Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($E!=""?"AND TABLE_NAME = ".q($E):"ORDER BY Name"):"SHOW TABLE STATUS".($E!=""?" LIKE ".q(addcslashes($E,"%_\\")):""))as$L){if($L["Engine"]=="InnoDB")$L["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$L["Comment"]);if(!isset($L["Engine"]))$L["Comment"]="";if($E!="")return$L;$K[$L["Name"]]=$L;}return$K;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){global$e;return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"])||(preg_match('~NDB~i',$R["Engine"])&&version_compare($e->server_info,'5.6')>=0);}function
fields($Q){$K=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$L){preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~',$L["Type"],$B);$K[$L["Field"]]=array("field"=>$L["Field"],"full_type"=>$L["Type"],"type"=>$B[1],"length"=>$B[2],"unsigned"=>ltrim($B[3].$B[4]),"default"=>($L["Default"]!=""||preg_match("~char|set~",$B[1])?$L["Default"]:null),"null"=>($L["Null"]=="YES"),"auto_increment"=>($L["Extra"]=="auto_increment"),"on_update"=>(preg_match('~^on update (.+)~i',$L["Extra"],$B)?$B[1]:""),"collation"=>$L["Collation"],"privileges"=>array_flip(preg_split('~, *~',$L["Privileges"])),"comment"=>$L["Comment"],"primary"=>($L["Key"]=="PRI"),);}return$K;}function
indexes($Q,$f=null){$K=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$f)as$L){$K[$L["Key_name"]]["type"]=($L["Key_name"]=="PRIMARY"?"PRIMARY":($L["Index_type"]=="FULLTEXT"?"FULLTEXT":($L["Non_unique"]?"INDEX":"UNIQUE")));$K[$L["Key_name"]]["columns"][]=$L["Column_name"];$K[$L["Key_name"]]["lengths"][]=$L["Sub_part"];$K[$L["Key_name"]]["descs"][]=null;}return$K;}function
foreign_keys($Q){global$e,$Xd;static$ze='`(?:[^`]|``)+`';$K=array();$kb=$e->result("SHOW CREATE TABLE ".table($Q),1);if($kb){preg_match_all("~CONSTRAINT ($ze) FOREIGN KEY ?\\(((?:$ze,? ?)+)\\) REFERENCES ($ze)(?:\\.($ze))? \\(((?:$ze,? ?)+)\\)(?: ON DELETE ($Xd))?(?: ON UPDATE ($Xd))?~",$kb,$td,PREG_SET_ORDER);foreach($td
as$B){preg_match_all("~$ze~",$B[2],$_f);preg_match_all("~$ze~",$B[5],$Vf);$K[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('idf_unescape',$_f[0]),"target"=>array_map('idf_unescape',$Vf[0]),"on_delete"=>($B[6]?$B[6]:"RESTRICT"),"on_update"=>($B[7]?$B[7]:"RESTRICT"),);}}return$K;}function
view($E){global$e;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\\s+AS\\s+~isU','',$e->result("SHOW CREATE VIEW ".table($E),1)));}function
collations(){$K=array();foreach(get_rows("SHOW COLLATION")as$L){if($L["Default"])$K[$L["Charset"]][-1]=$L["Collation"];else$K[$L["Charset"]][]=$L["Collation"];}ksort($K);foreach($K
as$y=>$X)asort($K[$y]);return$K;}function
information_schema($i){global$e;return($e->server_info>=5&&$i=="information_schema")||($e->server_info>=5.5&&$i=="performance_schema");}function
error(){global$e;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$e->error));}function
error_line(){global$e;if(preg_match('~ at line ([0-9]+)$~',$e->error,$bf))return$bf[1]-1;}function
create_database($i,$Wa){return
queries("CREATE DATABASE ".idf_escape($i).($Wa?" COLLATE ".q($Wa):""));}function
drop_databases($h){$K=apply_queries("DROP DATABASE",$h,'idf_escape');restart_session();set_session("dbs",null);return$K;}function
rename_database($E,$Wa){$K=false;if(create_database($E,$Wa)){$cf=array();foreach(tables_list()as$Q=>$U)$cf[]=table($Q)." TO ".idf_escape($E).".".table($Q);$K=(!$cf||queries("RENAME TABLE ".implode(", ",$cf)));if($K)queries("DROP DATABASE ".idf_escape(DB));restart_session();set_session("dbs",null);}return$K;}function
auto_increment(){$za=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$u){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$u["columns"],true)){$za="";break;}if($u["type"]=="PRIMARY")$za=" UNIQUE";}}return" AUTO_INCREMENT$za";}function
alter_table($Q,$E,$m,$rc,$bb,$Tb,$Wa,$ya,$ve){$sa=array();foreach($m
as$l)$sa[]=($l[1]?($Q!=""?($l[0]!=""?"CHANGE ".idf_escape($l[0]):"ADD"):" ")." ".implode($l[1]).($Q!=""?$l[2]:""):"DROP ".idf_escape($l[0]));$sa=array_merge($sa,$rc);$Df=($bb!==null?" COMMENT=".q($bb):"").($Tb?" ENGINE=".q($Tb):"").($Wa?" COLLATE ".q($Wa):"").($ya!=""?" AUTO_INCREMENT=$ya":"");if($Q=="")return
queries("CREATE TABLE ".table($E)." (\n".implode(",\n",$sa)."\n)$Df$ve");if($Q!=$E)$sa[]="RENAME TO ".table($E);if($Df)$sa[]=ltrim($Df);return($sa||$ve?queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$sa).$ve):true);}function
alter_indexes($Q,$sa){foreach($sa
as$y=>$X)$sa[$y]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$sa));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Lg){return
queries("DROP VIEW ".implode(", ",array_map('table',$Lg)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Lg,$Vf){$cf=array();foreach(array_merge($S,$Lg)as$Q)$cf[]=table($Q)." TO ".idf_escape($Vf).".".table($Q);return
queries("RENAME TABLE ".implode(", ",$cf));}function
copy_tables($S,$Lg,$Vf){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$E=($Vf==DB?table("copy_$Q"):idf_escape($Vf).".".table($Q));if(!queries("\nDROP TABLE IF EXISTS $E")||!queries("CREATE TABLE $E LIKE ".table($Q))||!queries("INSERT INTO $E SELECT * FROM ".table($Q)))return
false;}foreach($Lg
as$Q){$E=($Vf==DB?table("copy_$Q"):idf_escape($Vf).".".table($Q));$Kg=view($Q);if(!queries("DROP VIEW IF EXISTS $E")||!queries("CREATE VIEW $E AS $Kg[select]"))return
false;}return
true;}function
trigger($E){if($E=="")return
array();$M=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($E));return
reset($M);}function
triggers($Q){$K=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$L)$K[$L["Trigger"]]=array($L["Timing"],$L["Event"]);return$K;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($E,$U){global$e,$Vb,$Pc,$tg;$qa=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$sg="((".implode("|",array_merge(array_keys($tg),$qa)).")\\b(?:\\s*\\(((?:[^'\")]|$Vb)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";$ze="\\s*(".($U=="FUNCTION"?"":$Pc).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$sg";$g=$e->result("SHOW CREATE $U ".idf_escape($E),2);preg_match("~\\(((?:$ze\\s*,?)*)\\)\\s*".($U=="FUNCTION"?"RETURNS\\s+$sg\\s+":"")."(.*)~is",$g,$B);$m=array();preg_match_all("~$ze\\s*,?~is",$B[1],$td,PREG_SET_ORDER);foreach($td
as$qe){$E=str_replace("``","`",$qe[2]).$qe[3];$m[]=array("field"=>$E,"type"=>strtolower($qe[5]),"length"=>preg_replace_callback("~$Vb~s",'normalize_enum',$qe[6]),"unsigned"=>strtolower(preg_replace('~\\s+~',' ',trim("$qe[8] $qe[7]"))),"null"=>1,"full_type"=>$qe[4],"inout"=>strtoupper($qe[1]),"collation"=>strtolower($qe[9]),);}if($U!="FUNCTION")return
array("fields"=>$m,"definition"=>$B[11]);return
array("fields"=>$m,"returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",);}function
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
set_schema($of){return
true;}function
create_sql($Q,$ya){global$e;$K=$e->result("SHOW CREATE TABLE ".table($Q),1);if(!$ya)$K=preg_replace('~ AUTO_INCREMENT=\\d+~','',$K);return$K;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($qb){return"USE ".idf_escape($qb);}function
trigger_sql($Q,$If){$K="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$L)$K.="\n".($If=='CREATE+ALTER'?"DROP TRIGGER IF EXISTS ".idf_escape($L["Trigger"]).";;\n":"")."CREATE TRIGGER ".idf_escape($L["Trigger"])." $L[Timing] $L[Event] ON ".table($L["Table"])." FOR EACH ROW\n$L[Statement];;\n";return$K;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
convert_field($l){if(preg_match("~binary~",$l["type"]))return"HEX(".idf_escape($l["field"]).")";if($l["type"]=="bit")return"BIN(".idf_escape($l["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$l["type"]))return"AsWKT(".idf_escape($l["field"]).")";}function
unconvert_field($l,$K){if(preg_match("~binary~",$l["type"]))$K="UNHEX($K)";if($l["type"]=="bit")$K="CONV($K, 2, 10) + 0";if(preg_match("~geometry|point|linestring|polygon~",$l["type"]))$K="GeomFromText($K)";return$K;}function
support($kc){global$e;return!preg_match("~scheme|sequence|type|view_trigger".($e->server_info<5.1?"|event|partitioning".($e->server_info<5?"|routine|trigger|view":""):"")."~",$kc);}$x="sql";$tg=array();$Hf=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$y=>$X){$tg+=$X;$Hf[$y]=array_keys($X);}$_g=array("unsigned","zerofill","unsigned zerofill");$be=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$yc=array("char_length","date","from_unixtime","lower","round","sec_to_time","time_to_sec","upper");$Ac=array("avg","count","count distinct","group_concat","max","min","sum");$Lb=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array("(^|[^o])int|float|double|decimal"=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ga="4.2.1";class
Adminer{var$operators;function
name(){return"<a href='http://www.adminer.org/' target='_blank' id='h1'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
permanentLogin($g=false){return
password_file($g);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
database(){return
DB;}function
databases($qc=true){return
get_databases($qc);}function
schemas(){return
schemas();}function
queryTimeout(){return
5;}function
headers(){return
true;}function
head(){return
true;}function
loginForm(){global$Eb;echo'<table cellspacing="0">
<tr><th>System<td>',html_select("auth[driver]",$Eb,DRIVER,"loginDriver(this);"),'<tr><th>Server<td><input name="auth[server]" value="',h(SERVER),'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">
<tr><th>Username<td><input name="auth[username]" id="username" value="',h($_GET["username"]),'" autocapitalize="off">
<tr><th>Password<td><input type="password" name="auth[password]">
<tr><th>Database<td><input name="auth[db]" value="',h($_GET["db"]);?>" autocapitalize="off">
</table>
<script type="text/javascript">
var username = document.getElementById('username');
focus(username);
username.form['auth[driver]'].onchange();
</script>
<?php

echo"<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
login($rd,$G){return
true;}function
tableName($Of){return
h($Of["Name"]);}function
fieldName($l,$fe=0){return'<span title="'.h($l["full_type"]).'">'.h($l["field"]).'</span>';}function
selectLinks($Of,$P=""){echo'<p class="links">';$qd=array("select"=>'Select data');if(support("table")||support("indexes"))$qd["table"]='Show structure';if(support("table")){if(is_view($Of))$qd["view"]='Alter view';else$qd["create"]='Alter table';}if($P!==null)$qd["edit"]='New item';foreach($qd
as$y=>$X)echo" <a href='".h(ME)."$y=".urlencode($Of["Name"]).($y=="edit"?$P:"")."'".bold(isset($_GET[$y])).">$X</a>";echo"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Nf){return
array();}function
backwardKeysPrint($Aa,$L){}function
selectQuery($I,$bg){global$x;return"<p><code class='jush-$x'>".h(str_replace("\n"," ",$I))."</code> <span class='time'>($bg)</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($I)."'>".'Edit'."</a>":"")."</p>";}function
rowDescription($Q){return"";}function
rowDescriptions($M,$sc){return$M;}function
selectLink($X,$l){}function
selectVal($X,$_,$l,$me){$K=($X===null?"<i>NULL</i>":(preg_match("~char|binary~",$l["type"])&&!preg_match("~var~",$l["type"])?"<code>$X</code>":$X));if(preg_match('~blob|bytea|raw|file~',$l["type"])&&!is_utf8($X))$K=lang(array('%d byte','%d bytes'),strlen($me));return($_?"<a href='".h($_)."'".(is_url($_)?" rel='noreferrer'":"").">$K</a>":$K);}function
editVal($X,$l){return$X;}function
selectColumnsPrint($N,$d){global$yc,$Ac;print_fieldset("select",'Select',$N);$s=0;$N[""]=array();foreach($N
as$y=>$X){$X=$_GET["columns"][$y];$c=select_input(" name='columns[$s][col]' onchange='".($y!==""?"selectFieldChange(this.form)":"selectAddRow(this)").";'",$d,$X["col"]);echo"<div>".($yc||$Ac?"<select name='columns[$s][fun]' onchange='helpClose();".($y!==""?"":" this.nextSibling.nextSibling.onchange();")."'".on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'",1).">".optionlist(array(-1=>"")+array_filter(array('Functions'=>$yc,'Aggregation'=>$Ac)),$X["fun"])."</select>"."($c)":$c)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint($Z,$d,$v){print_fieldset("search",'Search',$Z);foreach($v
as$s=>$u){if($u["type"]=="FULLTEXT"){echo"(<i>".implode("</i>, <i>",array_map('h',$u["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$s]' value='".h($_GET["fulltext"][$s])."' onchange='selectFieldChange(this.form);'>",checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"),"<br>\n";}}$_GET["where"]=(array)$_GET["where"];reset($_GET["where"]);$Ka="this.nextSibling.onchange();";for($s=0;$s<=count($_GET["where"]);$s++){list(,$X)=each($_GET["where"]);if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div>".select_input(" name='where[$s][col]' onchange='$Ka'",$d,$X["col"],"(".'anywhere'.")"),html_select("where[$s][op]",$this->operators,$X["op"],$Ka),"<input type='search' name='where[$s][val]' value='".h($X["val"])."' onchange='".($X?"selectFieldChange(this.form)":"selectAddRow(this)").";' onkeydown='selectSearchKeydown(this, event);' onsearch='selectSearchSearch(this);'></div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($fe,$d,$v){print_fieldset("sort",'Sort',$fe);$s=0;foreach((array)$_GET["order"]as$y=>$X){if($X!=""){echo"<div>".select_input(" name='order[$s]' onchange='selectFieldChange(this.form);'",$d,$X),checkbox("desc[$s]",1,isset($_GET["desc"][$y]),'descending')."</div>\n";$s++;}}echo"<div>".select_input(" name='order[$s]' onchange='selectAddRow(this);'",$d),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>";echo"<input type='number' name='limit' class='size' value='".h($z)."' onchange='selectFieldChange(this.form);'>","</div></fieldset>\n";}function
selectLengthPrint($ag){if($ag!==null){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($ag)."'>","</div></fieldset>\n";}}function
selectActionPrint($v){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script type='text/javascript'>\n","var indexColumns = ";$d=array();foreach($v
as$u){if($u["type"]!="FULLTEXT")$d[reset($u["columns"])]=1;}$d[""]=1;foreach($d
as$y=>$X)json_row($y);echo";\n","selectFieldChange(document.getElementById('form'));\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint($Qb,$d){}function
selectColumnsProcess($d,$v){global$yc,$Ac;$N=array();$r=array();foreach((array)$_GET["columns"]as$y=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],$yc)||in_array($X["fun"],$Ac)))){$N[$y]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$Ac))$r[]=$N[$y];}}return
array($N,$r);}function
selectSearchProcess($m,$v){global$e,$x;$K=array();foreach($v
as$s=>$u){if($u["type"]=="FULLTEXT"&&$_GET["fulltext"][$s]!="")$K[]="MATCH (".implode(", ",array_map('idf_escape',$u["columns"])).") AGAINST (".q($_GET["fulltext"][$s]).(isset($_GET["boolean"][$s])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$db=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Mc=process_length($X["val"]);$db.=" ".($Mc!=""?$Mc:"(NULL)");}elseif($X["op"]=="SQL")$db=" $X[val]";elseif($X["op"]=="LIKE %%")$db=" LIKE ".$this->processInput($m[$X["col"]],"%$X[val]%");elseif($X["op"]=="ILIKE %%")$db=" ILIKE ".$this->processInput($m[$X["col"]],"%$X[val]%");elseif(!preg_match('~NULL$~',$X["op"]))$db.=" ".$this->processInput($m[$X["col"]],$X["val"]);if($X["col"]!="")$K[]=idf_escape($X["col"]).$db;else{$Ya=array();foreach($m
as$E=>$l){$Yc=preg_match('~char|text|enum|set~',$l["type"]);if((is_numeric($X["val"])||!preg_match('~(^|[^o])int|float|double|decimal|bit~',$l["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||$Yc)){$E=idf_escape($E);$Ya[]=($x=="sql"&&$Yc&&!preg_match("~^utf8_~",$l["collation"])?"CONVERT($E USING ".charset($e).")":$E);}}$K[]=($Ya?"(".implode("$db OR ",$Ya)."$db)":"0");}}}return$K;}function
selectOrderProcess($m,$v){$K=array();foreach((array)$_GET["order"]as$y=>$X){if($X!="")$K[]=(preg_match('~^((COUNT\\(DISTINCT |[A-Z0-9_]+\\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\\)|COUNT\\(\\*\\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$y])?" DESC":"");}return$K;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$sc){return
false;}function
selectQueryBuild($N,$Z,$r,$fe,$z,$F){return"";}function
messageQuery($I,$bg){global$x;restart_session();$Fc=&get_session("queries");$t="sql-".count($Fc[$_GET["db"]]);if(strlen($I)>1e6)$I=preg_replace('~[\x80-\xFF]+$~','',substr($I,0,1e6))."\n...";$Fc[$_GET["db"]][]=array($I,time(),$bg);return" <span class='time'>".@date("H:i:s")."</span> <a href='#$t' onclick=\"return !toggle('$t');\">".'SQL command'."</a>"."<div id='$t' class='hidden'><pre><code class='jush-$x'>".shorten_utf8($I,1000).'</code></pre>'.($bg?" <span class='time'>($bg)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($Fc[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
editFunctions($l){global$Lb;$K=($l["null"]?"NULL/":"");foreach($Lb
as$y=>$yc){if(!$y||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($yc
as$ze=>$X){if(!$ze||preg_match("~$ze~",$l["type"]))$K.="/$X";}if($y&&!preg_match('~set|blob|bytea|raw|file~',$l["type"]))$K.="/SQL";}}if($l["auto_increment"]&&!isset($_GET["select"])&&!where($_GET))$K='Auto Increment';return
explode("/",$K);}function
editInput($Q,$l,$wa,$Y){if($l["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$wa value='-1' checked><i>".'original'."</i></label> ":"").($l["null"]?"<label><input type='radio'$wa value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$wa,$l,$Y,0);return"";}function
processInput($l,$Y,$p=""){if($p=="SQL")return$Y;$E=$l["field"];$K=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$p))$K="$p()";elseif(preg_match('~^current_(date|timestamp)$~',$p))$K=$p;elseif(preg_match('~^([+-]|\\|\\|)$~',$p))$K=idf_escape($E)." $p $K";elseif(preg_match('~^[+-] interval$~',$p))$K=idf_escape($E)." $p ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+$~i",$Y)?$Y:$K);elseif(preg_match('~^(addtime|subtime|concat)$~',$p))$K="$p(".idf_escape($E).", $K)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$p))$K="$p($K)";return
unconvert_field($l,$K);}function
dumpOutput(){$K=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$K['gz']='gzip';return$K;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($i){}function
dumpTable($Q,$If,$Zc=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($If)dump_csv(array_keys(fields($Q)));}else{if($Zc==2){$m=array();foreach(fields($Q)as$E=>$l)$m[]=idf_escape($E)." $l[full_type]";$g="CREATE TABLE ".table($Q)." (".implode(", ",$m).")";}else$g=create_sql($Q,$_POST["auto_increment"]);set_utf8mb4($g);if($If&&$g){if($If=="DROP+CREATE"||$Zc==1)echo"DROP ".($Zc==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($Zc==1)$g=remove_definer($g);echo"$g;\n\n";}}}function
dumpData($Q,$If,$I){global$e,$x;$vd=($x=="sqlite"?0:1048576);if($If){if($_POST["format"]=="sql"){if($If=="TRUNCATE+INSERT")echo
truncate_sql($Q).";\n";$m=fields($Q);}$J=$e->query($I,1);if($J){$Rc="";$Ia="";$bd=array();$Kf="";$lc=($Q!=''?'fetch_assoc':'fetch_row');while($L=$J->$lc()){if(!$bd){$Hg=array();foreach($L
as$X){$l=$J->fetch_field();$bd[]=$l->name;$y=idf_escape($l->name);$Hg[]="$y = VALUES($y)";}$Kf=($If=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Hg):"").";\n";}if($_POST["format"]!="sql"){if($If=="table"){dump_csv($bd);$If="INSERT";}dump_csv($L);}else{if(!$Rc)$Rc="INSERT INTO ".table($Q)." (".implode(", ",array_map('idf_escape',$bd)).") VALUES";foreach($L
as$y=>$X){$l=$m[$y];$L[$y]=($X!==null?unconvert_field($l,preg_match('~(^|[^o])int|float|double|decimal~',$l["type"])&&$X!=''?$X:q($X)):"NULL");}$mf=($vd?"\n":" ")."(".implode(",\t",$L).")";if(!$Ia)$Ia=$Rc.$mf;elseif(strlen($Ia)+4+strlen($mf)+strlen($Kf)<$vd)$Ia.=",$mf";else{echo$Ia.$Kf;$Ia=$Rc.$mf;}}}if($Ia)echo$Ia.$Kf;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$e->error)."\n";}}function
dumpFilename($Jc){return
friendly_url($Jc!=""?$Jc:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($Jc,$Gd=false){$oe=$_POST["output"];$gc=(preg_match('~sql~',$_POST["format"])?"sql":($Gd?"tar":"csv"));header("Content-Type: ".($oe=="gz"?"application/x-gzip":($gc=="tar"?"application/x-tar":($gc=="sql"||$oe!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($oe=="gz")ob_start('ob_gzencode',1e6);return$gc;}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");return
true;}function
navigation($Fd){global$ga,$x,$Eb,$e;echo'<h1>
',$this->name(),' <span class="version">',$ga,'</span></h1>
';if($Fd=="auth"){$pc=true;foreach((array)$_SESSION["pwds"]as$Jg=>$wf){foreach($wf
as$O=>$Fg){foreach($Fg
as$V=>$G){if($G!==null){if($pc){echo"<p id='logins' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";$pc=false;}$tb=$_SESSION["db"][$Jg][$O][$V];foreach(($tb?array_keys($tb):array(""))as$i)echo"<a href='".h(auth_url($Jg,$O,$V,$i))."'>($Eb[$Jg]) ".h($V.($O!=""?"@$O":"").($i!=""?" - $i":""))."</a><br>\n";}}}}}else{if($_GET["ns"]!==""&&!$Fd&&DB!=""){$e->select_db(DB);$S=table_status('',true);}if(support("sql")){echo '<script type="text/javascript">
';if($S){$qd=array();foreach($S
as$Q=>$U)$qd[]=preg_quote($Q,'/');echo"var jushLinks = { $x: [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$qd).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$x;\n";}echo'bodyLoad(\'',(is_object($e)?substr($e->server_info,0,3):""),'\');
</script>
';}$this->databasesPrint($Fd);if(DB==""||!$Fd){echo"<p class='links'>".(support("sql")?"<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>\n<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>\n":"")."";if(support("dump"))echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>\n";}if($_GET["ns"]!==""&&!$Fd&&DB!=""){echo'<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>\n";if(!$S)echo"<p class='message'>".'No tables.'."\n";else$this->tablesPrint($S);}}}function
databasesPrint($Fd){global$b,$e;$h=$this->databases();echo'<form action="">
<p id="dbs">
';hidden_fields_get();$rb=" onmousedown='dbMouseDown(event, this);' onchange='dbChange(this);'";echo"<span title='".'database'."'>DB</span>: ".($h?"<select name='db'$rb>".optionlist(array(""=>"")+$h,DB)."</select>":'<input name="db" value="'.h(DB).'" autocapitalize="off">'),"<input type='submit' value='".'Use'."'".($h?" class='hidden'":"").">\n";if($Fd!="db"&&DB!=""&&$e->select_db(DB)){}echo(isset($_GET["sql"])?'<input type="hidden" name="sql" value="">':(isset($_GET["schema"])?'<input type="hidden" name="schema" value="">':(isset($_GET["dump"])?'<input type="hidden" name="dump" value="">':(isset($_GET["privileges"])?'<input type="hidden" name="privileges" value="">':"")))),"</p></form>\n";}function
tablesPrint($S){echo"<p id='tables' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";foreach($S
as$Q=>$Df){echo'<a href="'.h(ME).'select='.urlencode($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q,"select").">".'select'."</a> ";$E=$this->tableName($Df);echo(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"])),(is_view($Df)?"view":""),"structure")." title='".'Show structure'."'>$E</a>":"<span>$E</span>")."<br>\n";}}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);if($b->operators===null)$b->operators=$be;function
page_header($eg,$k="",$Ha=array(),$fg=""){global$ca,$ga,$b,$Eb,$x;page_headers();if(is_ajax()&&$k){page_messages($k);exit;}$gg=$eg.($fg!=""?": $fg":"");$hg=strip_tags($gg.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="robots" content="noindex">
<meta name="referrer" content="origin-when-crossorigin">
<title>',$hg,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME))."?file=default.css&amp;version=4.2.1&amp;driver=mysql",'">
<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=functions.js&amp;version=4.2.1&amp;driver=mysql",'"></script>
';if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=4.2.1&amp;driver=mysql",'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=4.2.1&amp;driver=mysql",'">
';}echo'
<body class="ltr nojs" onkeydown="bodyKeydown(event);" onclick="bodyClick(event);"',(isset($_COOKIE["adminer_version"])?"":" onload=\"verifyVersion('$ga');\"");?>>
<script type="text/javascript">
document.body.className = document.body.className.replace(/ nojs/, ' js');
var offlineMessage = '<?php echo
js_escape('You are offline.'),'\';
</script>

<div id="help" class="jush-',$x,' jsonly hidden" onmouseover="helpOpen = 1;" onmouseout="helpMouseout(this, event);"></div>

<div id="content">
';if($Ha!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?$_:".").'">'.$Eb[DRIVER].'</a> &raquo; ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$O=(SERVER!=""?h(SERVER):'Server');if($Ha===false)echo"$O\n";else{echo"<a href='".($_?h($_):".")."' accesskey='1' title='Alt+Shift+1'>$O</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Ha)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Ha)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Ha
as$y=>$X){$xb=(is_array($X)?$X[1]:h($X));if($xb!="")echo"<a href='".h(ME."$y=").urlencode(is_array($X)?$X[0]:$X)."'>$xb</a> &raquo; ";}}echo"$eg\n";}}echo"<h2>$gg</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($k);$h=&get_session("dbs");if(DB!=""&&$h&&!in_array(DB,$h,true))$h=null;stop_session();define("PAGE_HEADER",1);}function
page_headers(){global$b;header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");if($b->headers()){header("X-Frame-Options: deny");header("X-XSS-Protection: 0");}}function
page_messages($k){$Bg=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Dd=$_SESSION["messages"][$Bg];if($Dd){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Dd)."</div>\n";unset($_SESSION["messages"][$Bg]);}if($k)echo"<div class='error'>$k</div>\n";}function
page_footer($Fd=""){global$b,$T;echo'</div>

';if($Fd!="auth"){echo'<form action="" method="post">
<p class="logout">
<input type="hidden" name="token" value="',$T,'">
</p>
</form>
';}echo'<div id="menu">
';$b->navigation($Fd);echo'</div>
<script type="text/javascript">setupSubmitHighlight(document);</script>
';}function
int32($D){while($D>=2147483648)$D-=4294967296;while($D<=-2147483649)$D+=4294967296;return(int)$D;}function
long2str($W,$Ng){$mf='';foreach($W
as$X)$mf.=pack('V',$X);if($Ng)return
substr($mf,0,end($W));return$mf;}function
str2long($mf,$Ng){$W=array_values(unpack('V*',str_pad($mf,4*ceil(strlen($mf)/4),"\0")));if($Ng)$W[]=strlen($mf);return$W;}function
xxtea_mx($Sg,$Rg,$Lf,$ad){return
int32((($Sg>>5&0x7FFFFFF)^$Rg<<2)+(($Rg>>3&0x1FFFFFFF)^$Sg<<4))^int32(($Lf^$Rg)+($ad^$Sg));}function
encrypt_string($Ff,$y){if($Ff=="")return"";$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($Ff,true);$D=count($W)-1;$Sg=$W[$D];$Rg=$W[0];$H=floor(6+52/($D+1));$Lf=0;while($H-->0){$Lf=int32($Lf+0x9E3779B9);$Kb=$Lf>>2&3;for($pe=0;$pe<$D;$pe++){$Rg=$W[$pe+1];$Hd=xxtea_mx($Sg,$Rg,$Lf,$y[$pe&3^$Kb]);$Sg=int32($W[$pe]+$Hd);$W[$pe]=$Sg;}$Rg=$W[0];$Hd=xxtea_mx($Sg,$Rg,$Lf,$y[$pe&3^$Kb]);$Sg=int32($W[$D]+$Hd);$W[$D]=$Sg;}return
long2str($W,false);}function
decrypt_string($Ff,$y){if($Ff=="")return"";if(!$y)return
false;$y=array_values(unpack("V*",pack("H*",md5($y))));$W=str2long($Ff,false);$D=count($W)-1;$Sg=$W[$D];$Rg=$W[0];$H=floor(6+52/($D+1));$Lf=int32($H*0x9E3779B9);while($Lf){$Kb=$Lf>>2&3;for($pe=$D;$pe>0;$pe--){$Sg=$W[$pe-1];$Hd=xxtea_mx($Sg,$Rg,$Lf,$y[$pe&3^$Kb]);$Rg=int32($W[$pe]-$Hd);$W[$pe]=$Rg;}$Sg=$W[$D];$Hd=xxtea_mx($Sg,$Rg,$Lf,$y[$pe&3^$Kb]);$Rg=int32($W[0]-$Hd);$W[0]=$Rg;$Lf=int32($Lf-0x9E3779B9);}return
long2str($W,true);}$e='';$Ec=$_SESSION["token"];if(!$Ec)$_SESSION["token"]=rand(1,1e6);$T=get_token();$_e=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($y)=explode(":",$X);$_e[$y]=$X;}}function
add_invalid_login(){global$b;$nc=get_temp_dir()."/adminer.invalid";$o=@fopen($nc,"r+");if(!$o){$o=@fopen($nc,"w");if(!$o)return;}flock($o,LOCK_EX);$Uc=unserialize(stream_get_contents($o));$bg=time();if($Uc){foreach($Uc
as$Vc=>$X){if($X[0]<$bg)unset($Uc[$Vc]);}}$Tc=&$Uc[$b->bruteForceKey()];if(!$Tc)$Tc=array($bg+30*60,0);$Tc[1]++;$uf=serialize($Uc);rewind($o);fwrite($o,$uf);ftruncate($o,strlen($uf));flock($o,LOCK_UN);fclose($o);}$xa=$_POST["auth"];if($xa){$Uc=unserialize(@file_get_contents(get_temp_dir()."/adminer.invalid"));$Tc=$Uc[$b->bruteForceKey()];$Md=($Tc[1]>30?$Tc[0]-time():0);if($Md>0)auth_error(lang(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($Md/60)));session_regenerate_id();$Jg=$xa["driver"];$O=$xa["server"];$V=$xa["username"];$G=(string)$xa["password"];$i=$xa["db"];set_password($Jg,$O,$V,$G);$_SESSION["db"][$Jg][$O][$V][$i]=true;if($xa["permanent"]){$y=base64_encode($Jg)."-".base64_encode($O)."-".base64_encode($V)."-".base64_encode($i);$Ke=$b->permanentLogin(true);$_e[$y]="$y:".base64_encode($Ke?encrypt_string($G,$Ke):"");cookie("adminer_permanent",implode(" ",$_e));}if(count($_POST)==1||DRIVER!=$Jg||SERVER!=$O||$_GET["username"]!==$V||DB!=$i)redirect(auth_url($Jg,$O,$V,$i));}elseif($_POST["logout"]){if($Ec&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}else{foreach(array("pwds","db","dbs","queries")as$y)set_session($y,null);unset_permanent();redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.');}}elseif($_e&&!$_SESSION["pwds"]){session_regenerate_id();$Ke=$b->permanentLogin();foreach($_e
as$y=>$X){list(,$Qa)=explode(":",$X);list($Jg,$O,$V,$i)=array_map('base64_decode',explode("-",$y));set_password($Jg,$O,$V,decrypt_string(base64_decode($Qa),$Ke));$_SESSION["db"][$Jg][$O][$V][$i]=true;}}function
unset_permanent(){global$_e;foreach($_e
as$y=>$X){list($Jg,$O,$V,$i)=array_map('base64_decode',explode("-",$y));if($Jg==DRIVER&&$O==SERVER&&$V==$_GET["username"]&&$i==DB)unset($_e[$y]);}cookie("adminer_permanent",implode(" ",$_e));}function
auth_error($k){global$b,$Ec;$k=h($k);$xf=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$xf]||$_GET[$xf])&&!$Ec)$k='Session expired, please login again.';else{add_invalid_login();$G=get_password();if($G!==null){if($G===false)$k.='<br>'.sprintf('Master password expired. <a href="http://www.adminer.org/en/extension/" target="_blank">Implement</a> %s method to make it permanent.','<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent();}}if(!$_COOKIE[$xf]&&$_GET[$xf]&&ini_bool("session.use_only_cookies"))$k='Session support must be enabled.';$re=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?$_COOKIE["adminer_key"]:rand_string()),$re["lifetime"]);page_header('Login',$k,null);echo"<form action='' method='post'>\n";$b->loginForm();echo"<div>";hidden_fields($_POST,array("auth"));echo"</div>\n","</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])){if(!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",$Fe)),false);page_footer("auth");exit;}$e=connect();}$j=new
Min_Driver($e);if(!is_object($e)||!$b->login($_GET["username"],get_password()))auth_error((is_string($e)?$e:'Invalid credentials.'));if($xa&&$_POST["token"])$_POST["token"]=$T;$k='';if($_POST){if(!verify_token()){$Oc="max_input_vars";$zd=ini_get($Oc);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$y){$X=ini_get($y);if($X&&(!$zd||$X<$zd)){$Oc=$y;$zd=$X;}}}$k=(!$_POST["token"]&&$zd?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$Oc'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$k=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$k.=' '.'You can upload a big SQL file via FTP and import it from server.';}if(!ini_bool("session.use_cookies")||@ini_set("session.use_cookies",false)!==false)session_write_close();function
select($J,$f=null,$ie=array(),$z=0){global$x;$qd=array();$v=array();$d=array();$Fa=array();$tg=array();$K=array();odd('');for($s=0;(!$z||$s<$z)&&($L=$J->fetch_row());$s++){if(!$s){echo"<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($w=0;$w<count($L);$w++){$l=$J->fetch_field();$E=$l->name;$he=$l->orgtable;$ge=$l->orgname;$K[$l->table]=$he;if($ie&&$x=="sql")$qd[$w]=($E=="table"?"table=":($E=="possible_keys"?"indexes=":null));elseif($he!=""){if(!isset($v[$he])){$v[$he]=array();foreach(indexes($he,$f)as$u){if($u["type"]=="PRIMARY"){$v[$he]=array_flip($u["columns"]);break;}}$d[$he]=$v[$he];}if(isset($d[$he][$ge])){unset($d[$he][$ge]);$v[$he][$ge]=$w;$qd[$w]=$he;}}if($l->charsetnr==63)$Fa[$w]=true;$tg[$w]=$l->type;echo"<th".($he!=""||$l->name!=$ge?" title='".h(($he!=""?"$he.":"").$ge)."'":"").">".h($E).($ie?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($E))):"");}echo"</thead>\n";}echo"<tr".odd().">";foreach($L
as$y=>$X){if($X===null)$X="<i>NULL</i>";elseif($Fa[$y]&&!is_utf8($X))$X="<i>".lang(array('%d byte','%d bytes'),strlen($X))."</i>";elseif(!strlen($X))$X="&nbsp;";else{$X=h($X);if($tg[$y]==254)$X="<code>$X</code>";}if(isset($qd[$y])&&!$d[$qd[$y]]){if($ie&&$x=="sql"){$Q=$L[array_search("table=",$qd)];$_=$qd[$y].urlencode($ie[$Q]!=""?$ie[$Q]:$Q);}else{$_="edit=".urlencode($qd[$y]);foreach($v[$qd[$y]]as$Ua=>$w)$_.="&where".urlencode("[".bracket_escape($Ua)."]")."=".urlencode($L[$w]);}$X="<a href='".h(ME.$_)."'>$X</a>";}echo"<td>$X";}}echo($s?"</table>":"<p class='message'>".'No rows.')."\n";return$K;}function
referencable_primary($sf){$K=array();foreach(table_status('',true)as$Pf=>$Q){if($Pf!=$sf&&fk_support($Q)){foreach(fields($Pf)as$l){if($l["primary"]){if($K[$Pf]){unset($K[$Pf]);break;}$K[$Pf]=$l;}}}}return$K;}function
textarea($E,$Y,$M=10,$Ya=80){global$x;echo"<textarea name='$E' rows='$M' cols='$Ya' class='sqlarea jush-$x' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
edit_type($y,$l,$Xa,$tc=array()){global$Hf,$tg,$_g,$Xd;$U=$l["type"];echo'<td><select name="',$y,'[type]" class="type" onfocus="lastType = selectValue(this);" onchange="editingTypeChange(this);"',on_help("getTarget(event).value",1),'>';if($U&&!isset($tg[$U])&&!isset($tc[$U]))array_unshift($Hf,$U);if($tc)$Hf['Foreign keys']=$tc;echo
optionlist($Hf,$U),'</select>
<td><input name="',$y,'[length]" value="',h($l["length"]),'" size="3" onfocus="editingLengthFocus(this);"',(!$l["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":""),' onchange="editingLengthChange(this);" onkeyup="this.onchange();"><td class="options">';echo"<select name='$y"."[collation]'".(preg_match('~(char|text|enum|set)$~',$U)?"":" class='hidden'").'><option value="">('.'collation'.')'.optionlist($Xa,$l["collation"]).'</select>',($_g?"<select name='$y"."[unsigned]'".(!$U||preg_match('~((^|[^o])int|float|double|decimal)$~',$U)?"":" class='hidden'").'><option>'.optionlist($_g,$l["unsigned"]).'</select>':''),(isset($l['on_update'])?"<select name='$y"."[on_update]'".(preg_match('~timestamp|datetime~',$U)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),$l["on_update"]).'</select>':''),($tc?"<select name='$y"."[on_delete]'".(preg_match("~`~",$U)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",$Xd),$l["on_delete"])."</select> ":" ");}function
process_length($md){global$Vb;return(preg_match("~^\\s*\\(?\\s*$Vb(?:\\s*,\\s*$Vb)*+\\s*\\)?\\s*\$~",$md)&&preg_match_all("~$Vb~",$md,$td)?"(".implode(",",$td[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$md)));}function
process_type($l,$Va="COLLATE"){global$_g;return" $l[type]".process_length($l["length"]).(preg_match('~(^|[^o])int|float|double|decimal~',$l["type"])&&in_array($l["unsigned"],$_g)?" $l[unsigned]":"").(preg_match('~char|text|enum|set~',$l["type"])&&$l["collation"]?" $Va ".q($l["collation"]):"");}function
process_field($l,$rg){global$x;$vb=$l["default"];return
array(idf_escape(trim($l["field"])),process_type($rg),($l["null"]?" NULL":" NOT NULL"),(isset($vb)?" DEFAULT ".((preg_match('~time~',$l["type"])&&preg_match('~^CURRENT_TIMESTAMP$~i',$vb))||($x=="sqlite"&&preg_match('~^CURRENT_(TIME|TIMESTAMP|DATE)$~i',$vb))||($l["type"]=="bit"&&preg_match("~^([0-9]+|b'[0-1]+')\$~",$vb))||($x=="pgsql"&&preg_match("~^[a-z]+\\(('[^']*')+\\)\$~",$vb))?$vb:q($vb)):""),(preg_match('~timestamp|datetime~',$l["type"])&&$l["on_update"]?" ON UPDATE $l[on_update]":""),(support("comment")&&$l["comment"]!=""?" COMMENT ".q($l["comment"]):""),($l["auto_increment"]?auto_increment():null),);}function
type_class($U){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$y=>$X){if(preg_match("~$y|$X~",$U))return" class='$y'";}}function
edit_fields($m,$Xa,$U="TABLE",$tc=array(),$cb=false){global$e,$Pc;echo'<thead><tr class="wrap">
';if($U=="PROCEDURE"){echo'<td>&nbsp;';}echo'<th>',($U=="TABLE"?'Column name':'Parameter name'),'<td>Type<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;" onblur="editingLengthBlur(this);"></textarea>
<td>Length
<td>Options
';if($U=="TABLE"){echo'<td>NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym title="Auto Increment">AI</acronym>',doc_link(array('sql'=>"example-auto-increment.html",'sqlite'=>"autoinc.html",'pgsql'=>"datatype.html#DATATYPE-SERIAL",'mssql'=>"ms186775.aspx",)),'<td>Default value
',(support("comment")?"<td".($cb?"":" class='hidden'").">".'Comment':"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($m))."]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.2.1&amp;driver=mysql' alt='+' title='".'Add next'."'>",'<script type="text/javascript">row_count = ',count($m),';</script>
</thead>
<tbody onkeydown="return editingKeydown(event);">
';foreach($m
as$s=>$l){$s++;$je=$l[($_POST?"orig":"field")];$Ab=(isset($_POST["add"][$s-1])||(isset($l["field"])&&!$_POST["drop_col"][$s]))&&(support("drop_col")||$je=="");echo'<tr',($Ab?"":" style='display: none;'"),'>
',($U=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",$Pc),$l["inout"]):""),'<th>';if($Ab){echo'<input name="fields[',$s,'][field]" value="',h($l["field"]),'" onchange="editingNameChange(this);',($l["field"]!=""||count($m)>1?'':' editingAddRow(this);" onkeyup="if (this.value) editingAddRow(this);'),'" maxlength="64" autocapitalize="off">';}echo'<input type="hidden" name="fields[',$s,'][orig]" value="',h($je),'">
';edit_type("fields[$s]",$l,$Xa,$tc);if($U=="TABLE"){echo'<td>',checkbox("fields[$s][null]",1,$l["null"],"","","block"),'<td><label class="block"><input type="radio" name="auto_increment_col" value="',$s,'"';if($l["auto_increment"]){echo' checked';}?> onclick="var field = this.form['fields[' + this.value + '][field]']; if (!field.value) { field.value = 'id'; field.onchange(); }"></label><td><?php
echo
checkbox("fields[$s][has_default]",1,$l["has_default"]),'<input name="fields[',$s,'][default]" value="',h($l["default"]),'" onkeyup="keyupChange.call(this);" onchange="this.previousSibling.checked = true;">
',(support("comment")?"<td".($cb?"":" class='hidden'")."><input name='fields[$s][comment]' value='".h($l["comment"])."' maxlength='".($e->server_info>=5.5?1024:255)."'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.2.1&amp;driver=mysql' alt='+' title='".'Add next'."' onclick='return !editingAddRow(this, 1);'>&nbsp;"."<input type='image' class='icon' name='up[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=up.gif&amp;version=4.2.1&amp;driver=mysql' alt='^' title='".'Move up'."'>&nbsp;"."<input type='image' class='icon' name='down[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=down.gif&amp;version=4.2.1&amp;driver=mysql' alt='v' title='".'Move down'."'>&nbsp;":""),($je==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$s]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=4.2.1&amp;driver=mysql' alt='x' title='".'Remove'."' onclick=\"return !editingRemoveRow(this, 'fields\$1[field]');\">":""),"\n";}}function
process_fields(&$m){ksort($m);$Qd=0;if($_POST["up"]){$gd=0;foreach($m
as$y=>$l){if(key($_POST["up"])==$y){unset($m[$y]);array_splice($m,$gd,0,array($l));break;}if(isset($l["field"]))$gd=$Qd;$Qd++;}}elseif($_POST["down"]){$vc=false;foreach($m
as$y=>$l){if(isset($l["field"])&&$vc){unset($m[key($_POST["down"])]);array_splice($m,$Qd,0,array($vc));break;}if(key($_POST["down"])==$y)$vc=$l;$Qd++;}}elseif($_POST["add"]){$m=array_values($m);array_splice($m,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum($B){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($B[0][0].$B[0][0],$B[0][0],substr($B[0],1,-1))),'\\'))."'";}function
grant($q,$Me,$d,$Wd){if(!$Me)return
true;if($Me==array("ALL PRIVILEGES","GRANT OPTION"))return($q=="GRANT"?queries("$q ALL PRIVILEGES$Wd WITH GRANT OPTION"):queries("$q ALL PRIVILEGES$Wd")&&queries("$q GRANT OPTION$Wd"));return
queries("$q ".preg_replace('~(GRANT OPTION)\\([^)]*\\)~','\\1',implode("$d, ",$Me).$d).$Wd);}function
drop_create($Fb,$g,$Gb,$Yf,$Hb,$A,$Cd,$Ad,$Bd,$Td,$Kd){if($_POST["drop"])query_redirect($Fb,$A,$Cd);elseif($Td=="")query_redirect($g,$A,$Bd);elseif($Td!=$Kd){$lb=queries($g);queries_redirect($A,$Ad,$lb&&queries($Fb));if($lb)queries($Gb);}else
queries_redirect($A,$Ad,queries($Yf)&&queries($Hb)&&queries($Fb)&&queries($g));}function
create_trigger($Wd,$L){global$x;$dg=" $L[Timing] $L[Event]".($L["Event"]=="UPDATE OF"?" ".idf_escape($L["Of"]):"");return"CREATE TRIGGER ".idf_escape($L["Trigger"]).($x=="mssql"?$Wd.$dg:$dg.$Wd).rtrim(" $L[Type]\n$L[Statement]",";").";";}function
create_routine($jf,$L){global$Pc;$P=array();$m=(array)$L["fields"];ksort($m);foreach($m
as$l){if($l["field"]!="")$P[]=(preg_match("~^($Pc)\$~",$l["inout"])?"$l[inout] ":"").idf_escape($l["field"]).process_type($l,"CHARACTER SET");}return"CREATE $jf ".idf_escape(trim($L["name"]))." (".implode(", ",$P).")".(isset($_GET["function"])?" RETURNS".process_type($L["returns"],"CHARACTER SET"):"").($L["language"]?" LANGUAGE $L[language]":"").rtrim("\n$L[definition]",";").";";}function
remove_definer($I){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\\1)',logged_user()).'`~','\\1',$I);}function
format_foreign_key($n){global$Xd;return" FOREIGN KEY (".implode(", ",array_map('idf_escape',$n["source"])).") REFERENCES ".table($n["table"])." (".implode(", ",array_map('idf_escape',$n["target"])).")".(preg_match("~^($Xd)\$~",$n["on_delete"])?" ON DELETE $n[on_delete]":"").(preg_match("~^($Xd)\$~",$n["on_update"])?" ON UPDATE $n[on_update]":"");}function
tar_file($nc,$ig){$K=pack("a100a8a8a8a12a12",$nc,644,0,0,decoct($ig->size),decoct(time()));$Pa=8*32;for($s=0;$s<strlen($K);$s++)$Pa+=ord($K[$s]);$K.=sprintf("%06o",$Pa)."\0 ";echo$K,str_repeat("\0",512-strlen($K));$ig->send();echo
str_repeat("\0",511-($ig->size+511)%512);}function
ini_bytes($Oc){$X=ini_get($Oc);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}function
doc_link($ye){global$x,$e;$Cg=array('sql'=>"http://dev.mysql.com/doc/refman/".substr($e->server_info,0,3)."/en/",'sqlite'=>"http://www.sqlite.org/",'pgsql'=>"http://www.postgresql.org/docs/".substr($e->server_info,0,3)."/static/",'mssql'=>"http://msdn.microsoft.com/library/",'oracle'=>"http://download.oracle.com/docs/cd/B19306_01/server.102/b14200/",);return($ye[$x]?"<a href='$Cg[$x]$ye[$x]' target='_blank' rel='noreferrer'><sup>?</sup></a>":"");}function
ob_gzencode($Gf){return
gzencode($Gf);}function
db_size($i){global$e;if(!$e->select_db($i))return"?";$K=0;foreach(table_status()as$R)$K+=$R["Data_length"]+$R["Index_length"];return
format_number($K);}function
set_utf8mb4($g){global$e;static$P=false;if(!$P&&preg_match('~\butf8mb4~i',$g)){$P=true;echo"SET NAMES ".charset($e).";\n\n";}}function
connect_error(){global$b,$e,$T,$k,$Eb;if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$k)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$k,false);echo"<p class='links'>\n";foreach(array('database'=>'Create new database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$y=>$X){if(support($y))echo"<a href='".h(ME)."$y='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',$Eb[DRIVER],"<b>".h($e->server_info)."</b>","<b>$e->extension</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$h=$b->databases();if($h){$pf=support("scheme");$Xa=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);'>\n","<thead><tr>".(support("database")?"<td>&nbsp;":"")."<th>".'Database'." - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>"."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1' onclick=\"return !ajaxSetHtml('".js_escape(ME)."script=connect');\">".'Compute'."</a>"."</thead>\n";$h=($_GET["dbsize"]?count_tables($h):array_flip($h));foreach($h
as$i=>$S){$if=h(ME)."db=".urlencode($i);echo"<tr".odd().">".(support("database")?"<td>".checkbox("db[]",$i,in_array($i,(array)$_POST["db"])):""),"<th><a href='$if'>".h($i)."</a>";$Wa=nbsp(db_collation($i,$Xa));echo"<td>".(support("database")?"<a href='$if".($pf?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$Wa</a>":$Wa),"<td align='right'><a href='$if&amp;schema=' id='tables-".h($i)."' title='".'Database schema'."'>".($_GET["dbsize"]?$S:"?")."</a>","<td align='right' id='size-".h($i)."'>".($_GET["dbsize"]?db_size($i):"?"),"\n";}echo"</table>\n",(support("database")?"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value='' onclick=\"selectCount('selected', formChecked(this, /^db/));\">\n"."<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n"."</div></fieldset>\n":""),"<script type='text/javascript'>tableCheck();</script>\n","<input type='hidden' name='token' value='$T'>\n","</form>\n";}}page_footer("db");}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?$e->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}connect_error();exit;}$Xd="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";class
TmpFile{var$handler;var$size;function
TmpFile(){$this->handler=tmpfile();}function
write($gb){$this->size+=strlen($gb);fwrite($this->handler,$gb);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}$Vb="'(?:''|[^'\\\\]|\\\\.)*'";$Pc="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$m=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$N=array(idf_escape($_GET["field"]));$J=$j->select($a,$N,array(where($_GET,$m)),$N);$L=($J?$J->fetch_row():array());echo$L[0];exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$m=fields($a);if(!$m)$k=error();$R=table_status1($a,true);page_header(($m&&is_view($R)?'View':'Table').": ".h($a),$k);$b->selectLinks($R);$bb=$R["Comment"];if($bb!="")echo"<p>".'Comment'.": ".h($bb)."\n";if($m){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";foreach($m
as$l){echo"<tr".odd()."><th>".h($l["field"]),"<td><span title='".h($l["collation"])."'>".h($l["full_type"])."</span>",($l["null"]?" <i>NULL</i>":""),($l["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($l["default"])?" <span title='".'Default value'."'>[<b>".h($l["default"])."</b>]</span>":""),(support("comment")?"<td>".nbsp($l["comment"]):""),"\n";}echo"</table>\n";}if(!is_view($R)){if(support("indexes")){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$v=indexes($a);if($v){echo"<table cellspacing='0'>\n";foreach($v
as$E=>$u){ksort($u["columns"]);$Je=array();foreach($u["columns"]as$y=>$X)$Je[]="<i>".h($X)."</i>".($u["lengths"][$y]?"(".$u["lengths"][$y].")":"").($u["descs"][$y]?" DESC":"");echo"<tr title='".h($E)."'><th>$u[type]<td>".implode(", ",$Je)."\n";}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(fk_support($R)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$tc=foreign_keys($a);if($tc){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td>&nbsp;</thead>\n";foreach($tc
as$E=>$n){echo"<tr title='".h($E)."'>","<th><i>".implode("</i>, <i>",array_map('h',$n["source"]))."</i>","<td><a href='".h($n["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($n["db"]),ME):($n["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($n["ns"]),ME):ME))."table=".urlencode($n["table"])."'>".($n["db"]!=""?"<b>".h($n["db"])."</b>.":"").($n["ns"]!=""?"<b>".h($n["ns"])."</b>.":"").h($n["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$n["target"]))."</i>)","<td>".nbsp($n["on_delete"])."\n","<td>".nbsp($n["on_update"])."\n",'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($E)).'">'.'Alter'.'</a>';}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}}if(support(is_view($R)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$qg=triggers($a);if($qg){echo"<table cellspacing='0'>\n";foreach($qg
as$y=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($y)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($y))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Qf=array();$Rf=array();$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$td,PREG_SET_ORDER);foreach($td
as$s=>$B){$Qf[$B[1]]=array($B[2],$B[3]);$Rf[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$kg=0;$Ca=-1;$of=array();$Ze=array();$kd=array();foreach(table_status('',true)as$Q=>$R){if(is_view($R))continue;$Ce=0;$of[$Q]["fields"]=array();foreach(fields($Q)as$E=>$l){$Ce+=1.25;$l["pos"]=$Ce;$of[$Q]["fields"][$E]=$l;}$of[$Q]["pos"]=($Qf[$Q]?$Qf[$Q]:array($kg,0));foreach($b->foreignKeys($Q)as$X){if(!$X["db"]){$id=$Ca;if($Qf[$Q][1]||$Qf[$X["table"]][1])$id=min(floatval($Qf[$Q][1]),floatval($Qf[$X["table"]][1]))-1;else$Ca-=.1;while($kd[(string)$id])$id-=.0001;$of[$Q]["references"][$X["table"]][(string)$id]=array($X["source"],$X["target"]);$Ze[$X["table"]][$Q][(string)$id]=$X["target"];$kd[(string)$id]=true;}}$kg=max($kg,$of[$Q]["pos"][0]+2.5+$Ce);}echo'<div id="schema" style="height: ',$kg,'em;" onselectstart="return false;">
<script type="text/javascript">
var tablePos = {',implode(",",$Rf)."\n",'};
var em = document.getElementById(\'schema\').offsetHeight / ',$kg,';
document.onmousemove = schemaMousemove;
document.onmouseup = function (ev) {
	schemaMouseup(ev, \'',js_escape(DB),'\');
};
</script>
';foreach($of
as$E=>$Q){echo"<div class='table' style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em;' onmousedown='schemaMousedown(this, event);'>",'<a href="'.h(ME).'table='.urlencode($E).'"><b>'.h($E)."</b></a>";foreach($Q["fields"]as$l){$X='<span'.type_class($l["type"]).' title="'.h($l["full_type"].($l["null"]?" NULL":'')).'">'.h($l["field"]).'</span>';echo"<br>".($l["primary"]?"<i>$X</i>":$X);}foreach((array)$Q["references"]as$Wf=>$af){foreach($af
as$id=>$We){$jd=$id-$Qf[$E][1];$s=0;foreach($We[0]as$_f)echo"\n<div class='references' title='".h($Wf)."' id='refs$id-".($s++)."' style='left: $jd"."em; top: ".$Q["fields"][$_f]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$jd)."em;'></div></div>";}}foreach((array)$Ze[$E]as$Wf=>$af){foreach($af
as$id=>$d){$jd=$id-$Qf[$E][1];$s=0;foreach($d
as$Vf)echo"\n<div class='references' title='".h($Wf)."' id='refd$id-".($s++)."' style='left: $jd"."em; top: ".$Q["fields"][$Vf]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME))."?file=arrow.gif) no-repeat right center;&amp;version=4.2.1&amp;driver=mysql'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$jd)."em;'></div></div>";}}echo"\n</div>\n";}foreach($of
as$E=>$Q){foreach((array)$Q["references"]as$Wf=>$af){foreach($af
as$id=>$We){$Ed=$kg;$xd=-10;foreach($We[0]as$y=>$_f){$De=$Q["pos"][0]+$Q["fields"][$_f]["pos"];$Ee=$of[$Wf]["pos"][0]+$of[$Wf]["fields"][$We[1][$y]]["pos"];$Ed=min($Ed,$De,$Ee);$xd=max($xd,$De,$Ee);}echo"<div class='references' id='refl$id' style='left: $id"."em; top: $Ed"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($xd-$Ed)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$k){$jb="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$y)$jb.="&$y=".urlencode($_POST[$y]);cookie("adminer_export",substr($jb,1));$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$gc=dump_headers((count($S)==1?key($S):DB),(DB==""||count($S)>1));$Xc=preg_match('~sql~',$_POST["format"]);if($Xc){echo"-- Adminer $ga ".$Eb[DRIVER]." dump\n\n";if($x=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
".($_POST["data_style"]?"SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";$e->query("SET time_zone = '+00:00';");}}$If=$_POST["db_style"];$h=array(DB);if(DB==""){$h=$_POST["databases"];if(is_string($h))$h=explode("\n",rtrim(str_replace("\r","",$h),"\n"));}foreach((array)$h
as$i){$b->dumpDatabase($i);if($e->select_db($i)){if($Xc&&preg_match('~CREATE~',$If)&&($g=$e->result("SHOW CREATE DATABASE ".idf_escape($i),1))){set_utf8mb4($g);if($If=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($i).";\n";echo"$g;\n";}if($Xc){if($If)echo
use_sql($i).";\n\n";$ne="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$jf){foreach(get_rows("SHOW $jf STATUS WHERE Db = ".q($i),null,"-- ")as$L){$g=remove_definer($e->result("SHOW CREATE $jf ".idf_escape($L["Name"]),2));set_utf8mb4($g);$ne.=($If!='DROP+CREATE'?"DROP $jf IF EXISTS ".idf_escape($L["Name"]).";;\n":"")."$g;;\n\n";}}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$L){$g=remove_definer($e->result("SHOW CREATE EVENT ".idf_escape($L["Name"]),3));set_utf8mb4($g);$ne.=($If!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($L["Name"]).";;\n":"")."$g;;\n\n";}}if($ne)echo"DELIMITER ;;\n\n$ne"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$Lg=array();foreach(table_status('',true)as$E=>$R){$Q=(DB==""||in_array($E,(array)$_POST["tables"]));$ob=(DB==""||in_array($E,(array)$_POST["data"]));if($Q||$ob){if($gc=="tar"){$ig=new
TmpFile;ob_start(array($ig,'write'),1e5);}$b->dumpTable($E,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$Lg[]=$E;elseif($ob){$m=fields($E);$b->dumpData($E,$_POST["data_style"],"SELECT *".convert_fields($m,$m)." FROM ".table($E));}if($Xc&&$_POST["triggers"]&&$Q&&($qg=trigger_sql($E,$_POST["table_style"])))echo"\nDELIMITER ;;\n$qg\nDELIMITER ;\n";if($gc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$i/")."$E.csv",$ig);}elseif($Xc)echo"\n";}}foreach($Lg
as$Kg)$b->dumpTable($Kg,$_POST["table_style"],1);if($gc=="tar")echo
pack("x512");}}}if($Xc)echo"-- ".$e->result("SELECT NOW()")."\n";exit;}page_header('Export',$k,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table cellspacing="0">
';$sb=array('','USE','DROP+CREATE','CREATE');$Sf=array('','DROP+CREATE','CREATE');$pb=array('','TRUNCATE+INSERT','INSERT');if($x=="sql")$pb[]='INSERT+UPDATE';parse_str($_COOKIE["adminer_export"],$L);if(!$L)$L=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($L["events"])){$L["routines"]=$L["events"]=($_GET["dump"]=="");$L["triggers"]=$L["table_style"];}echo"<tr><th>".'Output'."<td>".html_select("output",$b->dumpOutput(),$L["output"],0)."\n";echo"<tr><th>".'Format'."<td>".html_select("format",$b->dumpFormat(),$L["format"],0)."\n";echo($x=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$sb,$L["db_style"]).(support("routine")?checkbox("routines",1,$L["routines"],'Routines'):"").(support("event")?checkbox("events",1,$L["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$Sf,$L["table_style"]).checkbox("auto_increment",1,$L["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$L["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$pb,$L["data_style"]),'</table>
<p><input type="submit" value="Export">
<input type="hidden" name="token" value="',$T,'">

<table cellspacing="0">
';$He=array();if(DB!=""){$Na=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$Na onclick='formCheck(this, /^tables\\[/);'>".'Tables'."</label>","<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$Na onclick='formCheck(this, /^data\\[/);'></label>","</thead>\n";$Lg="";$Tf=tables_list();foreach($Tf
as$E=>$U){$Ge=preg_replace('~_.*~','',$E);$Na=($a==""||$a==(substr($a,-1)=="%"?"$Ge%":$E));$Je="<tr><td>".checkbox("tables[]",$E,$Na,$E,"checkboxClick(event, this); formUncheck('check-tables');","block");if($U!==null&&!preg_match('~table~i',$U))$Lg.="$Je\n";else
echo"$Je<td align='right'><label class='block'><span id='Rows-".h($E)."'></span>".checkbox("data[]",$E,$Na,"","checkboxClick(event, this); formUncheck('check-data');")."</label>\n";$He[$Ge]++;}echo$Lg;if($Tf)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}else{echo"<thead><tr><th style='text-align: left;'><label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"")." onclick='formCheck(this, /^databases\\[/);'>".'Database'."</label></thead>\n";$h=$b->databases();if($h){foreach($h
as$i){if(!information_schema($i)){$Ge=preg_replace('~_.*~','',$i);echo"<tr><td>".checkbox("databases[]",$i,$a==""||$a=="$Ge%",$i,"formUncheck('check-databases');","block")."\n";$He[$Ge]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$pc=true;foreach($He
as$y=>$X){if($y!=""&&$X>1){echo($pc?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$y%")."'>".h($y)."</a>";$pc=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');$J=$e->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$q=$J;if(!$J)$J=$e->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($q?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th>&nbsp;</thead>\n";while($L=$J->fetch_assoc())echo'<tr'.odd().'><td>'.h($L["User"])."<td>".h($L["Host"]).'<td><a href="'.h(ME.'user='.urlencode($L["User"]).'&host='.urlencode($L["Host"])).'">'.'Edit'."</a>\n";if(!$q||DB!="")echo"<tr".odd()."><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n",'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";}elseif(isset($_GET["sql"])){if(!$k&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$Gc=&get_session("queries");$Fc=&$Gc[DB];if(!$k&&$_POST["clear"]){$Fc=array();redirect(remove_from_uri("history"));}page_header((isset($_GET["import"])?'Import':'SQL command'),$k);if(!$k&&$_POST){$o=false;if(!isset($_GET["import"]))$I=$_POST["query"];elseif($_POST["webfile"]){$o=@fopen((file_exists("adminer.sql")?"adminer.sql":"compress.zlib://adminer.sql.gz"),"rb");$I=($o?fread($o,1e6):false);}else$I=get_file("sql_file",true);if(is_string($I)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($I)+memory_get_usage()+8e6));if($I!=""&&strlen($I)<1e6){$H=$I.(preg_match("~;[ \t\r\n]*\$~",$I)?"":";");if(!$Fc||reset(end($Fc))!=$H){restart_session();$Fc[]=array($H,time());set_session("queries",$Gc);stop_session();}}$Af="(?:\\s|/\\*.*\\*/|(?:#|-- )[^\n]*\n|--\r?\n)";$wb=";";$Qd=0;$Sb=true;$f=connect();if(is_object($f)&&DB!="")$f->select_db(DB);$ab=0;$Xb=array();$pd=0;$se='[\'"'.($x=="sql"?'`#':($x=="sqlite"?'`[':($x=="mssql"?'[':''))).']|/\\*|-- |$'.($x=="pgsql"?'|\\$[^$]*\\$':'');$lg=microtime(true);parse_str($_COOKIE["adminer_export"],$la);$Jb=$b->dumpFormat();unset($Jb["sql"]);while($I!=""){if(!$Qd&&preg_match("~^$Af*DELIMITER\\s+(\\S+)~i",$I,$B)){$wb=$B[1];$I=substr($I,strlen($B[0]));}else{preg_match('('.preg_quote($wb)."\\s*|$se)",$I,$B,PREG_OFFSET_CAPTURE,$Qd);list($vc,$Ce)=$B[0];if(!$vc&&$o&&!feof($o))$I.=fread($o,1e5);else{if(!$vc&&rtrim($I)=="")break;$Qd=$Ce+strlen($vc);if($vc&&rtrim($vc)!=$wb){while(preg_match('('.($vc=='/*'?'\\*/':($vc=='['?']':(preg_match('~^-- |^#~',$vc)?"\n":preg_quote($vc)."|\\\\."))).'|$)s',$I,$B,PREG_OFFSET_CAPTURE,$Qd)){$mf=$B[0][0];if(!$mf&&$o&&!feof($o))$I.=fread($o,1e5);else{$Qd=$B[0][1]+strlen($mf);if($mf[0]!="\\")break;}}}else{$Sb=false;$H=substr($I,0,$Ce);$ab++;$Je="<pre id='sql-$ab'><code class='jush-$x'>".shorten_utf8(trim($H),1000)."</code></pre>\n";if(!$_POST["only_errors"]){echo$Je;ob_flush();flush();}$Cf=microtime(true);if($e->multi_query($H)&&is_object($f)&&preg_match("~^$Af*USE\\b~isU",$H))$f->query($H);do{$J=$e->store_result();$bg=" <span class='time'>(".format_time($Cf).")</span>".(strlen($H)<1000?" <a href='".h(ME)."sql=".urlencode(trim($H))."'>".'Edit'."</a>":"");if($e->error){echo($_POST["only_errors"]?$Je:""),"<p class='error'>".'Error in query'.($e->errno?" ($e->errno)":"").": ".error()."\n";$Xb[]=" <a href='#sql-$ab'>$ab</a>";if($_POST["error_stops"])break
2;}elseif(is_object($J)){$z=$_POST["limit"];$ie=select($J,$f,array(),$z);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$Nd=$J->num_rows;echo"<p>".($Nd?($z&&$Nd>$z?sprintf('%d / ',$z):"").lang(array('%d row','%d rows'),$Nd):""),$bg;$t="export-$ab";$fc=", <a href='#$t' onclick=\"return !toggle('$t');\">".'Export'."</a><span id='$t' class='hidden'>: ".html_select("output",$b->dumpOutput(),$la["output"])." ".html_select("format",$Jb,$la["format"])."<input type='hidden' name='query' value='".h($H)."'>"." <input type='submit' name='export' value='".'Export'."'><input type='hidden' name='token' value='$T'></span>\n";if($f&&preg_match("~^($Af|\\()*SELECT\\b~isU",$H)&&($ec=explain($f,$H))){$t="explain-$ab";echo", <a href='#$t' onclick=\"return !toggle('$t');\">EXPLAIN</a>$fc","<div id='$t' class='hidden'>\n";select($ec,$f,$ie);echo"</div>\n";}else
echo$fc;echo"</form>\n";}}else{if(preg_match("~^$Af*(CREATE|DROP|ALTER)$Af+(DATABASE|SCHEMA)\\b~isU",$H)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($e->info)."'>".lang(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$e->affected_rows)."$bg\n";}$Cf=microtime(true);}while($e->next_result());$pd+=substr_count($H.$vc,"\n");$I=substr($I,$Qd);$Qd=0;}}}}if($Sb)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"]){echo"<p class='message'>".lang(array('%d query executed OK.','%d queries executed OK.'),$ab-count($Xb))," <span class='time'>(".format_time($lg).")</span>\n";}elseif($Xb&&$ab>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Xb)."\n";}else
echo"<p class='error'>".upload_error($I)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$cc="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$H=$_GET["sql"];if($_POST)$H=$_POST["query"];elseif($_GET["history"]=="all")$H=$Fc;elseif($_GET["history"]!="")$H=$Fc[$_GET["history"]][0];echo"<p>";textarea("query",$H,20);echo($_POST?"":"<script type='text/javascript'>focus(document.getElementsByTagName('textarea')[0]);</script>\n"),"<p>$cc\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{echo"<fieldset><legend>".'File upload'."</legend><div>",(ini_bool("file_uploads")?"SQL (&lt; ".ini_get("upload_max_filesize")."B): <input type='file' name='sql_file[]' multiple>\n$cc":'File uploads are disabled.'),"</div></fieldset>\n","<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>adminer.sql".(extension_loaded("zlib")?"[.gz]":"")."</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n","<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])),'Show only errors')."\n","<input type='hidden' name='token' value='$T'>\n";if(!isset($_GET["import"])&&$Fc){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($Fc);$X;$X=prev($Fc)){$y=key($Fc);list($H,$bg,$Nb)=$X;echo'<a href="'.h(ME."sql=&history=$y").'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$bg)."'>".@date("H:i:s",$bg)."</span>"." <code class='jush-$x'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$H)))),80,"</code>").($Nb?" <span class='time'>($Nb)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$m=fields($a);$Z=(isset($_GET["select"])?(count($_POST["check"])==1?where_check($_POST["check"][0],$m):""):where($_GET,$m));$Ag=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($m
as$E=>$l){if(!isset($l["privileges"][$Ag?"update":"insert"])||$b->fieldName($l)=="")unset($m[$E]);}if($_POST&&!$k&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($Ag?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".urlencode($a);$v=indexes($a);$wg=unique_array($_GET["where"],$v);$Se="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,'Item has been deleted.',$j->delete($a,$Se,!$wg));else{$P=array();foreach($m
as$E=>$l){$X=process_input($l);if($X!==false&&$X!==null)$P[idf_escape($E)]=$X;}if($Ag){if(!$P)redirect($A);queries_redirect($A,'Item has been updated.',$j->update($a,$P,$Se,!$wg));if(is_ajax()){page_headers();page_messages($k);exit;}}else{$J=$j->insert($a,$P);$hd=($J?last_id():0);queries_redirect($A,sprintf('Item%s has been inserted.',($hd?" $hd":"")),$J);}}}$L=null;if($_POST["save"])$L=(array)$_POST["fields"];elseif($Z){$N=array();foreach($m
as$E=>$l){if(isset($l["privileges"]["select"])){$ua=convert_field($l);if($_POST["clone"]&&$l["auto_increment"])$ua="''";if($x=="sql"&&preg_match("~enum|set~",$l["type"]))$ua="1*".idf_escape($E);$N[]=($ua?"$ua AS ":"").idf_escape($E);}}$L=array();if(!support("table"))$N=array("*");if($N){$J=$j->select($a,$N,array($Z),$N,array(),(isset($_GET["select"])?2:1));$L=$J->fetch_assoc();if(!$L)$L=false;if(isset($_GET["select"])&&(!$L||$J->fetch_assoc()))$L=null;}}if(!support("table")&&!$m){if(!$Z){$J=$j->select($a,array("*"),$Z,array("*"));$L=($J?$J->fetch_assoc():false);if(!$L)$L=array($j->primary=>"");}if($L){foreach($L
as$y=>$X){if(!$Z)$L[$y]=null;$m[$y]=array("field"=>$y,"null"=>($y!=$j->primary),"auto_increment"=>($y==$j->primary));}}}edit_form($a,$m,$L,$Ag);}elseif(isset($_GET["create"])){$a=$_GET["create"];$te=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$y)$te[$y]=$y;$Ye=referencable_primary($a);$tc=array();foreach($Ye
as$Pf=>$l)$tc[str_replace("`","``",$Pf)."`".str_replace("`","``",$l["field"])]=$Pf;$le=array();$R=array();if($a!=""){$le=fields($a);$R=table_status($a);if(!$R)$k='No tables.';}$L=$_POST;$L["fields"]=(array)$L["fields"];if($L["auto_increment_col"])$L["fields"][$L["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!process_fields($L["fields"])&&!$k){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$m=array();$ra=array();$Dg=false;$rc=array();ksort($L["fields"]);$ke=reset($le);$pa=" FIRST";foreach($L["fields"]as$y=>$l){$n=$tc[$l["type"]];$rg=($n!==null?$Ye[$n]:$l);if($l["field"]!=""){if(!$l["has_default"])$l["default"]=null;if($y==$L["auto_increment_col"])$l["auto_increment"]=true;$Oe=process_field($l,$rg);$ra[]=array($l["orig"],$Oe,$pa);if($Oe!=process_field($ke,$ke)){$m[]=array($l["orig"],$Oe,$pa);if($l["orig"]!=""||$pa)$Dg=true;}if($n!==null)$rc[idf_escape($l["field"])]=($a!=""&&$x!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$tc[$l["type"]],'source'=>array($l["field"]),'target'=>array($rg["field"]),'on_delete'=>$l["on_delete"],));$pa=" AFTER ".idf_escape($l["field"]);}elseif($l["orig"]!=""){$Dg=true;$m[]=array($l["orig"]);}if($l["orig"]!=""){$ke=next($le);if(!$ke)$pa="";}}$ve="";if($te[$L["partition_by"]]){$we=array();if($L["partition_by"]=='RANGE'||$L["partition_by"]=='LIST'){foreach(array_filter($L["partition_names"])as$y=>$X){$Y=$L["partition_values"][$y];$we[]="\n  PARTITION ".idf_escape($X)." VALUES ".($L["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$ve.="\nPARTITION BY $L[partition_by]($L[partition])".($we?" (".implode(",",$we)."\n)":($L["partitions"]?" PARTITIONS ".(+$L["partitions"]):""));}elseif(support("partitioning")&&preg_match("~partitioned~",$R["Create_options"]))$ve.="\nREMOVE PARTITIONING";$C='Table has been altered.';if($a==""){cookie("adminer_engine",$L["Engine"]);$C='Table has been created.';}$E=trim($L["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($E),$C,alter_table($a,$E,($x=="sqlite"&&($Dg||$rc)?$ra:$m),$rc,($L["Comment"]!=$R["Comment"]?$L["Comment"]:null),($L["Engine"]&&$L["Engine"]!=$R["Engine"]?$L["Engine"]:""),($L["Collation"]&&$L["Collation"]!=$R["Collation"]?$L["Collation"]:""),($L["Auto_increment"]!=""?number($L["Auto_increment"]):""),$ve));}}page_header(($a!=""?'Alter table':'Create table'),$k,array("table"=>$a),h($a));if(!$_POST){$L=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($tg["int"])?"int":(isset($tg["integer"])?"integer":"")))),"partition_names"=>array(""),);if($a!=""){$L=$R;$L["name"]=$a;$L["fields"]=array();if(!$_GET["auto_increment"])$L["Auto_increment"]="";foreach($le
as$l){$l["has_default"]=isset($l["default"]);$L["fields"][]=$l;}if(support("partitioning")){$xc="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$J=$e->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $xc ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($L["partition_by"],$L["partitions"],$L["partition"])=$J->fetch_row();$we=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $xc AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$we[""]="";$L["partition_names"]=array_keys($we);$L["partition_values"]=array_values($we);}}}$Xa=collations();$Ub=engines();foreach($Ub
as$Tb){if(!strcasecmp($Tb,$L["Engine"])){$L["Engine"]=$Tb;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name: <input name="name" maxlength="64" value="',h($L["name"]),'" autocapitalize="off">
';if($a==""&&!$_POST){?><script type='text/javascript'>focus(document.getElementById('form')['name']);</script><?php }echo($Ub?"<select name='Engine' onchange='helpClose();'".on_help("getTarget(event).value",1).">".optionlist(array(""=>"(".'engine'.")")+$Ub,$L["Engine"])."</select>":""),' ',($Xa&&!preg_match("~sqlite|mssql~",$x)?html_select("Collation",array(""=>"(".'collation'.")")+$Xa,$L["Collation"]):""),' <input type="submit" value="Save">
';}echo'
';if(support("columns")){echo'<table cellspacing="0" id="edit-fields" class="nowrap">
';$cb=($_POST?$_POST["comments"]:$L["Comment"]!="");if(!$_POST&&!$cb){foreach($L["fields"]as$l){if($l["comment"]!=""){$cb=true;break;}}}edit_fields($L["fields"],$Xa,"TABLE",$tc,$cb);echo'</table>
<p>
Auto Increment: <input type="number" name="Auto_increment" size="6" value="',h($L["Auto_increment"]),'">
',checkbox("defaults",1,true,'Default values',"columnShow(this.checked, 5)","jsonly");if(!$_POST["defaults"]){echo'<script type="text/javascript">editingHideDefaults()</script>';}echo(support("comment")?"<label><input type='checkbox' name='comments' value='1' class='jsonly' onclick=\"columnShow(this.checked, 6); toggle('Comment'); if (this.checked) this.form['Comment'].focus();\"".($cb?" checked":"").">".'Comment'."</label>".' <input name="Comment" id="Comment" value="'.h($L["Comment"]).'" maxlength="'.($e->server_info>=5.5?2048:60).'"'.($cb?'':' class="hidden"').'>':''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}if(support("partitioning")){$ue=preg_match('~RANGE|LIST~',$L["partition_by"]);print_fieldset("partition",'Partition by',$L["partition_by"]);echo'<p>
',"<select name='partition_by' onchange='partitionByChange(this);'".on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')",1).">".optionlist(array(""=>"")+$te,$L["partition_by"])."</select>",'(<input name="partition" value="',h($L["partition"]),'">)
Partitions: <input type="number" name="partitions" class="size',($ue||!$L["partition_by"]?" hidden":""),'" value="',h($L["partitions"]),'">
<table cellspacing="0" id="partition-table"',($ue?"":" class='hidden'"),'>
<thead><tr><th>Partition name<th>Values</thead>
';foreach($L["partition_names"]as$y=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'"'.($y==count($L["partition_names"])-1?' onchange="partitionNameChange(this);"':'').' autocapitalize="off">','<td><input name="partition_values[]" value="'.h($L["partition_values"][$y]).'">';}echo'</table>
</div></fieldset>
';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Nc=array("PRIMARY","UNIQUE","INDEX");$R=table_status($a,true);if(preg_match('~MyISAM|M?aria'.($e->server_info>=5.6?'|InnoDB':'').'~i',$R["Engine"]))$Nc[]="FULLTEXT";$v=indexes($a);$Ie=array();if($x=="mongo"){$Ie=$v["_id_"];unset($Nc[0]);unset($v["_id_"]);}$L=$_POST;if($_POST&&!$k&&!$_POST["add"]&&!$_POST["drop_col"]){$sa=array();foreach($L["indexes"]as$u){$E=$u["name"];if(in_array($u["type"],$Nc)){$d=array();$nd=array();$yb=array();$P=array();ksort($u["columns"]);foreach($u["columns"]as$y=>$c){if($c!=""){$md=$u["lengths"][$y];$xb=$u["descs"][$y];$P[]=idf_escape($c).($md?"(".(+$md).")":"").($xb?" DESC":"");$d[]=$c;$nd[]=($md?$md:null);$yb[]=$xb;}}if($d){$dc=$v[$E];if($dc){ksort($dc["columns"]);ksort($dc["lengths"]);ksort($dc["descs"]);if($u["type"]==$dc["type"]&&array_values($dc["columns"])===$d&&(!$dc["lengths"]||array_values($dc["lengths"])===$nd)&&array_values($dc["descs"])===$yb){unset($v[$E]);continue;}}$sa[]=array($u["type"],$E,$P);}}}foreach($v
as$E=>$dc)$sa[]=array($dc["type"],$E,"DROP");if(!$sa)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$sa));}page_header('Indexes',$k,array("table"=>$a),h($a));$m=array_keys(fields($a));if($_POST["add"]){foreach($L["indexes"]as$y=>$u){if($u["columns"][count($u["columns"])]!="")$L["indexes"][$y]["columns"][]="";}$u=end($L["indexes"]);if($u["type"]||array_filter($u["columns"],'strlen'))$L["indexes"][]=array("columns"=>array(1=>""));}if(!$L){foreach($v
as$y=>$u){$v[$y]["name"]=$y;$v[$y]["columns"][]="";}$v[]=array("columns"=>array(1=>""));$L["indexes"]=$v;}?>

<form action="" method="post">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th>Index Type
<th><input type="submit" style="left: -1000px; position: absolute;">Column (length)
<th>Name
<th><noscript><input type='image' class='icon' name='add[0]' src='" . h(preg_replace("~\\?.*~", "", ME)) . "?file=plus.gif&amp;version=4.2.1&amp;driver=mysql' alt='+' title='Add next'></noscript>&nbsp;
</thead>
<?php
if($Ie){echo"<tr><td>PRIMARY<td>";foreach($Ie["columns"]as$y=>$c){echo
select_input(" disabled",$m,$c),"<label><input disabled type='checkbox'>".'descending'."</label> ";}echo"<td><td>\n";}$w=1;foreach($L["indexes"]as$u){if(!$_POST["drop_col"]||$w!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$w][type]",array(-1=>"")+$Nc,$u["type"],($w==count($L["indexes"])?"indexesAddRow(this);":1)),"<td>";ksort($u["columns"]);$s=1;foreach($u["columns"]as$y=>$c){echo"<span>".select_input(" name='indexes[$w][columns][$s]' onchange=\"".($s==count($u["columns"])?"indexesAddColumn":"indexesChangeColumn")."(this, '".js_escape($x=="sql"?"":$_GET["indexes"]."_")."');\"",($m?array_combine($m,$m):$m),$c),($x=="sql"||$x=="mssql"?"<input type='number' name='indexes[$w][lengths][$s]' class='size' value='".h($u["lengths"][$y])."'>":""),($x!="sql"?checkbox("indexes[$w][descs][$s]",1,$u["descs"][$y],'descending'):"")," </span>";$s++;}echo"<td><input name='indexes[$w][name]' value='".h($u["name"])."' autocapitalize='off'>\n","<td><input type='image' class='icon' name='drop_col[$w]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=4.2.1&amp;driver=mysql' alt='x' title='".'Remove'."' onclick=\"return !editingRemoveRow(this, 'indexes\$1[type]');\">\n";}$w++;}echo'</table>
<p>
<input type="submit" value="Save">
<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["database"])){$L=$_POST;if($_POST&&!$k&&!isset($_POST["add_x"])){$E=trim($L["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$E){if(DB!=""){$_GET["db"]=$E;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($E),'Database has been renamed.',rename_database($E,$L["collation"]));}else{$h=explode("\n",str_replace("\r","",$E));$Jf=true;$gd="";foreach($h
as$i){if(count($h)==1||$i!=""){if(!create_database($i,$L["collation"]))$Jf=false;$gd=$i;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($gd),'Database has been created.',$Jf);}}else{if(!$L["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($E).(preg_match('~^[a-z0-9_]+$~i',$L["collation"])?" COLLATE $L[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$k,array(),h(DB));$Xa=collations();$E=DB;if($_POST)$E=$L["name"];elseif(DB!="")$L["collation"]=db_collation(DB,$Xa);elseif($x=="sql"){foreach(get_vals("SHOW GRANTS")as$q){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\\.\\*)?~',$q,$B)&&$B[1]){$E=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($E,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($E).'</textarea><br>':'<input name="name" id="name" value="'.h($E).'" maxlength="64" autocapitalize="off">')."\n".($Xa?html_select("collation",array(""=>"(".'collation'.")")+$Xa,$L["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mssql'=>"ms187963.aspx",)):"");?>
<script type='text/javascript'>focus(document.getElementById('name'));</script>
<input type="submit" value="Save">
<?php
if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' class='icon' name='add' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.2.1&amp;driver=mysql' alt='+' title='".'Add next'."'>\n";echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["call"])){$da=$_GET["call"];page_header('Call'.": ".h($da),$k);$jf=routine($da,(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Mc=array();$ne=array();foreach($jf["fields"]as$s=>$l){if(substr($l["inout"],-3)=="OUT")$ne[$s]="@".idf_escape($l["field"])." AS ".idf_escape($l["field"]);if(!$l["inout"]||substr($l["inout"],0,2)=="IN")$Mc[]=$s;}if(!$k&&$_POST){$Ja=array();foreach($jf["fields"]as$y=>$l){if(in_array($y,$Mc)){$X=process_input($l);if($X===false)$X="''";if(isset($ne[$y]))$e->query("SET @".idf_escape($l["field"])." = $X");}$Ja[]=(isset($ne[$y])?"@".idf_escape($l["field"]):$X);}$I=(isset($_GET["callf"])?"SELECT":"CALL")." ".idf_escape($da)."(".implode(", ",$Ja).")";echo"<p><code class='jush-$x'>".h($I)."</code> <a href='".h(ME)."sql=".urlencode($I)."'>".'Edit'."</a>\n";if(!$e->multi_query($I))echo"<p class='error'>".error()."\n";else{$f=connect();if(is_object($f))$f->select_db(DB);do{$J=$e->store_result();if(is_object($J))select($J,$f);else
echo"<p class='message'>".lang(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$e->affected_rows)."\n";}while($e->next_result());if($ne)select($e->query("SELECT ".implode(", ",$ne)));}}echo'
<form action="" method="post">
';if($Mc){echo"<table cellspacing='0'>\n";foreach($Mc
as$y){$l=$jf["fields"][$y];$E=$l["field"];echo"<tr><th>".$b->fieldName($l);$Y=$_POST["fields"][$E];if($Y!=""){if($l["type"]=="enum")$Y=+$Y;if($l["type"]=="set")$Y=array_sum($Y);}input($l,$Y,(string)$_POST["function"][$E]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$E=$_GET["name"];$L=$_POST;if($_POST&&!$k&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){$C=($_POST["drop"]?'Foreign key has been dropped.':($E!=""?'Foreign key has been altered.':'Foreign key has been created.'));$A=ME."table=".urlencode($a);$L["source"]=array_filter($L["source"],'strlen');ksort($L["source"]);$Vf=array();foreach($L["source"]as$y=>$X)$Vf[$y]=$L["target"][$y];$L["target"]=$Vf;if($x=="sqlite")queries_redirect($A,$C,recreate_table($a,$a,array(),array(),array(" $E"=>($_POST["drop"]?"":" ".format_foreign_key($L)))));else{$sa="ALTER TABLE ".table($a);$Fb="\nDROP ".($x=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($E);if($_POST["drop"])query_redirect($sa.$Fb,$A,$C);else{query_redirect($sa.($E!=""?"$Fb,":"")."\nADD".format_foreign_key($L),$A,$C);$k='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.'."<br>$k";}}}page_header('Foreign key',$k,array("table"=>$a),h($a));if($_POST){ksort($L["source"]);if($_POST["add"])$L["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$L["target"]=array();}elseif($E!=""){$tc=foreign_keys($a);$L=$tc[$E];$L["source"][]="";}else{$L["table"]=$a;$L["source"]=array("");}$_f=array_keys(fields($a));$Vf=($a===$L["table"]?$_f:array_keys(fields($L["table"])));$Xe=array_keys(array_filter(table_status('',true),'fk_support'));echo'
<form action="" method="post">
<p>
';if($L["db"]==""&&$L["ns"]==""){echo'Target table:
',html_select("table",$Xe,$L["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table cellspacing="0">
<thead><tr><th>Source<th>Target</thead>
';$w=0;foreach($L["source"]as$y=>$X){echo"<tr>","<td>".html_select("source[".(+$y)."]",array(-1=>"")+$_f,$X,($w==count($L["source"])-1?"foreignAddRow(this);":1)),"<td>".html_select("target[".(+$y)."]",$Vf,$L["target"][$y]);$w++;}echo'</table>
<p>
ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",$Xd),$L["on_delete"]),' ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",$Xd),$L["on_update"]),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"ms174979.aspx",'oracle'=>"clauses002.htm#sthref2903",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';}if($E!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$L=$_POST;if($_POST&&!$k){$E=trim($L["name"]);$ua=" AS\n$L[select]";$A=ME."table=".urlencode($E);$C='View has been altered.';if($_GET["materialized"])$U="MATERIALIZED VIEW";else{$U="VIEW";if($x=="pgsql"){$Df=table_status($E);$U=($Df?strtoupper($Df["Engine"]):$U);}}if(!$_POST["drop"]&&$a==$E&&$x!="sqlite"&&$U!="MATERIALIZED VIEW")query_redirect(($x=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($E).$ua,$A,$C);else{$Xf=$E."_adminer_".uniqid();drop_create("DROP $U ".table($a),"CREATE $U ".table($E).$ua,"DROP $U ".table($E),"CREATE $U ".table($Xf).$ua,"DROP $U ".table($Xf),($_POST["drop"]?substr(ME,0,-1):$A),'View has been dropped.',$C,'View has been created.',$a,$E);}}if(!$_POST&&$a!=""){$L=view($a);$L["name"]=$a;if(!$k)$k=error();}page_header(($a!=""?'Alter view':'Create view'),$k,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($L["name"]),'" maxlength="64" autocapitalize="off">
<p>';textarea("select",$L["select"]);echo'<p>
<input type="submit" value="Save">
';if($_GET["view"]!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Sc=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Ef=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$L=$_POST;if($_POST&&!$k){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($L["INTERVAL_FIELD"],$Sc)&&isset($Ef[$L["STATUS"]])){$nf="\nON SCHEDULE ".($L["INTERVAL_VALUE"]?"EVERY ".q($L["INTERVAL_VALUE"])." $L[INTERVAL_FIELD]".($L["STARTS"]?" STARTS ".q($L["STARTS"]):"").($L["ENDS"]?" ENDS ".q($L["ENDS"]):""):"AT ".q($L["STARTS"]))." ON COMPLETION".($L["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$nf.($aa!=$L["EVENT_NAME"]?"\nRENAME TO ".idf_escape($L["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($L["EVENT_NAME"]).$nf)."\n".$Ef[$L["STATUS"]]." COMMENT ".q($L["EVENT_COMMENT"]).rtrim(" DO\n$L[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$k);if(!$L&&$aa!=""){$M=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$L=reset($M);}echo'
<form action="" method="post">
<table cellspacing="0">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($L["EVENT_NAME"]),'" maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$L[EXECUTE_AT]$L[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($L["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($L["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$Sc,$L["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Ef,$L["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($L["EVENT_COMMENT"]),'" maxlength="64">
<tr><th>&nbsp;<td>',checkbox("ON_COMPLETION","PRESERVE",$L["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$L["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=$_GET["procedure"];$jf=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$L=$_POST;$L["fields"]=(array)$L["fields"];if($_POST&&!process_fields($L["fields"])&&!$k){$Xf="$L[name]_adminer_".uniqid();drop_create("DROP $jf ".idf_escape($da),create_routine($jf,$L),"DROP $jf ".idf_escape($L["name"]),create_routine($jf,array("name"=>$Xf)+$L),"DROP $jf ".idf_escape($Xf),substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$da,$L["name"]);}page_header(($da!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($da):(isset($_GET["function"])?'Create function':'Create procedure')),$k);if(!$_POST&&$da!=""){$L=routine($da,$jf);$L["name"]=$da;}$Xa=get_vals("SHOW CHARACTER SET");sort($Xa);$kf=routine_languages();echo'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($L["name"]),'" maxlength="64" autocapitalize="off">
',($kf?'Language'.": ".html_select("language",$kf,$L["language"]):""),'<input type="submit" value="Save">
<table cellspacing="0" class="nowrap">
';edit_fields($L["fields"],$Xa,$jf);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$L["returns"],$Xa);}echo'</table>
<p>';textarea("definition",$L["definition"]);echo'<p>
<input type="submit" value="Save">
';if($da!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$E=$_GET["name"];$pg=trigger_options();$L=(array)trigger($E)+array("Trigger"=>$a."_bi");if($_POST){if(!$k&&in_array($_POST["Timing"],$pg["Timing"])&&in_array($_POST["Event"],$pg["Event"])&&in_array($_POST["Type"],$pg["Type"])){$Wd=" ON ".table($a);$Fb="DROP TRIGGER ".idf_escape($E).($x=="pgsql"?$Wd:"");$A=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($Fb,$A,'Trigger has been dropped.');else{if($E!="")queries($Fb);queries_redirect($A,($E!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($Wd,$_POST)));if($E!="")queries(create_trigger($Wd,$L+array("Type"=>reset($pg["Type"]))));}}$L=$_POST;}page_header(($E!=""?'Alter trigger'.": ".h($E):'Create trigger'),$k,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table cellspacing="0">
<tr><th>Time<td>',html_select("Timing",$pg["Timing"],$L["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',html_select("Event",$pg["Event"],$L["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$pg["Event"])?" <input name='Of' value='".h($L["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',html_select("Type",$pg["Type"],$L["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($L["Trigger"]);?>" maxlength="64" autocapitalize="off">
<script type="text/javascript">document.getElementById('form')['Timing'].onchange();</script>
<p><?php textarea("Statement",$L["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($E!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["user"])){$fa=$_GET["user"];$Me=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$L){foreach(explode(",",($L["Privilege"]=="Grant option"?"":$L["Context"]))as$hb)$Me[$hb][$L["Privilege"]]=$L["Comment"];}$Me["Server Admin"]+=$Me["File access on server"];$Me["Databases"]["Create routine"]=$Me["Procedures"]["Create routine"];unset($Me["Procedures"]["Create routine"]);$Me["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$Me["Columns"][$X]=$Me["Tables"][$X];unset($Me["Server Admin"]["Usage"]);foreach($Me["Tables"]as$y=>$X)unset($Me["Databases"][$y]);$Jd=array();if($_POST){foreach($_POST["objects"]as$y=>$X)$Jd[$X]=(array)$Jd[$X]+(array)$_POST["grants"][$y];}$zc=array();$Ud="";if(isset($_GET["host"])&&($J=$e->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])))){while($L=$J->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$L[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\\([^)]+\\))?~',$B[1],$td,PREG_SET_ORDER)){foreach($td
as$X){if($X[1]!="USAGE")$zc["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$L[0]))$zc["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$L[0],$B))$Ud=$B[1];}}if($_POST&&!$k){$Vd=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $Vd",ME."privileges=",'User has been dropped.');else{$Ld=q($_POST["user"])."@".q($_POST["host"]);$xe=$_POST["pass"];if($xe!=''&&!$_POST["hashed"]){$xe=$e->result("SELECT PASSWORD(".q($xe).")");$k=!$xe;}$lb=false;if(!$k){if($Vd!=$Ld){$lb=queries(($e->server_info<5?"GRANT USAGE ON *.* TO":"CREATE USER")." $Ld IDENTIFIED BY PASSWORD ".q($xe));$k=!$lb;}elseif($xe!=$Ud)queries("SET PASSWORD FOR $Ld = ".q($xe));}if(!$k){$gf=array();foreach($Jd
as$Pd=>$q){if(isset($_GET["grant"]))$q=array_filter($q);$q=array_keys($q);if(isset($_GET["grant"]))$gf=array_diff(array_keys(array_filter($Jd[$Pd],'strlen')),$q);elseif($Vd==$Ld){$Sd=array_keys((array)$zc[$Pd]);$gf=array_diff($Sd,$q);$q=array_diff($q,$Sd);unset($zc[$Pd]);}if(preg_match('~^(.+)\\s*(\\(.*\\))?$~U',$Pd,$B)&&(!grant("REVOKE",$gf,$B[2]," ON $B[1] FROM $Ld")||!grant("GRANT",$q,$B[2]," ON $B[1] TO $Ld"))){$k=true;break;}}}if(!$k&&isset($_GET["host"])){if($Vd!=$Ld)queries("DROP USER $Vd");elseif(!isset($_GET["grant"])){foreach($zc
as$Pd=>$gf){if(preg_match('~^(.+)(\\(.*\\))?$~U',$Pd,$B))grant("REVOKE",array_keys($gf),$B[2]," ON $B[1] FROM $Ld");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$k);if($lb)$e->query("DROP USER $Ld");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$k,array("privileges"=>array('','Privileges')));if($_POST){$L=$_POST;$zc=$Jd;}else{$L=$_GET+array("host"=>$e->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$L["pass"]=$Ud;if($Ud!="")$L["hashed"]=true;$zc[(DB==""||$zc?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0">
<tr><th>Server<td><input name="host" maxlength="60" value="',h($L["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" maxlength="16" value="',h($L["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($L["pass"]),'">
';if(!$L["hashed"]){echo'<script type="text/javascript">typePassword(document.getElementById(\'pass\'));</script>';}echo
checkbox("hashed",1,$L["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$s=0;foreach($zc
as$Pd=>$q){echo'<th>'.($Pd!="*.*"?"<input name='objects[$s]' value='".h($Pd)."' size='10' autocapitalize='off'>":"<input type='hidden' name='objects[$s]' value='*.*' size='10'>*.*");$s++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$hb=>$xb){foreach((array)$Me[$hb]as$Le=>$bb){echo"<tr".odd()."><td".($xb?">$xb<td":" colspan='2'").' lang="en" title="'.h($bb).'">'.h($Le);$s=0;foreach($zc
as$Pd=>$q){$E="'grants[$s][".h(strtoupper($Le))."]'";$Y=$q[strtoupper($Le)];if($hb=="Server Admin"&&$Pd!=(isset($zc["*.*"])?"*.*":".*"))echo"<td>&nbsp;";elseif(isset($_GET["grant"]))echo"<td><select name=$E><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'><input type='checkbox' name=$E value='1'".($Y?" checked":"").($Le=="All privileges"?" id='grants-$s-all'":($Le=="Grant option"?"":" onclick=\"if (this.checked) formUncheck('grants-$s-all');\""))."></label>";$s++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")&&$_POST&&!$k){$dd=0;foreach((array)$_POST["kill"]as$X){if(queries("KILL ".number($X)))$dd++;}queries_redirect(ME."processlist=",lang(array('%d process has been killed.','%d processes have been killed.'),$dd),$dd||!$_POST["kill"]);}page_header('Process list',$k);echo'
<form action="" method="post">
<table cellspacing="0" onclick="tableClick(event);" ondblclick="tableClick(event, true);" class="nowrap checkable">
';$s=-1;foreach(process_list()as$s=>$L){if(!$s){echo"<thead><tr lang='en'>".(support("kill")?"<th>&nbsp;":"");foreach($L
as$y=>$X)echo"<th>$y".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($y),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"../b14237/dynviews_2088.htm",));echo"</thead>\n";}echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$L["Id"],0):"");foreach($L
as$y=>$X)echo"<td>".(($x=="sql"&&$y=="Info"&&preg_match("~Query|Killed~",$L["Command"])&&$X!="")||($x=="pgsql"&&$y=="current_query"&&$X!="<IDLE>")||($x=="oracle"&&$y=="sql_text"&&$X!="")?"<code class='jush-$x'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($L["db"]!=""?"db=".urlencode($L["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>':nbsp($X));echo"\n";}echo'</table>
<script type=\'text/javascript\'>tableCheck();</script>
<p>
';if(support("kill")){echo($s+1)."/".sprintf('%d in total',$e->result("SELECT @@max_connections")),"<p><input type='submit' value='".'Kill'."'>\n";}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["select"])){$a=$_GET["select"];$R=table_status1($a);$v=indexes($a);$m=fields($a);$tc=column_foreign_keys($a);$Rd="";if($R["Oid"]){$Rd=($x=="sqlite"?"rowid":"oid");$v[]=array("type"=>"PRIMARY","columns"=>array($Rd));}parse_str($_COOKIE["adminer_import"],$ma);$hf=array();$d=array();$ag=null;foreach($m
as$y=>$l){$E=$b->fieldName($l);if(isset($l["privileges"]["select"])&&$E!=""){$d[$y]=html_entity_decode(strip_tags($E),ENT_QUOTES);if(is_shortable($l))$ag=$b->selectLengthProcess();}$hf+=$l["privileges"];}list($N,$r)=$b->selectColumnsProcess($d,$v);$Wc=count($r)<count($N);$Z=$b->selectSearchProcess($m,$v);$fe=$b->selectOrderProcess($m,$v);$z=$b->selectLimitProcess();$xc=($N?implode(", ",$N):"*".($Rd?", $Rd":"")).convert_fields($d,$m,$N)."\nFROM ".table($a);$_c=($r&&$Wc?"\nGROUP BY ".implode(", ",$r):"").($fe?"\nORDER BY ".implode(", ",$fe):"");if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$xg=>$L){$ua=convert_field($m[key($L)]);$N=array($ua?$ua:idf_escape(key($L)));$Z[]=where_check($xg,$m);$K=$j->select($a,$N,$Z,$N);if($K)echo
reset($K->fetch_row());}exit;}if($_POST&&!$k){$Pg=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$Oa=array();foreach($_POST["check"]as$Ma)$Oa[]=where_check($Ma,$m);$Pg[]="((".implode(") OR (",$Oa)."))";}$Pg=($Pg?"\nWHERE ".implode(" AND ",$Pg):"");$Ie=$zg=null;foreach($v
as$u){if($u["type"]=="PRIMARY"){$Ie=array_flip($u["columns"]);$zg=($N?$Ie:array());break;}}foreach((array)$zg
as$y=>$X){if(in_array(idf_escape($y),$N))unset($zg[$y]);}if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");if(!is_array($_POST["check"])||$zg===array())$I="SELECT $xc$Pg$_c";else{$vg=array();foreach($_POST["check"]as$X)$vg[]="(SELECT".limit($xc,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$m).$_c,1).")";$I=implode(" UNION ALL ",$vg);}$b->dumpData($a,"table",$I);exit;}if(!$b->selectEmailProcess($Z,$tc)){if($_POST["save"]||$_POST["delete"]){$J=true;$na=0;$P=array();if(!$_POST["delete"]){foreach($d
as$E=>$X){$X=process_input($m[$E]);if($X!==null&&($_POST["clone"]||$X!==false))$P[idf_escape($E)]=($X!==false?$X:idf_escape($E));}}if($_POST["delete"]||$P){if($_POST["clone"])$I="INTO ".table($a)." (".implode(", ",array_keys($P)).")\nSELECT ".implode(", ",$P)."\nFROM ".table($a);if($_POST["all"]||($zg===array()&&is_array($_POST["check"]))||$Wc){$J=($_POST["delete"]?$j->delete($a,$Pg):($_POST["clone"]?queries("INSERT $I$Pg"):$j->update($a,$P,$Pg)));$na=$e->affected_rows;}else{foreach((array)$_POST["check"]as$X){$Og="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$m);$J=($_POST["delete"]?$j->delete($a,$Og,1):($_POST["clone"]?queries("INSERT".limit1($I,$Og)):$j->update($a,$P,$Og)));if(!$J)break;$na+=$e->affected_rows;}}}$C=lang(array('%d item has been affected.','%d items have been affected.'),$na);if($_POST["clone"]&&$J&&$na==1){$hd=last_id();if($hd)$C=sprintf('Item%s has been inserted.'," $hd");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$C,$J);if(!$_POST["delete"]){edit_form($a,$m,(array)$_POST["fields"],!$_POST["clone"]);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$k='Ctrl+click on a value to modify it.';else{$J=true;$na=0;foreach($_POST["val"]as$xg=>$L){$P=array();foreach($L
as$y=>$X){$y=bracket_escape($y,1);$P[idf_escape($y)]=(preg_match('~char|text~',$m[$y]["type"])||$X!=""?$b->processInput($m[$y],$X):"NULL");}$J=$j->update($a,$P," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($xg,$m),!($Wc||$zg===array())," ");if(!$J)break;$na+=$e->affected_rows;}queries_redirect(remove_from_uri(),lang(array('%d item has been affected.','%d items have been affected.'),$na),$J);}}elseif(!is_string($mc=get_file("csv_file",true)))$k=upload_error($mc);elseif(!preg_match('~~u',$mc))$k='File must be in UTF-8 encoding.';else{cookie("adminer_import","output=".urlencode($ma["output"])."&format=".urlencode($_POST["separator"]));$J=true;$Ya=array_keys($m);preg_match_all('~(?>"[^"]*"|[^"\\r\\n]+)+~',$mc,$td);$na=count($td[0]);$j->begin();$tf=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$M=array();foreach($td[0]as$y=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$tf]*)$tf~",$X.$tf,$ud);if(!$y&&!array_diff($ud[1],$Ya)){$Ya=$ud[1];$na--;}else{$P=array();foreach($ud[1]as$s=>$Ua)$P[idf_escape($Ya[$s])]=($Ua==""&&$m[$Ya[$s]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$Ua))));$M[]=$P;}}$J=(!$M||$j->insertUpdate($a,$M,$Ie));if($J)$j->commit();queries_redirect(remove_from_uri("page"),lang(array('%d row has been imported.','%d rows have been imported.'),$na),$J);$j->rollback();}}}$Pf=$b->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Pf",$k);$P=null;if(isset($hf["insert"])||!support("table")){$P="";foreach((array)$_GET["where"]as$X){if(count($tc[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!preg_match('~[_%]~',$X["val"]))))$P.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($R,$P);if(!$d&&support("table"))echo"<p class='error'>".'Unable to select the table'.($m?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">',"</div>\n";$b->selectColumnsPrint($N,$d);$b->selectSearchPrint($Z,$d,$v);$b->selectOrderPrint($fe,$d,$v);$b->selectLimitPrint($z);$b->selectLengthPrint($ag);$b->selectActionPrint($v);echo"</form>\n";$F=$_GET["page"];if($F=="last"){$wc=$e->result(count_rows($a,$Z,$Wc,$r));$F=floor(max(0,$wc-1)/$z);}$qf=$N;if(!$qf){$qf[]="*";if($Rd)$qf[]=$Rd;}$ib=convert_fields($d,$m,$N);if($ib)$qf[]=substr($ib,2);$J=$j->select($a,$qf,$Z,$r,$fe,$z,$F,true);if(!$J)echo"<p class='error'>".error()."\n";else{if($x=="mssql"&&$F)$J->seek($z*$F);$Rb=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$M=array();while($L=$J->fetch_assoc()){if($F&&$x=="oracle")unset($L["RNUM"]);$M[]=$L;}if($_GET["page"]!="last"&&+$z&&$r&&$Wc&&$x=="sql")$wc=$e->result(" SELECT FOUND_ROWS()");if(!$M)echo"<p class='message'>".'No rows.'."\n";else{$Ba=$b->backwardKeys($a,$Pf);echo"<table id='table' cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);' onkeydown='return editingKeydown(event);'>\n","<thead><tr>".(!$r&&$N?"":"<td><input type='checkbox' id='all-page' onclick='formCheck(this, /check/);'> <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$Id=array();$yc=array();reset($N);$Ue=1;foreach($M[0]as$y=>$X){if($y!=$Rd){$X=$_GET["columns"][key($N)];$l=$m[$N?($X?$X["col"]:current($N)):$y];$E=($l?$b->fieldName($l,$Ue):($X["fun"]?"*":$y));if($E!=""){$Ue++;$Id[$y]=$E;$c=idf_escape($y);$Ic=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($y);$xb="&desc%5B0%5D=1";echo'<th onmouseover="columnMouse(this);" onmouseout="columnMouse(this, \' hidden\');">','<a href="'.h($Ic.($fe[0]==$c||$fe[0]==$y||(!$fe&&$Wc&&$r[0]==$c)?$xb:'')).'">';echo
apply_sql_function($X["fun"],$E)."</a>";echo"<span class='column hidden'>","<a href='".h($Ic.$xb)."' title='".'descending'."' class='text'> ↓</a>";if(!$X["fun"])echo'<a href="#fieldset-search" onclick="selectSearch(\''.h(js_escape($y)).'\'); return false;" title="'.'Search'.'" class="text jsonly"> =</a>';echo"</span>";}$yc[$y]=$X["fun"];next($N);}}$nd=array();if($_GET["modify"]){foreach($M
as$L){foreach($L
as$y=>$X)$nd[$y]=max($nd[$y],min(40,strlen(utf8_decode($X))));}}echo($Ba?"<th>".'Relations':"")."</thead>\n";if(is_ajax()){if($z%2==1&&$F%2==1)odd();ob_end_clean();}foreach($b->rowDescriptions($M,$tc)as$D=>$L){$wg=unique_array($M[$D],$v);if(!$wg){$wg=array();foreach($M[$D]as$y=>$X){if(!preg_match('~^(COUNT\\((\\*|(DISTINCT )?`(?:[^`]|``)+`)\\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\\(`(?:[^`]|``)+`\\))$~',$y))$wg[$y]=$X;}}$xg="";foreach($wg
as$y=>$X){if(($x=="sql"||$x=="pgsql")&&strlen($X)>64){$y=(strpos($y,'(')?$y:idf_escape($y));$y="MD5(".($x=='sql'&&preg_match("~^utf8_~",$m[$y]["collation"])?$y:"CONVERT($y USING ".charset($e).")").")";$X=md5($X);}$xg.="&".($X!==null?urlencode("where[".bracket_escape($y)."]")."=".urlencode($X):"null%5B%5D=".urlencode($y));}echo"<tr".odd().">".(!$r&&$N?"":"<td>".checkbox("check[]",substr($xg,1),in_array(substr($xg,1),(array)$_POST["check"]),"","this.form['all'].checked = false; formUncheck('all-page');").($Wc||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$xg)."'>".'edit'."</a>"));foreach($L
as$y=>$X){if(isset($Id[$y])){$l=$m[$y];if($X!=""&&(!isset($Rb[$y])||$Rb[$y]!=""))$Rb[$y]=(is_mail($X)?$Id[$y]:"");$_="";if(preg_match('~blob|bytea|raw|file~',$l["type"])&&$X!="")$_=ME.'download='.urlencode($a).'&field='.urlencode($y).$xg;if(!$_&&$X!==null){foreach((array)$tc[$y]as$n){if(count($tc[$y])==1||end($n["source"])==$y){$_="";foreach($n["source"]as$s=>$_f)$_.=where_link($s,$n["target"][$s],$M[$D][$_f]);$_=($n["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\\1'.urlencode($n["db"]),ME):ME).'select='.urlencode($n["table"]).$_;if(count($n["source"])==1)break;}}}if($y=="COUNT(*)"){$_=ME."select=".urlencode($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$wg))$_.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($wg
as$ad=>$W)$_.=where_link($s++,$ad,$W);}$X=select_value($X,$_,$l,$ag);$t=h("val[$xg][".bracket_escape($y)."]");$Y=$_POST["val"][$xg][bracket_escape($y)];$Mb=!is_array($L[$y])&&is_utf8($X)&&$M[$D][$y]==$L[$y]&&!$yc[$y];$Zf=preg_match('~text|lob~',$l["type"]);if(($_GET["modify"]&&$Mb)||$Y!==null){$Bc=h($Y!==null?$Y:$L[$y]);echo"<td>".($Zf?"<textarea name='$t' cols='30' rows='".(substr_count($L[$y],"\n")+1)."'>$Bc</textarea>":"<input name='$t' value='$Bc' size='$nd[$y]'>");}else{$sd=strpos($X,"<i>...</i>");echo"<td id='$t' onclick=\"selectClick(this, event, ".($sd?2:($Zf?1:0)).($Mb?"":", '".h('Use edit link to modify this value.')."'").");\">$X";}}}if($Ba)echo"<td>";$b->backwardKeysPrint($Ba,$M[$D]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n";}if(($M||$F)&&!is_ajax()){$bc=true;if($_GET["page"]!="last"){if(!+$z)$wc=count($M);elseif($x!="sql"||!$Wc){$wc=($Wc?false:found_rows($R,$Z));if($wc<max(1e4,2*($F+1)*$z))$wc=reset(slow_query(count_rows($a,$Z,$Wc,$r)));else$bc=false;}}if(+$z&&($wc===false||$wc>$z||$F)){echo"<p class='pages'>";$wd=($wc===false?$F+(count($M)>=$z?2:1):floor(($wc-1)/$z));if($x!="simpledb"){echo'<a href="'.h(remove_from_uri("page"))."\" onclick=\"pageClick(this.href, +prompt('".'Page'."', '".($F+1)."'), event); return false;\">".'Page'."</a>:",pagination(0,$F).($F>5?" ...":"");for($s=max(1,$F-4);$s<min($wd,$F+5);$s++)echo
pagination($s,$F);if($wd>0){echo($F+5<$wd?" ...":""),($bc&&$wc!==false?pagination($wd,$F):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$wd'>".'last'."</a>");}echo(($wc===false?count($M)+1:$wc-$F*$z)>$z?' <a href="'.h(remove_from_uri("page")."&page=".($F+1)).'" onclick="return !selectLoadMore(this, '.(+$z).', \''.'Loading'.'...\');" class="loadmore">'.'Load more data'.'</a>':'');}else{echo'Page'.":",pagination(0,$F).($F>1?" ...":""),($F?pagination($F,$F):""),($wd>$F?pagination($F+1,$F).($wd>$F+1?" ...":""):"");}}echo"<p class='count'>\n",($wc!==false?"(".($bc?"":"~ ").lang(array('%d row','%d rows'),$wc).") ":"");$Bb=($bc?"":"~ ").$wc;echo
checkbox("all",1,0,'whole result',"var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$Bb' : checked); selectCount('selected2', this.checked || !checked ? '$Bb' : checked);")."\n";if($b->selectCommandPrint()){echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete"',confirm(),'>
</div></fieldset>
';}$uc=$b->dumpFormat();foreach((array)$_GET["columns"]as$c){if($c["fun"]){unset($uc['sql']);break;}}if($uc){print_fieldset("export",'Export'." <span id='selected2'></span>");$oe=$b->dumpOutput();echo($oe?html_select("output",$oe,$ma["output"])." ":""),html_select("format",$uc,$ma["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}echo(!$r&&$N?"":"<script type='text/javascript'>tableCheck();</script>\n");}if($b->selectImportPrint()){print_fieldset("import",'Import',!$M);echo"<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$ma["format"],1);echo" <input type='submit' name='import' value='".'Import'."'>","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($Rb,'strlen'),$d);echo"<p><input type='hidden' name='token' value='$T'></p>\n","</form>\n";}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$Df=isset($_GET["status"]);page_header($Df?'Status':'Variables');$Ig=($Df?show_status():show_variables());if(!$Ig)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($Ig
as$y=>$X){echo"<tr>","<th><code class='jush-".$x.($Df?"status":"set")."'>".h($y)."</code>","<td>".nbsp($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Mf=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$E=>$R){json_row("Comment-$E",nbsp($R["Comment"]));if(!is_view($R)){foreach(array("Engine","Collation")as$y)json_row("$y-$E",nbsp($R[$y]));foreach($Mf+array("Auto_increment"=>0,"Rows"=>0)as$y=>$X){if($R[$y]!=""){$X=format_number($R[$y]);json_row("$y-$E",($y=="Rows"&&$X&&$R["Engine"]==($Bf=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Mf[$y]))$Mf[$y]+=($R["Engine"]!="InnoDB"||$y!="Data_free"?$R[$y]:0);}elseif(array_key_exists($y,$R))json_row("$y-$E");}}}foreach($Mf
as$y=>$X)json_row("sum-$y",format_number($X));json_row("");}elseif($_GET["script"]=="kill")$e->query("KILL ".number($_POST["kill"]));else{foreach(count_tables($b->databases())as$i=>$X){json_row("tables-$i",$X);json_row("size-$i",db_size($i));}json_row("");}exit;}else{$Uf=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Uf&&!$k&&!$_POST["search"]){$J=true;$C="";if($x=="sql"&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$J=truncate_tables($_POST["tables"]);$C='Tables have been truncated.';}elseif($_POST["move"]){$J=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$C='Tables have been moved.';}elseif($_POST["copy"]){$J=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$C='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$J=drop_views($_POST["views"]);if($J&&$_POST["tables"])$J=drop_tables($_POST["tables"]);$C='Tables have been dropped.';}elseif($x!="sql"){$J=($x=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$C='Tables have been optimized.';}elseif(!$_POST["tables"])$C='No tables.';elseif($J=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"])))){while($L=$J->fetch_assoc())$C.="<b>".h($L["Table"])."</b>: ".h($L["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$C,$J);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$k,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Tf=tables_list();if(!$Tf)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'> <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!="")search_tables();}echo"<table cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);'>\n",'<thead><tr class="wrap"><td><input id="check-all" type="checkbox" onclick="formCheck(this, /^(tables|views)\[/);">';$Cb=doc_link(array('sql'=>'show-table-status.html'));echo'<th>'.'Table','<td>'.'Engine'.doc_link(array('sql'=>'storage-engines.html')),'<td>'.'Collation'.doc_link(array('sql'=>'charset-mysql.html')),'<td>'.'Data Length'.$Cb,'<td>'.'Index Length'.$Cb,'<td>'.'Data Free'.$Cb,'<td>'.'Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html')),'<td>'.'Rows'.$Cb,(support("comment")?'<td>'.'Comment'.$Cb:''),"</thead>\n";$S=0;foreach($Tf
as$E=>$U){$Kg=($U!==null&&!preg_match('~table~i',$U));echo'<tr'.odd().'><td>'.checkbox(($Kg?"views[]":"tables[]"),$E,in_array($E,$Uf,true),"","formUncheck('check-all');"),'<th>'.(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($E).'" title="'.'Show structure'.'">'.h($E).'</a>':h($E));if($Kg){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($E).'" title="'.'Alter view'.'">'.(preg_match('~materialized~i',$U)?'Materialized View':'View').'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($E).'" title="'.'Select data'.'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$y=>$_){$t=" id='$y-".h($E)."'";echo($_?"<td align='right'>".(support("table")||$y=="Rows"||(support("indexes")&&$y!="Data_length")?"<a href='".h(ME."$_[0]=").urlencode($E)."'$t title='$_[1]'>?</a>":"<span$t>?</span>"):"<td id='$y-".h($E)."'>&nbsp;");}$S++;}echo(support("comment")?"<td id='Comment-".h($E)."'>&nbsp;":"");}echo"<tr><td>&nbsp;<th>".sprintf('%d in total',count($Tf)),"<td>".nbsp($x=="sql"?$e->result("SELECT @@storage_engine"):""),"<td>".nbsp(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$y)echo"<td align='right' id='sum-$y'>&nbsp;";echo"</table>\n";if(!information_schema(DB)){$Gg="<input type='submit' value='".'Vacuum'."'".on_help("'VACUUM'")."> ";$ce="<input type='submit' name='optimize' value='".'Optimize'."'".on_help($x=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'")."> ";echo"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>".($x=="sqlite"?$Gg:($x=="pgsql"?$Gg.$ce:($x=="sql"?"<input type='submit' value='".'Analyze'."'".on_help("'ANALYZE TABLE'")."> ".$ce."<input type='submit' name='check' value='".'Check'."'".on_help("'CHECK TABLE'")."> "."<input type='submit' name='repair' value='".'Repair'."'".on_help("'REPAIR TABLE'")."> ":"")))."<input type='submit' name='truncate' value='".'Truncate'."'".confirm().on_help($x=="sqlite"?"'DELETE'":"'TRUNCATE".($x=="pgsql"?"'":" TABLE'"))."> "."<input type='submit' name='drop' value='".'Drop'."'".confirm().on_help("'DROP TABLE'").">\n";$h=(support("scheme")?$b->schemas():$b->databases());if(count($h)!=1&&$x!="sqlite"){$i=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".'Move to other database'.": ",($h?html_select("target",$h,$i):'<input name="target" value="'.h($i).'" autocapitalize="off">')," <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'>":""),"\n";}echo"<input type='hidden' name='all' value='' onclick=\"selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $S);":"")."\">\n";echo"<input type='hidden' name='token' value='$T'>\n","</div></fieldset>\n";}echo"</form>\n","<script type='text/javascript'>tableCheck();</script>\n";}echo'<p class="links"><a href="'.h(ME).'create=">'.'Create table'."</a>\n",(support("view")?'<a href="'.h(ME).'view=">'.'Create view'."</a>\n":""),(support("materializedview")?'<a href="'.h(ME).'view=&amp;materialized=1">'.'Create materialized view'."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$lf=routines();if($lf){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td>&nbsp;</thead>\n";odd('');foreach($lf
as$L){echo'<tr'.odd().'>','<th><a href="'.h(ME).($L["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($L["ROUTINE_NAME"]).'">'.h($L["ROUTINE_NAME"]).'</a>','<td>'.h($L["ROUTINE_TYPE"]),'<td>'.h($L["DTD_IDENTIFIER"]),'<td><a href="'.h(ME).($L["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($L["ROUTINE_NAME"]).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$M=get_rows("SHOW EVENTS");if($M){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($M
as$L){echo"<tr>","<th>".h($L["Name"]),"<td>".($L["Execute at"]?'At given time'."<td>".$L["Execute at"]:'Every'." ".$L["Interval value"]." ".$L["Interval field"]."<td>$L[Starts]"),"<td>$L[Ends]",'<td><a href="'.h(ME).'event='.urlencode($L["Name"]).'">'.'Alter'.'</a>';}echo"</table>\n";$Zb=$e->result("SELECT @@event_scheduler");if($Zb&&$Zb!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Zb)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}if($Tf)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}}}page_footer();