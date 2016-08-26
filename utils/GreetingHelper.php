<?php

class GreetingHelper{
	
// Deal with 'greetings.solo_casual' and 'greetings.solo_casual_nickname_only' 
	function process_solo_greetings( &$suffixes, &$prefixes, &$values, &$contactIDs , &$greetings_token_names){
		
		
		if(count( $contactIDs) ==0 ){	
			return ; 
		}
		
		$ids_for_sql = implode ( ", ", $contactIDs );
		
		$sql = "select c.id, c.first_name, c.nick_name, c.last_name,
				c.display_name, c.contact_type 
				FROM civicrm_contact c
				WHERE c.id IN ( $ids_for_sql  ) ";
		
		$dao =& CRM_Core_DAO::executeQuery( $sql );
		
		while ( $dao->fetch( ) ) {
			$first_name = $dao->first_name;
			$last_name = $dao->last_name;
			$nick_name = $dao->nick_name;
			
			$display_name = $dao->display_name;
			$contact_type = $dao->contact_type; 
			$cur_cid = $dao->id;
			
			$tmp_solo_casual_nickname_only = "";
		
			if(strlen($nick_name) > 0 ){
				$tmp_solo_casual_nickname_only = $nick_name;
			}else if(strlen($first_name) > 0 ){
				$tmp_solo_casual_nickname_only = $first_name;
			}else{
				$tmp_solo_casual_nickname_only = $display_name;
			}
			
			$tmp_solo_casual = "";
			if( $contact_type == 'Individual'){
				if(strlen($nick_name) > 0 && strlen($last_name) > 0  ){
					$tmp_solo_casual = $nick_name." ".$last_name;
				}else if(strlen($first_name) > 0 && strlen($last_name) == 0  ){
					$tmp_solo_casual = $first_name;
				}else if(strlen($first_name) > 0 && strlen($last_name) > 0){
					$tmp_solo_casual = $first_name." ".$last_name;
				}else if( strlen($nick_name) > 0 ){
					$tmp_solo_casual = $nick_name;
				}else if(strlen($first_name) > 0 ){
					$tmp_solo_casual = $first_name;
				}else{
					$tmp_solo_casual = $display_name;
				}
				
				if( strlen($tmp_solo_casual) == 0){
					$tmp_solo_casual = $display_name;
					
				}
				if( strlen($nick_name) > 0){
					$tmp_solo_nickname_only = $nick_name;
				}else if( strlen($first_name) > 0){
					$tmp_solo_nickname_only = $first_name;
				}else{
					$tmp_solo_nickname_only =  $display_name;
				}
				

				if( strlen($tmp_solo_nickname_only) == 0){
					$tmp_solo_nickname_only = $display_name;
						
				}
			}else{
				// ie an organization or a household.
				if( strlen($nick_name) > 0  ){
					$tmp_solo_casual = $nick_name; 
					$tmp_solo_nickname_only = $nick_name;
				}else{
					$tmp_solo_casual = $display_name;
					$tmp_solo_nickname_only =  $display_name;
				}
				
			}
			
			if(array_key_exists($cur_cid,  $values)){
				
				$values[$cur_cid]['greetings.solo_casual'] = $tmp_solo_casual;
				$values[$cur_cid]['greetings.solo_casual_nickname_only'] = $tmp_solo_nickname_only; 
				
			}else{
				//print "<br>Does NOT Contain $cur_cid";
			}
			
		
		}
		$dao->free();
		
		
		
		
	}
	
function process_spouses(&$suffixes, &$prefixes, &$values, &$contactIDs, $greetings_token_names,   $household_id){
	 
	 
	$id_list = "";
	$i = 1;
	foreach ( $contactIDs as $cid ) {
		$id_list = $id_list.$cid;
		if( $i < count($contactIDs) ){
			$id_list = $id_list.' ,';
		}
		$i = $i +1;
	}

	if ( $i == 1){
		return;
	}


	$household_contains_minimum_individual = false;
	$current_family = array();
	// print "<br><br>Inside process_spouses: ".$id_list;
	$sqlstr = "SELECT rel.contact_id_a  as cid_a, rel.contact_id_b as cid_b,  c1.prefix_id  , c1.first_name, c1.nick_name,
	c1.last_name, c1.suffix_id, c1.birth_date, c1.gender_id, c1.is_deceased ,
	c1.contact_type as contact_type_a, 
	c2.prefix_id as spouse_prefix_id,
	c2.first_name as spouse_first_name,  c2.nick_name as spouse_nick_name,  c2.last_name as spouse_last_name,
	c2.suffix_id as spouse_suffix_id, c2.gender_id as spouse_gender_id, c2.is_deceased as spouse_is_deceased,
	c2.contact_type as contact_type_b
	FROM civicrm_relationship AS rel
	JOIN (
	civicrm_contact AS c1,
	civicrm_contact AS c2,
	civicrm_relationship_type AS reltype)
	ON rel.contact_id_a = c1.id
	AND rel.contact_id_b = c2.id
	AND rel.relationship_type_id = reltype.id
	WHERE ( lower(name_a_b)  LIKE '%spouse%'  OR lower(name_a_b)  LIKE '%partner%' )
	and rel.is_active = 1
	AND (rel.contact_id_a in ( $id_list)  or rel.contact_id_b in ( $id_list )  )
	AND c1.is_deleted <> 1 and c2.is_deleted <> 1
	AND c1.is_deceased <> 1 AND  c2.is_deceased <> 1
	group by rel.contact_id_a , rel.contact_id_b ,  c1.prefix_id , c1.first_name, c1.last_name
	order by rel.contact_id_b, c1.birth_date ";



	$contact_dao =& CRM_Core_DAO::executeQuery( $sqlstr );

	// print "<br><br>sql: ".$sqlstr;
	$i = 0;

	//Lets get greetings for spouses.
	while ( $contact_dao->fetch( ) ) {
		$i = $i + 1;
		// Load up cur_contact with current record data
		// print "<br>record :".$i;
		$prefix_id = $contact_dao->prefix_id;
		
		if( strlen($prefix_id) > 0 ){
			$prefix_label  = $prefixes[$prefix_id];
		}else{
			$prefix_label  = "";
		}

		$prefix_id_spouse = $contact_dao->spouse_prefix_id;
		
		if( strlen( $prefix_id_spouse ) > 0 ){
			$prefix_label_spouse  = $prefixes[$prefix_id_spouse];
		}else{
			$prefix_label_spouse = "";
		}
		
		$suffix_id = $contact_dao->suffix_id;
		if(strlen($suffix_id) > 0){
			$suffix_label  = $suffixes[$suffix_id];
		}else{
			$suffix_label  = "";
		}
		

		$suffix_id_spouse = $contact_dao->spouse_suffix_id;
		if(strlen( $suffix_id_spouse ) > 0 ){
			$suffix_label_spouse  = $suffixes[$suffix_id_spouse];
		}else{
			$suffix_label_spouse = "";
		}
		
		

		// deal with genders
		$gender_id = $contact_dao->gender_id;
		if ($gender_id  == 1){
			$gender_label = "Female";
		}else{
			$gender_label = "Male";
		}

		$spouse_gender_id = $contact_dao->spouse_gender_id;
		if ($spouse_gender_id  == 1){
			$spouse_gender_label = "Female";
		}else{
			$spouse_gender_label = "Male";
		}
		 
		 
		$cur_contact_a["contact_id"] = $contact_dao->cid_a;
		$cur_contact_a["contact_type"] = $contact_dao->contact_type_a;
		$cur_contact_a["prefix"] = $prefix_label;
		$cur_contact_a["first_name"] = $contact_dao->first_name;
		$cur_contact_a["nick_name"] = $contact_dao->nick_name;
		$cur_contact_a["last_name"] =  $contact_dao->last_name;
		$cur_contact_a["suffix"]  = $suffix_label;
		$cur_contact_a["gender"]=   $gender_label;
		$cur_contact_a["is_deceased"] =  $contact_dao->is_deceased;

		// add cur_contact to the family array.
		$current_family[] =  $cur_contact_a ;
		$household_contains_minimum_individual = true;

		$cur_contact_b["contact_id"] = $contact_dao->cid_b;
		$cur_contact_a["contact_type"] = $contact_dao->contact_type_b;
		$cur_contact_b["prefix"] = $prefix_label_spouse;
		$cur_contact_b["first_name"] =  $contact_dao->spouse_first_name;
		$cur_contact_b["nick_name"] =  $contact_dao->spouse_nick_name;
		$cur_contact_b["last_name"] =  $contact_dao->spouse_last_name;
		$cur_contact_b["suffix"] =  $suffix_label_spouse;
		$cur_contact_b["gender"]=   $spouse_gender_label;
		$cur_contact_b["is_deceased"] =  $contact_dao->spouse_is_deceased;



		// add cur_contact to the family array.
		$current_family[] =  $cur_contact_b ;
		$household_contains_minimum_individual = true;

		
		
		// Add current household to family array if hh_id is not empty.
		if( $household_id <> '' ){
			$cur_hh["contact_id"] = $household_id ;
			$cur_hh["contact_type"] = "Household";
			$current_family[] = $cur_hh;
		}
		
		$this->process_family_greetings( $current_family, $values, $greetings_token_names);
		$current_family = array();
		
	}
	// print "<br>Count ".$i;
	$contact_dao->free( );
	
	if($household_contains_minimum_individual == false){
		if( $household_id <> '' ){
			$cur_hh["contact_id"] = $household_id ;
			$cur_hh["contact_type"] = "Household";
			$current_family[] = $cur_hh;
			
			
			$this->process_family_greetings( $current_family, $values, $greetings_token_names);
		}
		
		
	}
	
	
	



}


function greetings_determine_middle_content( $contact_id ){

	$params = array(
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
	);
	$result = civicrm_api('JointGreetings', 'getsingle', $params);

	$middle_html = "";

	$middle_html = $middle_html." <tr> <td class='label'>Casual (nickname last name):</td> <td class='html-adjust'>".$result['greetings.joint_casual']."</td>  </tr> \n  ";
	$middle_html = $middle_html." <tr> <td class='label'>Casual (first name last name):</td> <td class='html-adjust'>".$result['greetings.joint_casual_firstname_lastname']."</td>  </tr> \n  ";
	$middle_html = $middle_html." <tr> <td class='label'>Casual (nickname):</td> <td class='html-adjust'>".$result['greetings.joint_casual_nickname_only']."</td>  </tr> \n  ";

	$middle_html = $middle_html." <tr> <td class='label'>Casual (first name):</td> <td class='html-adjust'>".$result['greetings.joint_casual_firstname_only']."</td>  </tr> \n  ";
	$middle_html = $middle_html." <tr> <td class='label'>Formal (prefix last name):</td> <td class='html-adjust'>".$result['greetings.joint_formal']."</td>  </tr> \n  ";
	$middle_html = $middle_html." <tr> <td class='label'>Formal (prefix first name last name):</td> <td class='html-adjust'>".$result['greetings.joint_formal_firstname']."</td>  </tr> \n  ";


	return $middle_html;

	/*
	 $middle_html = $middle_html." <tr> <td class='label'>Solo Casual:</td> <td class='html-adjust'>".$result['greetings.solo_casual']."</td>  </tr> \n  ";
	 $middle_html = $middle_html." <tr> <td class='label'>Solo Casual nickname only:</td> <td class='html-adjust'>".$result['greetings.solo_casual_nickname_only']."</td>  </tr> \n  ";
	 */

}


function greetings_determine_beginning_content( $label ){

	$html_rtn = "   <div id='customFields'>
	<div class='contact_panel'>
	<div class='contactCardLeft'>
	<div class='customFieldGroup ui-corner-all'>
	<table>

	<tr>
	<td colspan='2' class='grouplabel'>$label</td>
	</tr> \n
	";

	return $html_rtn;


}




function greetings_determine_ending_content(){

	$html_rtn = " </table>
		            </div>
		                        </div><!--contactCardLeft-->

		                        <div class='contactCardRight'>
		                                                                </div>

		                        <div class='clear'></div>
		                    </div>
		                </div>  \n
		   ";

	return $html_rtn;


}



function avoid_empty_tokens(  &$values, &$contactIDs , &$greetings_token_names){
	
	
	foreach($contactIDs as $cur_id ){
		if(array_key_exists($cur_id,  $values)){
			foreach($greetings_token_names as $cur_token_name){
			    $tmp = $values[$cur_id][$cur_token_name];
			    if(isset( $values[$cur_id][$cur_token_name] ) && strlen(trim($tmp) > 0)){
			    	
			    }else{	
					// Token value for this contact is empty, go get display_name
			    	
			    	$api_result = civicrm_api3('Contact', 'get', array(
			    			'sequential' => 1,
			    			'id' => $cur_id,
			    	));
			    		
			    	if( $api_result['count'] == 1){
			    		$tmp_display_name = $api_result['values'][0]['display_name'];
			    		$values[$cur_id][$cur_token_name] = $tmp_display_name;
			    	}
			    		
				
			    }
			}
		}
	}
		
		
		
	
	
	
}
/*********************************************************************************
 *  Get the greetings for everyone in a single family.
 *
 *
 **********************************************************************************/
function process_family_greetings( &$family, &$values, $greetings_token_names){


	$token_formal_short =  $token_formal_long = $greetings_token_names['greetings.joint_formal'];
	$token_formal_fn_short =  $token_formal_fn_long = $greetings_token_names['greetings.joint_formal_firstname'];

	// $token_firstname_only_short =  $token_firstname_only_long = $greetings_token_names['greetings.joint_casual_firstname_only'];
	// $token_name_long = $greetings_token_names['greetings.joint_casual'];

	$token_joint_casual = $greetings_token_names['greetings.joint_casual'];
	$token_joint_casual_firstname_only =  $greetings_token_names['greetings.joint_casual_firstname_only'];
	$token_joint_casual_firstname_lastname = $greetings_token_names['greetings.joint_casual_firstname_lastname'];
	$token_joint_casual_nickname_only = $greetings_token_names['greetings.joint_casual_nickname_only'];



	// get family greeting
	$have_greeting = false;
	$needed_greets = array();
	
	foreach($family as $cur_contact){
		$tmp_cid = $cur_contact['contact_id'];
		
		
		//print "<br>tmp_cid: $tmp_cid";
		if(array_key_exists($tmp_cid,  $values)){
			 
			if(isset(  $values[$tmp_cid][$token_joint_casual] )){
				$test_greet = $values[$tmp_cid][$token_joint_casual];
			}else{
				$test_greet = "";
			}
			if($test_greet != ""){
				$have_greeting = true;
				$needed_greets['casual'] = $values[$tmp_cid][$token_joint_casual] ;
				$needed_greets['casual_firstname_only'] = $values[$tmp_cid][$token_joint_casual_firstname_only] ;
				$needed_greets['casual_firstname_lastname'] = $values[$tmp_cid][$token_joint_casual_firstname_lastname];
				$needed_greets['casual_nickname_only'] = $values[$tmp_cid][$token_joint_casual_nickname_only] ;

				$needed_greets['formal'] = $values[$tmp_cid][$token_formal_long]  ;
				$needed_greets['formal_firstname'] = $values[$tmp_cid][$token_formal_fn_long] ;
				//	print "<br><br>needed greets: casual_firstname_only: ".$needed_greets['casual_firstname_only'];
			}
			 
		}
	}
	 
	 

	if($have_greeting == false){
		if(count($family) == 1){
				
				if( $family[0][spouse_last_name]){
					// this is a married couple or a widow.
					if($family[0]['is_deceased'] OR $family[0]['spouse_is_deceased'] ){
						$needed_greets = $this->get_formatted_greeting_for_widow( $family[0]['prefix'], $family[0]['first_name'], $family[0]['last_name'], $family[0]['suffix'], $family[0]['gender'], $family[0]['is_deceased'], $family[0]['spouse_prefix'], $family[0]['spouse_first_name'], $family[0]['spouse_last_name'], $family[0]['spouse_suffix'], $family[0]['spouse_gender'], $family[0]['spouse_is_deceased'], $family[0]['nick_name'], $family[0]['spouse_nick_name'] );
					}else{
						$needed_greets = $this->get_formatted_greeting_for_couple( $family[0]['prefix'], $family[0]['first_name'], $family[0]['last_name'], $family[0]['suffix'], $family[0]['gender'], $family[0]['is_deceased'], $family[0]['spouse_prefix'], $family[0]['spouse_first_name'], $family[0]['spouse_last_name'], $family[0]['spouse_suffix'], $family[0]['spouse_gender'], $family[0]['spouse_is_deceased'], $family[0]['nick_name'], $family[0]['spouse_nick_name'] );
					}
				}else{
					// this is a person in a one person household.
					$uses_spouses_name = false;
					$needed_greets = $this->get_formatted_greeting_for_single( $family[0]['prefix'], $family[0]['first_name'], $family[0]['last_name'], $family[0]['suffix'], $family[0]['gender'], $uses_spouses_name,  $family[0]['nick_name'] );
						
				}
			
			}else if(count($family) >= 2){
				if($family[0]['is_deceased'] OR $family[1]['is_deceased'] ){
					$needed_greets = $this->get_formatted_greeting_for_widow( $family[0]['prefix'], $family[0]['first_name'], $family[0]['last_name'], $family[0]['suffix'], $family[0]['gender'], $family[0]['is_deceased'], $family[1]['prefix'], $family[1]['first_name'], $family[1]['last_name'], $family[1]['suffix'], $family[1]['gender'], $family[1]['is_deceased'] );
				}else{
					$needed_greets = $this->get_formatted_greeting_for_couple( $family[0]['prefix'], $family[0]['first_name'], $family[0]['last_name'], $family[0]['suffix'], $family[0]['gender'], $family[0]['is_deceased'], $family[1]['prefix'], $family[1]['first_name'], $family[1]['last_name'], $family[1]['suffix'], $family[1]['gender'], $family[1]['is_deceased'], $family[0]['nick_name'], $family[1]['nick_name'] );
				}

		}
	}



	// fill in family greeting for each contact.
	$i =0;
	foreach($family as $cur_contact){
		 
		// print "<br>cur contact: ";
		// print_r( $cur_contact) ;
		$family[$i]['needed_greets'] = $needed_greets ;
		 
		 
		//$token_formal_long, $token_formal_short, $token_formal_fn_long, $token_formal_fn_short
		$cur_cid = $cur_contact['contact_id'];
		
		if(isset($cur_contact['hh_id'])){
			$cur_hhid = $cur_contact['hh_id'];
		}else{
			$cur_hhid = "";
		}
		 
		if(array_key_exists($cur_cid,  $values)){
			//print "<br> Contains $cur_cid  use greeting: $greeting";
			$values[$cur_cid][$token_joint_casual] = $needed_greets['casual'];
			$values[$cur_cid][$token_formal_short] = $values[$cur_cid][$token_formal_long] = $needed_greets['formal'] ;
			$values[$cur_cid][$token_formal_fn_short] = $values[$cur_cid][$token_formal_fn_long] = $needed_greets['formal_firstname'];
			 
			$values[$cur_cid][$token_joint_casual_firstname_only] = $needed_greets['casual_firstname_only'];
			$values[$cur_cid][$token_joint_casual_firstname_lastname] = $needed_greets['casual_firstname_lastname'];
			$values[$cur_cid][$token_joint_casual_nickname_only] = $needed_greets['casual_nickname_only'];
		}else{
			//print "<br>Does NOT Contain $cur_cid";
		}
		 
		if(array_key_exists($cur_hhid,  $values)){
			//print "<br> Contains hhid $cur_hhid use greeting: $greeting";
			$values[$cur_hhid][$token_joint_casual] = $needed_greets['casual'];
			$values[$cur_hhid][$token_formal_short] = $values[$cur_hhid][$token_formal_long] = $needed_greets['formal'] ;
			$values[$cur_hhid][$token_formal_fn_short] = $values[$cur_hhid][$token_formal_fn_long] = $needed_greets['formal_firstname'];

			 
			$values[$cur_hhid][$token_joint_casual_firstname_only] = $needed_greets['casual_firstname_only'];
			$values[$cur_hhid][$token_joint_casual_firstname_lastname] = $needed_greets['casual_firstname_lastname'];
			$values[$cur_hhid][$token_joint_casual_nickname_only] = $needed_greets['casual_nickname_only'];
		}else{
			// print "<br>Does NOT Contain hhid $cur_hhid";
		}
		$i = $i + 1;
	}


	
}


function get_formatted_greeting_for_couple( $adult_a_prefix, $adult_a_first_name, $adult_a_last_name, $adult_a_suffix, $adult_a_gender, $adult_a_deceased,  $adult_b_prefix, $adult_b_first_name, $adult_b_last_name, $adult_b_suffix, $adult_b_gender, $adult_b_deceased, $a_nick_name, $b_nick_name ){

	$needed_greets =  array();
	$and_label = " and ";  // English.

	$b_has_real_title = false;

	if( strlen($a_nick_name) > 0){
		$a_casual_first_name = $a_nick_name;
	}else{
		$a_casual_first_name = $adult_a_first_name;
	}


	if( strlen($b_nick_name) > 0){
		$b_casual_first_name = $b_nick_name;
	}else{
		$b_casual_first_name = $adult_b_first_name;
	}

	$needed_greets['casual_firstname_only'] = $adult_a_first_name.$and_label.$adult_b_first_name;
	$needed_greets['casual_nickname_only'] = $a_casual_first_name.$and_label.$b_casual_first_name ;

	if(  $adult_a_last_name  == $adult_b_last_name ){
		// This couple shares a last name.
		$uses_spouses_name = true;
		$prefix_info_a = $this->determine_title($adult_a_prefix,  $adult_a_gender, $uses_spouses_name);
		$prefix_info_b = $this->determine_title($adult_b_prefix,  $adult_b_gender, $uses_spouses_name);

		 
		if($prefix_info_b['real_title'] and !($prefix_info_a['real_title'])) {
			$needed_greets['casual'] = $prefix_info_b['prefix']." ".$b_casual_first_name.$and_label.$a_casual_first_name." ".$adult_a_last_name;
			$needed_greets['formal'] = $prefix_info_b['prefix'].$and_label.$prefix_info_a['prefix']." ".$adult_a_last_name;
		}else if($prefix_info_a['real_title'] and !($prefix_info_b['real_title'])){
			$needed_greets['casual'] = $prefix_info_a['prefix']." ".$a_casual_first_name.$and_label.$b_casual_first_name." ".$adult_a_last_name;
			$needed_greets['formal'] = $prefix_info_a['prefix'].$and_label.$prefix_info_b['prefix']." ".$adult_a_last_name ;
		}else if($prefix_info_a['real_title'] and $prefix_info_b['real_title']){
			// both have real titles.
			$needed_greets['casual'] = $prefix_info_a['prefix']." ".$a_casual_first_name.$and_label.$prefix_info_b['prefix']." ".$b_casual_first_name." ".$adult_a_last_name;
			$needed_greets['formal'] = $prefix_info_a['prefix'].$and_label.$prefix_info_b['prefix']." ".$adult_a_last_name ;
		}else{
			// no one has a real title.
			$needed_greets['casual'] = $a_casual_first_name.$and_label.$b_casual_first_name." ".$adult_a_last_name;
			//
			if($adult_b_gender == 'Male' ){
				// Make sure Mr. is the start.
				$needed_greets['formal'] = $prefix_info_b['prefix'].$and_label.$prefix_info_a['prefix']." ".$adult_a_last_name ;
			}else{
				$needed_greets['formal'] = $prefix_info_a['prefix'].$and_label.$prefix_info_b['prefix']." ".$adult_a_last_name ;
			}
		}
		// deal with casual greetings that do not include title.
		$needed_greets['casual_firstname_lastname'] = $adult_a_first_name.$and_label.$adult_b_first_name." ".$adult_b_last_name;

		// Deal with formal greetings with first names. Such as Mr. and Mrs. John Smith
		if( ($adult_a_gender == 'Male') and (!($prefix_info_b['real_title'])) and ($adult_b_gender == 'Female')){
			$needed_greets['formal_firstname'] = $prefix_info_a['prefix'].$and_label.$prefix_info_b['prefix']." ".$adult_a_first_name." ".$adult_a_last_name." ".$adult_a_suffix;
		}else if( $adult_b_gender == 'Male' and !($prefix_info_a['real_title']) and $adult_a_gender == 'Female'){
			$needed_greets['formal_firstname']   = $prefix_info_b['prefix'].$and_label.$prefix_info_a['prefix']." ".$adult_b_first_name." ".$adult_b_last_name." ".$adult_b_suffix;
		}else{
			$needed_greets_a = $this->get_formatted_greeting_for_single( $adult_a_prefix, $adult_a_first_name, $adult_a_last_name, $adult_a_suffix,  $adult_a_gender, $uses_spouses_name , $a_nick_name );
			$needed_greets_b = $this->get_formatted_greeting_for_single( $adult_b_prefix, $adult_b_first_name, $adult_b_last_name, $adult_b_suffix,  $adult_b_gender, $uses_spouses_name, $b_nick_name  );
			if($b_has_real_title) {
				$needed_greets['formal_firstname'] = $needed_greets_b['formal_firstname'].$and_label.$needed_greets_a['formal_firstname'];
			}else{
				$needed_greets['formal_firstname'] = $needed_greets_a['formal_firstname'].$and_label.$needed_greets_b['formal_firstname'];
			}
		}
		 
	}else{
		// the couple has different last names.
		$uses_spouses_name = false;
		$needed_greets_a = $this->get_formatted_greeting_for_single( $adult_a_prefix, $adult_a_first_name, $adult_a_last_name, $adult_a_suffix,  $adult_a_gender, $uses_spouses_name, $a_nick_name  );
		$needed_greets_b = $this->get_formatted_greeting_for_single( $adult_b_prefix, $adult_b_first_name, $adult_b_last_name, $adult_b_suffix,  $adult_b_gender, $uses_spouses_name, $b_nick_name  );
		 
		$prefix_info_a = $this->determine_title($adult_a_prefix,  $adult_a_gender, $uses_spouses_name);
		$prefix_info_b = $this->determine_title($adult_b_prefix,  $adult_b_gender, $uses_spouses_name);
		 
		 
		if($prefix_info_b['real_title']){
			$needed_greets['casual_firstname_only'] = $needed_greets_b['formal_firstname'].$and_label.$needed_greets_a['formal_firstname'];
			$needed_greets['casual'] = $needed_greets_b['casual'].$and_label.$needed_greets_a['casual'];
			$needed_greets['casual_firstname_lastname'] = $adult_b_first_name." ".$adult_b_last_name.$and_label.$adult_a_first_name." ".$adult_a_last_name;
			$needed_greets['casual_nickname_only'] = $needed_greets_b['casual_nickname_only'].$and_label.$needed_greets_a['casual_nickname_only'];
			 
			$needed_greets['formal'] = $needed_greets_b['formal'].$and_label.$needed_greets_a['formal'];
			$needed_greets['formal_firstname'] = $needed_greets_b['formal_firstname'].$and_label.$needed_greets_a['formal_firstname'];
		}else{
			$needed_greets['casual_firstname_only'] = $needed_greets_a['casual_firstname_only'].$and_label.$needed_greets_b['casual_firstname_only'];
			$needed_greets['casual'] = $needed_greets_a['casual'].$and_label.$needed_greets_b['casual'];
			$needed_greets['casual_firstname_lastname'] = $adult_a_first_name." ".$adult_a_last_name.$and_label.$adult_b_first_name." ".$adult_b_last_name;
			$needed_greets['casual_nickname_only'] = $needed_greets_a['casual_nickname_only'].$and_label.$needed_greets_b['casual_nickname_only'];
			 
			 
			$needed_greets['formal'] = $needed_greets_a['formal'].$and_label.$needed_greets_b['formal'];
			$needed_greets['formal_firstname'] = $needed_greets_a['formal_firstname'].$and_label.$needed_greets_b['formal_firstname'];
		}
	} // end else

	//   print_r( $needed_greets);
	return $needed_greets;
}

// format as a single person.
function get_formatted_greeting_for_single($adult_prefix, $adult_first_name, $adult_last_name, $adult_suffix, $adult_gender, $uses_spouses_name, $nick_name ){

	$needed_greets = array();

	if( !($adult_last_name)){
		if(strlen( $nick_name ) > 0){

			$needed_greets['casual'] = $nick_name;
			$needed_greets['casual_firstname_only'] = $adult_first_name;
			$needed_greets['casual_firstname_lastname'] = $adult_first_name;
			$needed_greets['casual_nickname_only']   = $nick_name;
		}else{
	 	$needed_greets['casual'] = $adult_first_name;
	 	$needed_greets['casual_firstname_only'] = $adult_first_name;
	 	$needed_greets['casual_firstname_lastname'] = $adult_first_name;
	 	$needed_greets['casual_nickname_only'] =  $adult_first_name;
		}
		$needed_greets['formal']  = $adult_first_name;
		$needed_greets['formal_firstname'] = $adult_first_name;
		return $needed_greets;
		 
	}

	$prefix_info = $this->determine_title($adult_prefix,  $adult_gender, $uses_spouses_name);

	if($prefix_info['real_title']){
		if(strlen( $nick_name ) > 0){
			$needed_greets['casual'] = $prefix_info['prefix']." ".$nick_name." ".$adult_last_name." ".$adult_suffix;
			$needed_greets['casual_firstname_only'] = $adult_first_name;
			$needed_greets['casual_firstname_lastname'] = $adult_first_name." ".$adult_last_name;
			$needed_greets['casual_nickname_only'] =  $nick_name;
		}else{
			$needed_greets['casual'] = $prefix_info['prefix']." ".$adult_first_name." ".$adult_last_name." ".$adult_suffix;
			$needed_greets['casual_firstname_only'] = $adult_first_name;
			$needed_greets['casual_firstname_lastname'] = $adult_first_name." ".$adult_last_name;
			$needed_greets['casual_nickname_only'] =  $adult_first_name;
		}
	}else{
		if(strlen( $nick_name ) > 0){
			$needed_greets['casual'] = $nick_name." ".$adult_last_name." ".$adult_suffix;
			$needed_greets['casual_firstname_only'] = $adult_first_name;
			$needed_greets['casual_firstname_lastname'] = $adult_first_name." ".$adult_last_name;
			$needed_greets['casual_nickname_only'] =  $nick_name;
		}else{
			$needed_greets['casual'] = $adult_first_name." ".$adult_last_name." ".$adult_suffix;
			$needed_greets['casual_firstname_only'] = $adult_first_name;
			$needed_greets['casual_firstname_lastname'] = $adult_first_name." ".$adult_last_name;
			$needed_greets['casual_nickname_only'] =  $adult_first_name;
		}
	}

	$needed_greets['formal'] = $prefix_info['prefix']." ".$adult_last_name." ".$adult_suffix ;
	$needed_greets['formal_firstname'] = $prefix_info['prefix']." ".$adult_first_name." ".$adult_last_name." ".$adult_suffix ;

	/*
	 if(strlen( $nick_name ) > 0){
	 $needed_greets['casual_firstname_only'] = $nick_name;
	 }else{
	 $needed_greets['casual_firstname_only'] = $adult_first_name;
	 }
	 */

	return $needed_greets;
}



// Determine courtesy title for someone without a professional title.
// For example, if someone is a doctor, then return the stored prefix ie Dr.
// If someone doesn't have a title, return Mr or Ms
function determine_title($prefix, $gender, $uses_spouses_name){

	$prefix_info = array();
	$prefix_info['real_title'] = false;

	if(!($prefix) ){
		$prefix_info['real_title'] = false;
		if( $gender == 'Female' and $uses_spouses_name ){
			$prefix = "Mrs.";
		}else if($gender == 'Female'){
			$prefix = "Ms.";
		}else{
			$prefix = "Mr.";
		}
	}else if( $prefix == 'Mr' or $prefix == 'Mr.' or $prefix == 'Mrs' or $prefix == 'Mrs.' or $prefix == 'Ms' or $prefix == 'Ms.' or $prefix == 'Miss'){
		$prefix_info['real_title'] = false;
		if($gender == 'Female' and $uses_spouses_name ){
			// This means the woman shares her spouse's last name and may be part of "Mr and Mrs"
			// It is never correct to write Mr. and Ms. Smith     or   Mr. and Ms. John Smith
			$prefix = "Mrs.";
		}

	}else{
		$prefix_info['real_title'] = true;

	}

	// Check if period is needed, due to typo in CiviCRM data.
	if($prefix == 'Dr' or $prefix == 'Mr' or $prefix == 'Mrs' or $prefix == 'Ms'){
		$prefix = "$prefix.";
	}


	$prefix_info['prefix'] = $prefix ;
	return $prefix_info;
}


function process_households(&$suffixes, &$prefixes, &$values, &$contactIDs,$greetings_token_names ){


	$token_name_long = $greetings_token_names['greetings.joint_casual'];
	$token_formal_short =  $token_formal_long = $greetings_token_names['greetings.joint_formal'];
	$token_formal_fn_short =  $token_formal_fn_long = $greetings_token_names['greetings.joint_formal_firstname'];

	//print "<hr><br>Inside process households<br>";

	$i = 1;
	$cid_list = "";
	foreach ( $contactIDs as $cid ) {
		$cid_list = $cid_list.$cid;
		if( $i < count($contactIDs) ){
			$cid_list = $cid_list.' ,';
		}
		$i = $i +1;
	}


	if($i == 1){

		return;
	}



	$sqlstr = "SELECT r2.contact_id_a AS cid_a, r2.contact_id_b AS cid_b, c1.contact_type, c1.prefix_id, c1.first_name, c1.nick_name, c1.last_name, c1.suffix_id, c1.birth_date, name_a_b
	FROM civicrm_relationship AS r1
	JOIN (
	civicrm_relationship AS r2, civicrm_contact AS c1, civicrm_relationship_type AS reltype
	) ON r1.contact_id_b = r2.contact_id_b
	AND r2.contact_id_a = c1.id
	AND r2.relationship_type_id = reltype.id
	WHERE (reltype.name_a_b = 'Head of Household for' or reltype.name_a_b = 'Household Member of' )
	AND r1.is_active =1
	AND r2.is_active =1
	and (r1.contact_id_a in ( $cid_list)  or r1.contact_id_b in ( $cid_list )  )
	and c1.is_deleted <> 1
	GROUP BY r2.contact_id_a, r2.contact_id_b, c1.prefix_id, c1.first_name, c1.last_name, reltype.name_a_b
	ORDER BY  r2.contact_id_b, reltype.name_a_b, c1.birth_date ";

	/*

	$sqlstr = "SELECT r2.contact_id_a AS cid_a, r2.contact_id_b AS cid_b, c1.contact_type, c1.prefix_id, c1.first_name, c1.last_name,
	c1.suffix_id, c1.birth_date, r3.contact_id_a, r3.contact_id_b,  reltype.name_a_b , reltype3.name_a_b
	FROM
	civicrm_relationship AS r1 JOIN ( civicrm_relationship AS r2, civicrm_contact AS c1
	civicrm_relationship_type AS reltype, civicrm_relationship as r3, civicrm_relationship_type AS reltype3 )
	ON r1.contact_id_b = r2.contact_id_b AND r2.contact_id_a = c1.id AND r2.relationship_type_id = reltype.id and
	(r2.contact_id_a = r3.contact_id_a or r2.contact_id_a = r3.contact_id_b) and r3.relationship_type_id = reltype3.id and
	lower(reltype3.name_a_b) LIKE '%spouse%'
	WHERE (reltype.name_a_b = 'Head of Household for' or reltype.name_a_b = 'Household Member of' ) AND r1.is_active =1
	AND r2.is_active =1 and (r1.contact_id_a in (  $cid_list) or r1.contact_id_b in (  $cid_list ) ) GROUP BY r2.contact_id_a,
	r2.contact_id_b, c1.prefix_id, c1.first_name, c1.last_name, reltype.name_a_b ORDER BY r2.contact_id_b, reltype.name_a_b, c1.birth_date";

	*/
	// print "<br>sql: ".$sqlstr;

	$contact_dao =& CRM_Core_DAO::executeQuery( $sqlstr );

	$current_hh = array();
	$last_hh_id = "";
	//Lets get greetings for everyone in a household.
	while ( $contact_dao->fetch( ) ) {

		$cur_contact_id = $contact_dao->cid_a;
		$cur_hh_id = $contact_dao->cid_b;


		$tmp_contactIDs = array();
		$tmp_contactIDs[] = $cur_contact_id ;


		$this->process_spouses($suffixes, $prefixes, $values, $tmp_contactIDs, $greetings_token_names,  $cur_hh_id );
		 
		 
	}

	$contact_dao->free( );




}


/**********************************************************************************************
 *   Get the greetings for people not in a household or spousal relationship.
 *
 *
 *
 ***********************************************************************************************/
function process_singles(&$suffixes, &$prefixes, &$values, $greetings_token_names){

	// print_r($values);

	$token_formal_long = $greetings_token_names['greetings.joint_formal'];
	$token_formal_fn_long = $greetings_token_names['greetings.joint_formal_firstname'];

	$token_joint_casual = $greetings_token_names['greetings.joint_casual'];
	$token_joint_casual_firstname_only =  $greetings_token_names['greetings.joint_casual_firstname_only'];
	$token_joint_casual_firstname_lastname = $greetings_token_names['greetings.joint_casual_firstname_lastname'];
	$token_joint_casual_nickname_only = $greetings_token_names['greetings.joint_casual_nickname_only'];

	$token_solo_casual  = $greetings_token_names['greetings.solo_casual'];
	$token_solo_casual_nickname_only = $greetings_token_names['greetings.solo_casual_nickname_only'];
	$cid_list = "";
	foreach ( $values as $cur_contact ) {
	 //  print "<br>cur contact:";
	 //  print_r($cur_contact);
		// print "<br>current joint_greeting: ".$cur_contact['contact.joint_greeting'];
		if(! (array_key_exists( $token_joint_casual , $cur_contact) ) ){
			$cid = $cur_contact['contact_id'];
			if(strlen($cid_list) > 0 && strlen($cid) >0   ){
				$cid_list = $cid_list.",";

			}
			$cid_list = "$cid_list $cid ";
		}

	}
	
	if(strlen($cid_list) > 0 ){
		$tmp_pos = strlen($cid_list) - 1;
		$last = $cid_list[$tmp_pos] ;
	}else{
		$last = "";
	}
	
	if($last == ","){
		$cid_list[strlen($cid_list)-1] = " ";
	}
	$cid_list = trim($cid_list);

	//print "<br> cidlist length: ";
	//print strlen($cid_list);
	if( strlen($cid_list) > 0  ) {
		$where_clause = " WHERE c.id in ( $cid_list) ";

		$sqlstr = "(SELECT c.id, c.contact_type, c.prefix_id, c.first_name, c.nick_name,  c.last_name, c.suffix_id, c.gender_id
		FROM  civicrm_contact AS c $where_clause and contact_type = 'Individual')
		UNION
		(SELECT c1.id, c1.contact_type, c2.prefix_id, c2.first_name, c2.nick_name, c2.last_name, c2.suffix_id, c2.gender_id
		FROM civicrm_contact c1,civicrm_relationship r, civicrm_contact c2, civicrm_relationship_type rt
		where  c1.id in ($cid_list) and c1.id = r.contact_id_b and r.contact_id_a = c2.id and r.relationship_type_id = rt.id
		and r.is_active = 1
		and c1.contact_type = 'Household' and rt.name_a_b = 'Household Member of'
		group by c1.id
		having count(c1.id) = 1 )
		UNION
		(SELECT c1.id, c1.contact_type, c2.prefix_id, c2.first_name, c2.nick_name, c2.last_name, c2.suffix_id, c2.gender_id
		FROM civicrm_contact c1,civicrm_relationship r, civicrm_contact c2, civicrm_relationship_type rt
		where  c1.id in ($cid_list) and c1.id = r.contact_id_b and r.contact_id_a = c2.id and r.relationship_type_id = rt.id
		and r.is_active = 1
		and c1.contact_type = 'Household' and rt.name_a_b = 'Head of Household for'
		group by c1.id
		having count(c1.id) = 1) " ;


		$contact_dao =& CRM_Core_DAO::executeQuery( $sqlstr );

		//Lets get greetings for singles
		while ( $contact_dao->fetch( ) ) {
			$cur_cid = $contact_dao->id;
			$cur_contact_type = $contact_dao->contact_type;
			$prefix_id = $contact_dao->prefix_id ;
			if(strlen($prefix_id)> 0){
				$prefix_label  = $prefixes[$prefix_id];
			}else{
				$prefix_label = "";
			}
			
			$suffix_id = $contact_dao->suffix_id ;
			if(strlen( $suffix_id ) > 0 ){
				$suffix_label  = $suffixes[$suffix_id];
			}else{
				$suffix_label  = "";
			}
			// deal with genders
			$gender_id = $contact_dao->gender_id;
			if ($gender_id  == 1){
				$gender_label = "Female";
			}else{
				$gender_label = "Male";
			}
	   
			$cur_prefix = $prefix_label;
			$cur_first_name = $contact_dao->first_name;
			$cur_last_name =  $contact_dao->last_name;
			$cur_gender =   $gender_label;
			$cur_nick_name = $contact_dao->nick_name;
			 
			//print "<br>contact type: $cur_contact_type";
			if($cur_contact_type == 'Individual'){
				$uses_spouses_name = false;
				$needed_greets = $this->get_formatted_greeting_for_single($cur_prefix, $cur_first_name, $cur_last_name,  $suffix_label,  $gender_label, $uses_spouses_name , $cur_nick_name );
			}else{
				//$needed_greets = array();
				//print "<br>Contact type household, what to do? ".$cur_cid;
				$uses_spouses_name = false;
				$needed_greets = $this->get_formatted_greeting_for_single($cur_prefix, $cur_first_name, $cur_last_name,  $suffix_label,  $gender_label, $uses_spouses_name , $cur_nick_name  );



			}
			 
			 
			$values[$cur_cid][$token_joint_casual] = $needed_greets['casual'];
			$values[$cur_cid][$token_formal_long] = $needed_greets['formal'];
			$values[$cur_cid][$token_formal_fn_long] = $needed_greets['formal_firstname'];
		 $values[$cur_cid][$token_joint_casual_firstname_only] = $needed_greets['casual_firstname_only'];
		 	
		 $values[$cur_cid][$token_joint_casual_firstname_lastname] = $needed_greets['casual_firstname_lastname'];
		 $values[$cur_cid][$token_joint_casual_nickname_only] = $needed_greets['casual_nickname_only'];
		 	

		}
		$contact_dao->free( );

	}else{
		//print "<br>No contacts to process";
	}

	// handle solo greetings (including for married contacts)


	//print "<br> cidlist length: ";
	//print strlen($cid_list);
	if( strlen($cid_list) > 0  ) {
		$where_clause = " WHERE c.id in ( $cid_list) ";

		$sql = " SELECT c.id, c.contact_type, c.prefix_id, c.first_name, c.nick_name,  c.last_name, c.suffix_id, c.gender_id
		FROM  civicrm_contact AS c $where_clause and contact_type = 'Individual' ";

		$contact_dao =& CRM_Core_DAO::executeQuery( $sql );

		//Lets get greetings for singles
		while ( $contact_dao->fetch( ) ) {
			$cur_cid = $contact_dao->id;
			$cur_contact_type = $contact_dao->contact_type;
			$prefix_id = $contact_dao->prefix_id ;
			
			if( strlen( $prefix_id  ) > 0 ){
				$prefix_label  = $prefixes[$prefix_id];
			}else{
				$prefix_label  = "";
			}
			$suffix_id = $contact_dao->suffix_id ;
			if( strlen($suffix_id) > 0 ){
				$suffix_label  = $suffixes[$suffix_id];
			}else{
				$suffix_label  = "";
			}

			// deal with genders
			$gender_id = $contact_dao->gender_id;
			if ($gender_id  == 1){
				$gender_label = "Female";
			}else{
				$gender_label = "Male";
			}
	   
			$cur_prefix = $prefix_label;
			$cur_first_name = $contact_dao->first_name;
			$cur_last_name =  $contact_dao->last_name;
			$cur_gender =   $gender_label;
			$cur_nick_name = $contact_dao->nick_name;
			//print "<br>contact type: $cur_contact_type";
			if($cur_contact_type == 'Individual'){
				$uses_spouses_name = false;
				$needed_solo_greets = $this->get_formatted_greeting_for_single($cur_prefix, $cur_first_name, $cur_last_name,  $suffix_label,  $gender_label, $uses_spouses_name , $cur_nick_name );

				$values[$cur_cid][$token_solo_casual] = $needed_solo_greets['casual'];
				$values[$cur_cid][$token_solo_casual_nickname_only] = $needed_solo_greets['casual_nickname_only'];

	  }

		}

	}

}


/**********************************************************************************************
 *   Get the greetings for organizations
 *
 *
 *
 ***********************************************************************************************/
function process_organizations(&$suffixes, &$prefixes, &$values, $greetings_token_names){


	// print_r(  $greetings_token_names );
		$token_name_long = $greetings_token_names['greetings.joint_casual'];
	//	$token_formal_short =  $token_formal_long = $greetings_token_names['greetings.joint_formal'];
	//	$token_formal_fn_short =  $token_formal_fn_long = $greetings_token_names['greetings.joint_formal_firstname'];
	//	$token_firstname_only_short =  $token_name_long = $greetings_token_names['greetings.joint_casual_firstname_only'];


	//print "<br>first name only: ".$token_firstname_only_short;
	
	$cid_list = "";
	foreach ( $values as $cur_contact ) {
		// print "<br>current joint_greeting: ".$cur_contact['contact.joint_greeting'];
		if(! (array_key_exists( $token_name_long , $cur_contact) ) ){
			$cid = $cur_contact['contact_id'];
			if( strlen($cid_list) > 0 && strlen($cid) > 0 ){
				$cid_list = "$cid_list,";
			}
			$cid_list = "$cid_list $cid";
		}

	}
	if( strlen($cid_list ) > 0){
		$last = $cid_list[strlen($cid_list)-1] ;
	}else{
		$last = "";
	}
	
	if($last == ","  ){
		$cid_list[strlen($cid_list)-1] = " ";
	}

	// print "<br><br>cidlist: ".$cid_list;
	$cid_list = trim( $cid_list) ;
	if( strlen($cid_list) > 0  ) {
		$where_clause = " WHERE c.id in ( $cid_list) ";

		$sqlstr = "SELECT c.id, c.contact_type,  c.display_name as display_name
		FROM  civicrm_contact AS c $where_clause and c.contact_type = 'Organization' " ;

		//print "<br>sql: ".$sqlstr;
		$contact_dao =& CRM_Core_DAO::executeQuery( $sqlstr );


		while ( $contact_dao->fetch( ) ) {
			$cur_cid = $contact_dao->id;
			$cur_contact_type = $contact_dao->contact_type;
			$cur_display_name = $contact_dao->display_name;
			 
			foreach( $greetings_token_names as $cur_token_name){
				$values[$cur_cid][$cur_token_name] = $cur_display_name;
	    
			}
			//  $values[$cur_cid][$token_name_long] = $cur_display_name;
			// $values[$cur_cid][$token_formal_short] = $values[$cur_cid][$token_formal_long] = $cur_display_name;
			// $values[$cur_cid][$token_formal_fn_short] = $values[$cur_cid][$token_formal_fn_long] = $cur_display_name;
			// $values[$cur_cid][$token_firstname_only_short] = $values[$cur_cid][$token_firstname_only_short] = $cur_display_name;
		}
		$contact_dao->free( );

	}

}

function get_all_prefixes(){

	$sqlstr = "SELECT ov.label, ov.value FROM civicrm_option_group og join civicrm_option_value ov
on og.id = ov.option_group_id
where og.name = 'individual_prefix'  ";

	$prefix_dao =& CRM_Core_DAO::executeQuery( $sqlstr );

	$prefixes = array();

	while ( $prefix_dao->fetch( ) ) {
		$curprefix_id = $prefix_dao->value;
		$curprefix_label = $prefix_dao->label;
		 
		$prefixes[$curprefix_id] = $curprefix_label;

	}

	$prefix_dao->free( );

	return $prefixes;
}

function  get_all_suffixes(){

	$sqlstr = "SELECT ov.label, ov.value FROM civicrm_option_group og join civicrm_option_value ov
on og.id = ov.option_group_id
where og.name = 'individual_suffix'  ";

	$suffix_dao =& CRM_Core_DAO::executeQuery( $sqlstr );

	$suffixes = array();

	while ( $suffix_dao->fetch( ) ) {
		$cursuffix_id = $suffix_dao->value;
		$cursuffix_label = $suffix_dao->label;
		 
		$suffixes[$cursuffix_id] = $cursuffix_label;

	}

	$suffix_dao->free( );

	return $suffixes;


}

}