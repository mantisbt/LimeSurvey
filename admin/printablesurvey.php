<?php
/*
	#############################################################
	# >>> PHP Surveyor  										#
	#############################################################
	# > Author:  Jason Cleeland									#
	# > E-mail:  jason@cleeland.org								#
	# > Mail:    Box 99, Trades Hall, 54 Victoria St,			#
	# >          CARLTON SOUTH 3053, AUSTRALIA
	# > Date: 	 20 February 2003								#
	#															#
	# This set of scripts allows you to develop, publish and	#
	# perform data-entry on surveys.							#
	#############################################################
	#															#
	#	Copyright (C) 2003  Jason Cleeland						#
	#															#
	# This program is free software; you can redistribute 		#
	# it and/or modify it under the terms of the GNU General 	#
	# Public License as published by the Free Software 			#
	# Foundation; either version 2 of the License, or (at your 	#
	# option) any later version.								#
	#															#
	# This program is distributed in the hope that it will be 	#
	# useful, but WITHOUT ANY WARRANTY; without even the 		#
	# implied warranty of MERCHANTABILITY or FITNESS FOR A 		#
	# PARTICULAR PURPOSE.  See the GNU General Public License 	#
	# for more details.											#
	#															#
	# You should have received a copy of the GNU General 		#
	# Public License along with this program; if not, write to 	#
	# the Free Software Foundation, Inc., 59 Temple Place - 	#
	# Suite 330, Boston, MA  02111-1307, USA.					#
	#############################################################	
*/

$sid = $_GET['sid'];

$boxstyle = "style='border-color: #111111; border-width: 1; border-style: solid'";
require_once("config.php");

//Get local language file
$query = "SELECT language FROM {$dbprefix}surveys WHERE sid=$sid";
$result = mysql_query($query) or die ("Couldn't get language file");
if (!isset($tpldir)) {$tpldir=$publicdir."/templates";}
while ($row=mysql_fetch_array($result)) {$surveylanguage = $row['language'];}
if (!isset($templatedir) || !$templatedir) {$thistpl=$tpldir."/default";} else {$thistpl=$tpldir."/$templatedir";}
if (!is_dir($thistpl)) {$thistpl=$tpldir."/default";}
$langdir="$publicdir/lang";
$langfilename="$langdir/$surveylanguage.lang.php";
if (!is_file($langfilename)) {$langfilename="$langdir/$defaultlang.lang.php";}
require($langfilename);	

sendcacheheaders();

echo "<html>\n<head>\n";
echo "<meta http-equiv='content-script-type' content='text/javascript' />\n";
echo "</head>\n<body>\n";

// PRESENT SURVEY DATAENTRY SCREEN

$desquery = "SELECT * FROM {$dbprefix}surveys WHERE sid=$sid";
$desresult = mysql_query($desquery);
while ($desrow = mysql_fetch_array($desresult))
	{
	$surveyname = $desrow['short_title'];
	$surveydesc = $desrow['description'];
	$surveyactive = $desrow['active'];
	$surveytable = "{$dbprefix}survey_{$desrow['sid']}";
	$surveyexpirydate = $desrow['expires'];
	$surveyfaxto = $desrow['faxto'];
	}
//if ($surveyactive == "Y") {echo "$surveyoptions\n";}
echo "<table width='100%' cellspacing='0'>\n";
echo "\t<tr>\n";
echo "\t\t<td colspan='3' align='center'><font color='black'>\n";
echo "\t\t\t<table border='1' style='border-collapse: collapse; border-color: #111111; width: 100%'>\n";
echo "\t\t\t\t<tr><td align='center'>\n";
echo "\t\t\t\t\t<font size='5' face='verdana'><b>$surveyname</b></font>\n";
echo "\t\t\t\t\t<font size='4' face='verdana'><br />$setfont$surveydesc</font>\n";
echo "\t\t\t\t</td></tr>\n";
echo "\t\t\t</table>\n";
echo "\t\t</td>\n";
echo "\t</tr>\n";
// SURVEY NAME AND DESCRIPTION TO GO HERE

$degquery = "SELECT * FROM {$dbprefix}groups WHERE sid=$sid ORDER BY group_name";
$degresult = mysql_query($degquery);
// GROUP NAME
while ($degrow = mysql_fetch_array($degresult))
	{
	$deqquery = "SELECT * FROM {$dbprefix}questions WHERE sid=$sid AND gid={$degrow['gid']} ORDER BY title";
	$deqresult = mysql_query($deqquery);
	$deqrows = array(); //Create an empty array in case mysql_fetch_array does not return any rows
	while ($deqrow = mysql_fetch_array($deqresult)) {$deqrows[] = $deqrow;} // Get table output into array
	
	// Perform a case insensitive natural sort on group name then question title of a multidimensional array
	usort($deqrows, 'CompareGroupThenTitle');
	
	echo "\t<tr>\n";
	echo "\t\t<td colspan='3' align='center' bgcolor='#EEEEEE' style='border-width: 1; border-style: double; border-color: #111111'>\n";
	echo "\t\t\t<font size='3' face='verdana'><b>{$degrow['group_name']}</b></font>\n";
	if ($degrow['description'])
		{
		echo "\t\t\t<br /><font size='2' face='verdana'>{$degrow['description']}</font>\n";
		}
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
	$gid = $degrow['gid'];
	//Alternate bgcolor for different groups
	if (!isset($bgc) || $bgc == "#EEEEEE") {$bgc = "#DDDDDD";}
	else {$bgc = "#EEEEEE";}
	
	foreach ($deqrows as $deqrow)
		{
		//GET ANY CONDITIONS THAT APPLY TO THIS QUESTION
		$explanation = ""; //reset conditions explanation
		$x=0;
		$distinctquery="SELECT DISTINCT cqid, {$dbprefix}questions.title FROM {$dbprefix}conditions, {$dbprefix}questions WHERE {$dbprefix}conditions.cqid={$dbprefix}questions.qid AND {$dbprefix}conditions.qid={$deqrow['qid']} ORDER BY cqid";
		$distinctresult=mysql_query($distinctquery);
		while ($distinctrow=mysql_fetch_array($distinctresult))
			{
			if ($x > 0) {$explanation .= " <i>"._PS_CON_JOINER."</i> ";}
			$explanation .= _PS_CON_IFYOU." ";
			$conquery="SELECT cid, cqid, {$dbprefix}questions.title,\n"
					 ."{$dbprefix}questions.question, value, {$dbprefix}questions.type,\n"
					 ."{$dbprefix}questions.lid, cfieldname\n"
					 ."FROM {$dbprefix}conditions, {$dbprefix}questions\n"
					 ."WHERE {$dbprefix}conditions.cqid={$dbprefix}questions.qid\n"
					 ."AND {$dbprefix}conditions.cqid={$distinctrow['cqid']}\n"
					 ."AND {$dbprefix}conditions.qid={$deqrow['qid']}";
			$conresult=mysql_query($conquery) or die("$conquery<br />".mysql_error());
			$conditions=array();
			while ($conrow=mysql_fetch_array($conresult))
				{
				$postans="";
				$value=$conrow['value'];
				if ($conrow['type'] == "Y")
					{
					switch ($conrow['value'])
						{
						case "Y":
							$conditions[]=_YES;
							break;
						case "N":
							$conditions[]=_NO;
						}
					}
				elseif ($conrow['type'] == "F" || $conrtow['type'] == "H")
					{
					$value=substr($conrow['cfieldname'], strpos($conrow['cfieldname'], "X".$conrow['cqid'])+strlen("X".$conrow['cqid']), strlen($conrow['cfieldname']));
					$fquery = "SELECT * FROM {$dbprefix}labels\n"
							. "WHERE lid='{$conrow['lid']}'\n"
							. "AND code='{$conrow['value']}'";
					$fresult=mysql_query($fquery) or die("$fquery<br />".mysql_error());
					while($frow=mysql_fetch_array($fresult))
						{
						$postans=$frow['title'];
						} // while
					}
				$ansquery="SELECT answer\n"
						 ."FROM {$dbprefix}answers\n"
						 ."WHERE qid='{$conrow['cqid']}'\n"
						 ."AND code='$value'";
				$ansresult=mysql_query($ansquery) or die ("$ansquery<br />ERROR!<br />".mysql_error());
				while ($ansrow=mysql_fetch_array($ansresult))
					{
					$conditions[]=$ansrow['answer']. " - ".$postans;
					}
				}
			if (count($conditions) > 1)
				{
				$explanation .=  "'".implode("' "._PS_CON_OR." '", $conditions)."'";	
				}
			else
				{
				$explanation .= "'".$conditions[0]."'";
				}
			unset($conditions);
			$explanation .= " "._PS_CON_TOQUESTION." '".$distinctrow['title']."'";
			$x++;
			}
		
		if ($explanation) 
			{
			$explanation = "["._PS_CON_ONLYANSWER." ".$explanation."]";
			echo "<tr bgcolor='$bgc'><td colspan='3'>$setfont$explanation</font></td></tr>\n";
			}
		
		//END OF GETTING CONDITIONS
		
		$qid = $deqrow['qid'];
		$fieldname = "$sid"."X"."$gid"."X"."$qid";
		echo "\t<tr bgcolor='$bgc'>\n";
		echo "\t\t<td valign='top' align='left' colspan='3'>\n";
		echo "\t\t\t<b>$setfont{$deqrow['title']}: {$deqrow['question']}</b>\n";
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		//DIFFERENT TYPES OF DATA FIELD HERE
		echo "\t<tr bgcolor='$bgc'>\n";
		echo "\t\t<td width='15%' valign='top'>\n";
		if ($deqrow['help'])
			{
			$hh = $deqrow['help'];
			echo "\t\t\t<table width='100%' border='1'><tr><td align='center'><font size='1'>$hh</td></tr></table>\n";

			}
		echo "\t\t</td>\n";
		echo "\t\t<td style='padding-left: 20px'>\n";
		switch($deqrow['type'])
			{
			case "5":  //5 POINT CHOICE
				echo "\t\t\t$setfont<u>"._PS_CHOOSEONE."</u><br />\n";
				for ($i=1; $i<=5; $i++) 
					{
					echo "\t\t\t<input type='checkbox' name='$fieldname' value='$i' />$i \n";
					}
				break;
			case "D":  //DATE
				echo "\t\t\t$setfont<u>"._PS_DATE.":</u><br />\n";
				echo "\t\t\t<input type='text' $boxstyle name='$fieldname' size='30' value='&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;' />\n";
				break;
			case "G":  //GENDER
				echo "\t\t\t$setfont<u>"._PS_CHOOSEONE.":</u><br />\n";
				echo "\t\t\t<input type='checkbox' name='$fieldname' value='F' />"._FEMALE."<br />\n";
				echo "\t\t\t<input type='checkbox' name='$fieldname' value='M' />"._MALE."<br />\n";
				break;
			case "L":  //LIST
				echo "\t\t\t$setfont<u>"._PS_CHOOSEONE.":</u><br />\n";
				$deaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$dearesult = mysql_query($deaquery);
				while ($dearow = mysql_fetch_array($dearesult))
					{
					echo "\t\t\t<input type='checkbox' name='$fieldname' value='{$dearow['code']}' />{$dearow['answer']}<br />\n";
					}
				if ($deqrow['other'] == "Y")
					{
				    echo "\t\t\t<input type='checkbox'>"._OTHER." <input type='text' size='30'><br />\n";
					}
				break;
			case "O":  //LIST WITH COMMENT
				echo "\t\t\t$setfont<u>"._PS_CHOOSEONE.":</u><br />\n";
				$deaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$dearesult = mysql_query($deaquery);
				while ($dearow = mysql_fetch_array($dearesult))
					{
					echo "\t\t\t<input type='checkbox' name='$fieldname' value='{$dearow['code']}' />{$dearow['answer']}<br />\n";
					}
				echo "\t\t\t<u>"._PS_COMMENT.":</u><br />\n";
				echo "\t\t\t<textarea $boxstyle cols='50' rows='8' name='$fieldname"."comment"."'></textarea>\n";
				break;
			case "R":  //RANKING Type Question
				$reaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$rearesult = mysql_query($reaquery) or die ("Couldn't get ranked answers<br />".mysql_error());
				$reacount = mysql_num_rows($rearesult);
				echo "\t\t\t$setfont<u>"._PS_RANKING." $reacount</u><br />\n";
				while ($rearow = mysql_fetch_array($rearesult))
					{
					echo "\t\t\t<table cellspacing='1' cellpadding='0'><tr><td width='20' height='20' bgcolor='white' style='border: solid 1 #111111'>&nbsp;</td>\n";
					echo "\t\t\t<td valign='middle'>$setfont{$rearow['answer']}</td></tr></table>\n";
					}
				break;
			case "M":  //MULTIPLE OPTIONS (Quite tricky really!)
				echo "\t\t\t$setfont<u>"._PS_CHOOSEANY."</u><br />\n";
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='Y' />{$mearow['answer']}<br />\n";
					}
				if ($deqrow['other'] == "Y")
					{
					echo "\t\t\t"._OTHER.": <input type='text' $boxstyle size='60' name='$fieldname" . "other' />\n";
					}
				break;
			case "P":  //MULTIPLE OPTIONS WITH COMMENTS
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t$setfont<u>"._PS_CHOOSEANYCOMMENT."</u><br />\n";
				echo "\t\t\t<table border='0'>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td>$setfont<input type='checkbox' name='$fieldname{$mearow['code']}' value='Y'";
					if ($mearow[3] == "Y") {echo " checked";}
					echo " />{$mearow['answer']} </td>\n";
					//This is the commments field:
					echo "\t\t\t\t\t<td>$setfont<input type='text' $boxstyle name='$fieldname{$mearow['code']}comment' size='60' /></td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			case "Q":  //MULTIPLE SHORT TEXT
				echo "\t\t\t$setfont<u>"._PS_WRITEMULTI.":</u><br />\n";
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t<table border='0'>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td>$setfont{$mearow['answer']}: <input type='text' size='60' name='$fieldname{$mearow['code']}' value=''";
					if ($mearow[3] == "Y") {echo " checked";}
					echo " /> </td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			case "S":  //SHORT TEXT
				echo "\t\t\t$setfont<u>"._PS_WRITE.":</u><br />\n";
				echo "\t\t\t<input type='text' name='$fieldname' size='60' $boxstyle />\n";
				break;
			case "T":  //LONG TEXT
				echo "\t\t\t$setfont<u>"._PS_WRITE.":</u><br />\n";
				echo "\t\t\t<textarea $boxstyle cols='50' rows='8' name='$fieldname'></textarea>\n";
				break;
			case "N":  //NUMERICAL
				echo "\t\t\t$setfont<u>"._PS_WRITE.":</u><br />\n";
				echo "\t\t\t<input type='text' size='40' $boxstyle />\n";
				break;
			case "Y":  //YES/NO
				echo "\t\t\t$setfont<u>"._PS_CHOOSEONE.":</u><br />\n";
				echo "\t\t\t<input type='checkbox' name='$fieldname' value='Y' />"._YES."<br />\n";
				echo "\t\t\t<input type='checkbox' name='$fieldname' value='N' />"._NO."<br />\n";
				break;
			case "A":  //ARRAY (5 POINT CHOICE)
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t$setfont<u>"._PS_EACHITEM."</u><br />\n";
				echo "\t\t\t<table>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td align='left'>$setfont{$mearow['answer']}</td>\n";
					echo "\t\t\t\t\t<td>$setfont";
					for ($i=1; $i<=5; $i++)
						{
						echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='$i' />$i&nbsp;\n";
						}
					echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			case "B":  //ARRAY (10 POINT CHOICE)
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t$setfont<u>"._PS_EACHITEM."</u><br />";
				echo "\t\t\t<table border='0'>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td align='left'>$setfont{$mearow['answer']}</td>\n";
					echo "\t\t\t\t\t<td>$setfont\n";
					for ($i=1; $i<=10; $i++)
						{
						echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='$i' />$i&nbsp;\n";
						}
					echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			case "C":  //ARRAY (YES/UNCERTAIN/NO)
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t$setfont<u>"._PS_EACHITEM."</u><br />\n";
				echo "\t\t\t<table>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td align='left'>$setfont{$mearow['answer']}</td>\n";
					echo "\t\t\t\t\t<td>$setfont\n";
					echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='Y'>"._YES."&nbsp;\n";
					echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='U'>"._UNCERTAIN."&nbsp;\n";
					echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='N'>"._NO."&nbsp;\n";
					echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			case "E":  //ARRAY (Increase/Same/Decrease)
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t$setfont<u>"._PS_EACHITEM."</u><br />\n";
				echo "\t\t\t<table>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td align='left'>$setfont{$mearow['answer']}</td>\n";
					echo "\t\t\t\t\t<td>$setfont\n";
					echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='I'>"._INCREASE."&nbsp;\n";
					echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='S'>"._SAME."&nbsp;\n";
					echo "\t\t\t\t\t\t<input type='checkbox' name='$fieldname{$mearow['code']}' value='D'>"._DECREASE."&nbsp;\n";
					echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			case "F": //ARRAY (Flexible Labels)
				//$headstyle="style='border-left-style: solid; border-left-width: 1px; border-left-color: #AAAAAA'";
				$headstyle="style='padding-left: 20px; padding-right: 7px'";
				$meaquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$deqrow['qid']} ORDER BY sortorder, answer";
				$mearesult = mysql_query($meaquery);
				echo "\t\t\t$setfont<u>"._PS_EACHITEM."</u><br />\n";
				echo "\t\t\t<table align='left' cellspacing='0'><tr><td></td>\n";
				$fquery = "SELECT * FROM {$dbprefix}labels WHERE lid='{$deqrow['lid']}' ORDER BY sortorder, code";
				$fresult = mysql_query($fquery);
				$fcount = mysql_num_rows($fresult);
				$fwidth = "120";
				$i=0;
				while ($frow = mysql_fetch_array($fresult))
					{
					echo "\t\t\t\t\t\t<td align='center' valign='bottom' $headstyle><font size='1'>{$frow['title']}</td>\n";
					$i++;
					}
				echo "\t\t\t\t\t\t</tr>\n";
				while ($mearow = mysql_fetch_array($mearesult))
					{
					echo "\t\t\t\t<tr>\n";
					echo "\t\t\t\t\t<td align='left'>$setfont{$mearow['answer']}</td>\n";
					//echo "\t\t\t\t\t<td>";
					for ($i=1; $i<=$fcount; $i++)
						{
						
						echo "\t\t\t\t\t<td align='center'";
						if ($i > 1) {echo " $headstyle";}
						echo ">$setfont\n";
						echo "\t\t\t\t\t\t<input type='checkbox'>\n";
						echo "\t\t\t\t\t</td>\n";
						}
					//echo "\t\t\t\t\t</tr></table></td>\n";
					echo "\t\t\t\t</tr>\n";
					}
				echo "\t\t\t</table>\n";
				break;
			}
		//echo "\t\t[$sid"."X"."$gid"."X"."$qid]\n";
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		echo "\t<tr><td height='3' colspan='3'><hr noshade size='1' color='#111111'></td></tr>\n";
		}
	}
echo "\t<tr>\n";
echo "\t\t<td colspan='3' align='center'>\n";
echo "\t\t\t<table width='100%' border='1' style='border-collapse: collapse' bordercolor='#111111'>\n";
echo "\t\t\t\t<tr>\n";
echo "\t\t\t\t\t<td align='center'>\n";
echo "\t\t\t\t\t\t$setfont<b>"._PS_SUBMIT."</b><br />\n";
echo "\t\t\t\t\t\t"._PS_THANKYOU." "._PS_FAXTO." $surveyfaxto";
if ($surveyexpirydate && $surveyexpirydate != "0000-00-00")
	{
	echo " by $surveyexpirydate";
	}
echo ".\n";
echo "\t\t\t\t\t</td>\n";
echo "\t\t\t\t</tr>\n";
echo "\t\t\t</table>\n";
echo "\t\t</td>\n";
echo "\t</tr>\n";
echo "</table>\n";
echo "</body>\n</html>";

?>