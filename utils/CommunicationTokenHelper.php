<?php

class CommunicationTokenHelper{
	
	function getTableOfPhones(&$contactIDs, &$values, &$token_needed ){
	
		require_once('utils/CustomSearchTools.php');
		$tmp = new CustomSearchTools();
		$sql_cids = $tmp->convertArrayToSqlString($contactIDs);
			
		$sql = "select ph.contact_id,  ph.phone , ph.is_primary,
				lt.display_name as location_type_label,
				ov.label as phone_type_label
				FROM civicrm_phone ph
				LEFT JOIN civicrm_location_type lt ON ph.location_type_id = lt.id ,
				civicrm_option_value ov ,
				civicrm_option_group og
				WHERE ph.contact_id IN ( ".$sql_cids." )
				AND ph.phone_type_id = ov.value
				AND ov.option_group_id = og.id AND og.name = 'phone_type'
				ORDER BY ph.contact_id" ;
		// print "\n\n<br><br>sql: ".$sql;
		$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
			
		$first = true;
		$prev_cid = "";
			
		while($dao->fetch()){
			//print "\n\n<br>Inside while loop";
				
			$cid = $dao->contact_id ;
				
			$phone = $dao->phone;
			$is_primary = $dao->is_primary;
			$location_type_label = $dao->location_type_label;
			$phone_type_label = $dao->phone_type_label;
	
			if(strcmp( $is_primary, '1') == 0 ){
				$is_primary_formatted = 'Yes';
			}else{
				$is_primary_formatted = 'No';
			}
			 
			if( strcmp($cid, $prev_cid )  <> 0 ){
				// First phone for this contact
					
				$values[$cid][$token_needed] = "<table border=0 width='100%'><tr><th align=left>Number</th>
				   <th align=left>Is Primary?</th><th align=left>Location</th><Th align=left>Phone Type</th></tr>";
				// wrap up token for prev. cid
				if( strcmp( $prev_cid, "") <> 0){
	
					$values[$prev_cid][$token_needed] = $values[$prev_cid][$token_needed]."</table>";
	
				}
			}
	
			// add the data for this child to this parent's token
			$values[$cid][$token_needed] = $values[$cid][$token_needed]."<tr><td>$phone</td>".
					"<td>$is_primary_formatted</td><td>$location_type_label</td><td>$phone_type_label</td></tr>";
				
				
				
				
			$prev_cid = $cid;
		}
		$dao->free();
		if( strcmp( $prev_cid, "") <> 0){
	
			$values[$prev_cid][$token_needed] = $values[$prev_cid][$token_needed]."</table>";
	
		}
			
	
	
	
	}
	
	function getTableOfEmails(&$contactIDs, &$values, &$token_needed){
	
	
		require_once('utils/CustomSearchTools.php');
		$tmp = new CustomSearchTools();
		$sql_cids = $tmp->convertArrayToSqlString($contactIDs);
			
		$sql = "select em.contact_id,  em.email , em.is_primary,
				lt.display_name as location_type_label
				FROM civicrm_email em
				LEFT JOIN civicrm_location_type lt ON em.location_type_id = lt.id
				WHERE em.contact_id IN ( ".$sql_cids." )
				ORDER BY em.contact_id" ;
		// print "\n\n<br><br>sql: ".$sql;
		$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
			
		$first = true;
		$prev_cid = "";
			
		while($dao->fetch()){
			//print "\n\n<br>Inside while loop";
				
			$cid = $dao->contact_id ;
				
			$email = $dao->email;
			$is_primary = $dao->is_primary;
			$location_type_label = $dao->location_type_label;
	
	
			if(strcmp( $is_primary, '1') == 0 ){
				$is_primary_formatted = 'Yes';
			}else{
				$is_primary_formatted = 'No';
			}
			 
			if( strcmp($cid, $prev_cid )  <> 0 ){
				// First phone for this contact
					
				$values[$cid][$token_needed] = "<table border=0 width='100%'><tr><th align=left>Email</th>
				   <th align=left>Is Primary?</th><th align=left>Location</th></tr>";
				// wrap up token for prev. cid
				if( strcmp( $prev_cid, "") <> 0){
	
					$values[$prev_cid][$token_needed] = $values[$prev_cid][$token_needed]."</table>";
	
				}
			}
	
			// add the data for this child to this parent's token
			$values[$cid][$token_needed] = $values[$cid][$token_needed]."<tr><td>$email</td>".
					"<td>$is_primary_formatted</td><td>$location_type_label</td></tr>";
				
				
				
				
			$prev_cid = $cid;
		}
		$dao->free();
			
		if( strcmp( $prev_cid, "") <> 0){
	
			$values[$prev_cid][$token_needed] = $values[$prev_cid][$token_needed]."</table>";
	
		}
	
	}
	
	
	

}
