<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

 LICENSE

	This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------



/**
* Log  history 
*
* 
*
* @param $id_device
* @param $device_type
* @param $changes
**/
function history_log ($id_device,$device_type,$changes) {

		global $SEARCH_OPTION, $LINK_ID_TABLE;

		$db = new DB;
		$date_mod=date("Y-m-d H:i:s");
				
		if(!empty($changes)){
			
			// cr�er un query avec l'insertion des �l�ments fixes + changements
			$id_search_option=$changes[0];
			$old_value=$changes[1];
			$new_value=$changes[2];

				// Build query
				$query = "INSERT INTO glpi_history  VALUES ('','$id_device','$device_type','". addslashes(getUserName($_SESSION["glpiID"],$link=0))."','$date_mod','$id_search_option','$old_value','$new_value');";
				
				//echo $query;
				//echo  "<br>";
				
				$db->query($query)  or die($db->error());
				
		}
}

/**
* Construct  history for device
*
* 
*
* @param $id_device
* @param $device_type
* @param $oldvalues
* @param $newvalues
**/
function construct_history($id_device,$device_type,$key,$oldvalues,$newvalues) {
			
			global $SEARCH_OPTION, $LINK_ID_TABLE,$phproot, $lang ;

			// on ne log que les changements pas la d�finition d'un �l�ment vide
			if (!empty($oldvalues)){
			
			// n�cessaire pour avoir les $search_option
			include_once ($phproot . "/glpi/includes_search.php");
			
				// on parse le tableau $search_option, on v�rifie qu'il existe une entr�e correspondante � $key
				foreach($SEARCH_OPTION[$device_type] as $key2 => $val2){
			
					if($val2["linkfield"]==$key){
					
					$id_search_option=$key2; // on r�cupere dans $SEARCH_OPTION l'id_search_options
			
						if($val2["table"]==$LINK_ID_TABLE[$device_type]){
						// 1er cas $key est un champs normal -> on ne touche pas au valeur 
						$changes=array($id_search_option, $oldvalues,$newvalues);
						}else {
						//2�me cas $key est un champs li�, il faut r�cup�rer les valeurs du dropdown
						$changes=array($id_search_option,  addslashes(getDropdownName( $val2["table"],$oldvalues)), addslashes(getDropdownName( $val2["table"],$newvalues)));
						}

					}
				} // fin foreach
			
			history_log ($id_device,$device_type,$changes);

			} // Fin if

} // function construct_history




/**
* Log an event.
*
* Log the event $event on the glpi_event table with all the others args, if
* $level is above or equal to setting from configuration.
*
* @param $item 
* @param $itemtype
* @param $level
* @param $service
* @param $event
**/
function logEvent ($item, $itemtype, $level, $service, $event) {
	// Logs the event if level is above or equal to setting from configuration

	GLOBAL $cfg_features, $lang;
	if ($level <= $cfg_features["event_loglevel"]) { 
		$db = new DB;	
		 $query = "INSERT INTO glpi_event_log VALUES (NULL, '".addslashes($item)."', '".addslashes($itemtype)."', NOW(), '".addslashes($service)."', '".addslashes($level)."', '".addslashes($event)."')";

		$result = $db->query($query);    
	
	}
}

/**
* Return arrays for function showEvent et lastEvent
*
**/
function logArray(){

	GLOBAL $lang;

	$logItemtype=array("system"=>$lang["log"][1],
				"computers"=>$lang["log"][2],
				"monitors"=>$lang["log"][3],
		 		"printers"=>$lang["log"][4],
				"software"=>$lang["log"][5],
				"networking"=>$lang["log"][6],
				"cartridges"=>$lang["log"][7],
				"peripherals"=>$lang["log"][8],
				"consumables"=>$lang["log"][9],
				"tracking"=>$lang["log"][10],
				"contacts"=>$lang["log"][11],
				"enterprises"=>$lang["log"][12],
				"documents"=>$lang["log"][13],
				"knowbase"=>$lang["log"][14],
				"users"=>$lang["log"][15],
				"infocom"=>$lang["log"][19],
				"devices"=>$lang["log"][18],
				"links"=>$lang["log"][38],
				"typedocs"=>$lang["log"][39],
				"planning"=>$lang["log"][16],
				"reservation"=>$lang["log"][42],
				"contracts"=>$lang["log"][17]);
	
	$logService=array("inventory"=>$lang["log"][50],
				"tracking"=>$lang["log"][51],
				"planning"=>$lang["log"][52],
				"tools"=>$lang["log"][53],
				"financial"=>$lang["log"][54],
				"login"=>$lang["log"][55],
				"setup"=>$lang["log"][57],
				"setup"=>$lang["log"][58],
				"document"=>$lang["log"][56]);
	
return array($logItemtype,$logService);

}


/**
* Print a nice tab for last event from inventory section
*
* Print a great tab to present lasts events occured on glpi
*
*
* @param $target where to go when complete
* @param $order order by clause occurences (eg: ) 
* @param $sort order by clause occurences (eg: date) 
* @param $user
**/
function showAddEvents($target,$order,$sort,$user="") {
	// Show events from $result in table form

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;

	list($logItemtype,$logService)=logArray();

	// new database object
	$db = new DB;

	// define default sorting
	
	if (!$sort) {
		$sort = "date";
		$order = "DESC";
	}
	
	$usersearch="%";
	if (!empty($user))
	$usersearch=$user." ";
	
	// Query Database
	$query = "SELECT * FROM glpi_event_log WHERE message LIKE '".$usersearch.addslashes($lang["log"][20])."%' ORDER BY $sort $order LIMIT 0,".$cfg_features["num_of_events"];

	// Get results
	$result = $db->query($query);
	
	
	// Number of results
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre' width='90%'>";
		echo "<tr><th>".$lang["central"][4]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
		return;
	}
	
	// Output events
	$i = 0;

	echo "<div align='center'><br><table width='400' class='tab_cadre'>";
	echo "<tr><th colspan='5'>".$lang["central"][2]." ".$cfg_features["num_of_events"]." ".$lang["central"][8].":</th></tr>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][1]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][2]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][4]."</a></th></tr>";

	while ($i < $number) {
		$ID = $db->result($result, $i, "ID");
		$item = $db->result($result, $i, "item");
		$itemtype = $db->result($result, $i, "itemtype");
		$date = $db->result($result, $i, "date");
		$service = $db->result($result, $i, "service");
		//$level = $db->result($result, $i, "level");
		$message = $db->result($result, $i, "message");
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$logItemtype[$itemtype].":</td><td align='center'><b>";
		if ($item=="-1" || $item=="0") {
			echo $item;
		} else {
				if ($itemtype=="reservation"){
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/index.php?show=resa&amp;ID=";
				} else {
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/".$itemtype."-info-form.php?ID=";
				}
			echo $item;
			echo "\">$item</a>";
		}			
		echo "</b></td><td><span style='font-size:9px;'>".convDateTime($date)."</span></td><td align='center'>".$logService[$service]."</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></div><br>";
}

/**
* Print a nice tab for last event
*
* Print a great tab to present lasts events occured on glpi
*
*
* @param $target where to go when complete
* @param $order order by clause occurences (eg: ) 
* @param $sort order by clause occurences (eg: date) 
* @param $start
**/
function showEvents($target,$order,$sort,$start=0) {
	// Show events from $result in table form

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;

	 list($logItemtype,$logService)=logArray();
	
	// new database object
	$db = new DB;

	// define default sorting
	
	if (!$sort) {
		$sort = "date";
		$order = "DESC";
	}
	
	// Query Database
	$query = "SELECT * FROM glpi_event_log ORDER BY $sort $order";

	$query_limit = "SELECT * FROM glpi_event_log ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"];
	// Get results
	$result = $db->query($query);
	
	
	// Number of results
	$numrows = $db->numrows($result);
	$result = $db->query($query_limit);
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<div align='center'><b>".$lang["central"][4]."</b></div>";
		return;
	}
	
	// Output events
	$i = 0;

	echo "<div align='center'>";
	$parameters="sort=$sort&amp;order=$order";
	printPager($start,$numrows,$target,$parameters);

	echo "<table width='90%' class='tab_cadre'>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][1]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][2]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="level") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=level&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][3]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][4]."</a></th></tr>";

	while ($i < $number) {
		$ID = $db->result($result, $i, "ID");
		$item = $db->result($result, $i, "item");
		$itemtype = $db->result($result, $i, "itemtype");
		$date = $db->result($result, $i, "date");
		$service = $db->result($result, $i, "service");
		$level = $db->result($result, $i, "level");
		$message = $db->result($result, $i, "message");
		
		echo "<tr class='tab_bg_2'>";
		
		echo "<td>".$logItemtype[$itemtype].":</td><td align='center'><b>"; 

		//echo "<td>$itemtype:</td><td align='center'><b>";
		if ($item=="-1" || $item=="0") {
			echo $item;
		} else {
			if ($itemtype=="infocom"){
				echo "<a href='#' onClick=\"window.open('".$cfg_install["root"]."/infocoms/infocoms-show.php?ID=$item','infocoms','location=infocoms,width=750,height=600,scrollbars=no')\">$item</a>";					
			} else {
				if ($itemtype=="reservation"){
					echo "<a href=\"".$cfg_install["root"]."/$itemtype/index.php?show=resa&amp;ID=";
				} else {
					echo "<a href=\"".$cfg_install["root"]."/$itemtype/".$itemtype."-info-form.php?ID=";
				}
				echo $item;
				echo "\">$item</a>";
		   }
		}			
		echo "</b></td><td>".convDateTime($date)."</td><td align='center'>".$logService[$service]."</td><td align='center'>$level</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></div><br>";
}

?>
