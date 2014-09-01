<?php

require_once 'fancytokens.civix.php';


function fancytokens_civicrm_tokens( &$tokens ){  
	$tokens['communitynews'] = array(
	  	'communitynews.upcomingevents___day_7' =>   'Community News & Engagement: Events in the next 7 days',
	  	'communitynews.upcomingevents___day_14' =>  'Community News & Engagement: Events in the next 14 days',
	  	'communitynews.upcomingevents___day_30' =>  'Community News & Engagement: Events in the next 30 days', 
	  	'communitynews.upcomingevents___week_3' =>  'Community News & Engagement: Events in the next 3 weeks', 
	  	'communitynews.upcomingevents___month_3' => 'Community News & Engagement: Events in the next 3 months',  
	  	);
  	
  	
  	
  	// create tokens for the next x events that allow online registration
  	$event_sql = "select e.id, e.title, date(e.start_date) as start_date FROM civicrm_event e
  	WHERE e.start_date >= now() and e.is_active =1 AND e.is_online_registration = 1
  	AND e.is_template <> 1
  	AND ( e.registration_end_date is null || now() <= e.registration_end_date ) 
  	AND ( e.registration_start_date is null || now() >= e.registration_start_date )
  	ORDER BY e.start_date
  	LIMIT 15 ";
  	
  	 $dao =& CRM_Core_DAO::executeQuery( $event_sql,   CRM_Core_DAO::$_nullArray ) ;
	
  	while($dao->fetch()){
  		$e_id = $dao->id;
  		$e_title = $dao->title;
  		$e_start_date = $dao->start_date;
  		$label = 'Community News & Engagement: Event Registration Page: '.$e_title.' on '.$e_start_date.' (id: '.$e_id.')'; 
  		$key = 'communitynews.event_registrationpage___'.$e_id ;
  		
  		 $tokens['communitynews'][$key] = $label; 
  	}
  	$dao->free();	
  	
  	
  	// Create tokens for all active Contribution Pages
  	$params = array(
	  'version' => 3,
	  'sequential' => 1,
	  'is_active' => 1,
	);
	$result = civicrm_api('ContributionPage', 'get', $params);	
	if( $result['is_error'] <> 0 ){
	    print "<br><br>Error calling get API for ContributionPage";
	    print_r($result); 
	}else{
		   $contrib_pages = $result['values'] ; 
		   foreach($contrib_pages as $cur){
		   
			    $key = 'communitynews.contributionpage___'.$cur['id'] ;
			    $label = 'Community News & Engagement: Contribution Page: '.$cur['title'].' (id: '.$cur['id'].')'; 
			   
			   $tokens['communitynews'][$key] = $label; 
		   }	   
	   }
	
	
	// Get all active profiles and create tokens for the ones that can be stand-alone. 
	$params = array(
	  'version' => 3,
	  'sequential' => 1,
	  'is_active' => 1,
	  'options' => array( 'limit' => 100),
	);
	$result = civicrm_api('UFGroup', 'get', $params);
	
	if( $result['is_error'] <> 0 ){
	    print "<br><br>Error calling get API for UFGroup (aka Profiles)";
	    print_r($result); 
	}else{
		  $standalone_profiles = $result['values'] ; 
		   foreach($standalone_profiles as $cur){
		   	 
		   	$p_type = $cur['group_type']; 
		   	
		   	$type_array = explode( "," , $p_type); // Contributions Activity
		   	if ( false == ( in_array("Participant", $type_array ) || in_array("Organization", $type_array ) || in_array("Membership", $type_array ) || in_array("Household", $type_array ) || in_array("Contributions", $type_array )  || in_array("Activity", $type_array )  )) {
		   	
		   		$key = 'communitynews.standaloneprofile___'.$cur['id'] ;
			    $label = 'Community News & Engagement: Standalone Profile Form: '.$cur['title'].' (id: '.$cur['id'].')'; 
			   
			   $tokens['communitynews'][$key] = $label; 
		   	
		   	
		   	}
		   }
	
	}	
		
	
	}
	
	
	
  function fancytokens_civicrm_tokenValues( &$values, &$contactIDs, $job = null, $tokens = array(), $context = null) {
  	
	    
  if(!empty($tokens['communitynews'])){
        $website_host_name = $_SERVER['SERVER_NAME']; 
        $ssl_in_use = $_SERVER['HTTPS'];
	if( strlen($ssl_in_use) > 0){
		$protocol = "https://"; 
	}else{
		$protocol = "http://";
	}
	
        while( $cur_token_raw = current( $tokens['communitynews'] )){
	 	$tmp_key = key($tokens['communitynews']); 
	 	
	 	$font_size = "10px";
	 	// CiviCRM is buggy here, if token is being used in CiviMail, we need to use the key 
	 	// as the token. Otherwise ( PDF Letter, one-off email, etc) we
	 	// need to use the value. 
	 	$cur_token = ''; 
	 	if(  is_numeric( $tmp_key)){
	 		 $cur_token = $cur_token_raw;
	 	}else{
	 		// Its being used by CiviMail.
	 		$cur_token = $tmp_key;
	 	}
	 	
	 	$token_to_fill = 'communitynews.'.$cur_token;
	 	//print "<br><br>Token to fill: ".$token_to_fill."<br>"; 
	 	
	 	$token_as_array = explode("___",  $cur_token ); 
	
	      
	 	
	 	$partial_token =  $token_as_array[0];
	        
	        if($partial_token == 'standaloneprofile'){
	        	$profile_id = $token_as_array[1];
	           $partial_profile_link_url = $protocol.$website_host_name."/civicrm/profile/edit?reset=1&gid="; 
	          
	               	    
	           if( is_numeric( $profile_id )){
	           
	                  $params = array(
				  'version' => 3,
				  'sequential' => 1,
				  'id' => $profile_id,
				);
			$result = civicrm_api('UFGroup', 'getsingle', $params);
	               	 $link_label = $result['title'];
	               	 
	               foreach ( $contactIDs as $cid ) {
	                   
  		     	    $tmp_checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($cid); 
  		     	     $full_profile_link = $partial_profile_link_url.$profile_id."&cs=".$tmp_checksum."&id=".$cid; 
  		     	    $tmp_profile_html = "<a href='".$full_profile_link."'>".$link_label."</a>";
  		     	    
	                   $values[$cid][$token_to_fill] =  $tmp_profile_html;
	               
	               
	               }
	           
	           
	           }
	        
	        }else if( $partial_token == 'contributionpage' ){
	           $contrib_page_id = $token_as_array[1];
	           $partial_contrib_page_link_url = $protocol.$website_host_name."/civicrm/contribute/transact?reset=1&id="; 
	           
	               	    
	           if( is_numeric( $contrib_page_id )){
	           
	             $params = array(
				  'version' => 3,
				  'sequential' => 1,
				  'id' => $contrib_page_id,
				);
			$result = civicrm_api('ContributionPage', 'getsingle', $params);
	               	 $link_label = $result['title'];
	               	 
	               foreach ( $contactIDs as $cid ) {
	                   
  		     	    $tmp_checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($cid); 
  		     	     $full_contrib_page_link = $partial_contrib_page_link_url.$contrib_page_id."&cs=".$tmp_checksum."&cid=".$cid; 
  		     	    $tmp_contrib_page_html = "<a href='".$full_contrib_page_link."'>".$link_label."</a>";
  		     	    
	                   $values[$cid][$token_to_fill] =  $tmp_contrib_page_html;
	               
	               
	               }
	           
	           
	           }
	        
	        
	        }else if( $partial_token == 'upcomingevents'){
	        
	            $token_date = $token_as_array[1];
	            $date_array = explode("_", $token_date);
	            $date_unit = $date_array[0];
	            $date_number = $date_array[1];
	            
	           
	            $tmp_event_html = ""; 
	            if( is_numeric( $date_number) && ( $date_unit == 'day'  || $date_unit == 'week' || $date_unit == 'month'  )){ 
	            // get event data 
	            $sql = "SELECT e.id , e.summary,  e.title, e.registration_link_text, 
	             date_format( e.start_date, '%W %b %e at %l:%i %p' ) as start_date  ,
	             if( e.is_online_registration = 1 AND 
	               ( e.registration_end_date is null || now() <= e.registration_end_date ) AND
	               ( e.registration_start_date is null || now() >= e.registration_start_date ), '1', '0') as 
	             show_registration_link  
	             FROM civicrm_event e 
	            WHERE e.is_active = 1 AND e.is_public = 1  AND e.is_template <> 1 AND 
	            e.start_date >= now() AND e.start_date <= date_add(now(), INTERVAL ".$date_number." ".$date_unit." ) 
	            ORDER BY e.start_date";
	            
	           // print "<br>SQL: ".$sql; 
	            
	            $event_info_link_url = $protocol.$website_host_name."/civicrm/event/info?reset=1&id="; 
	            $event_register_link_url = $protocol.$website_host_name."/civicrm/event/register?reset=1&id="; 
		
		  	
  		    // print "<br>contacts: ";
	            // print_r($contactIDs);
		    foreach ( $contactIDs as $cid ) {
		       $tmp_event_html = ""; 
		          $dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
  		     while($dao->fetch()){
  		     	$eid = $dao->id;
  		     	$e_title = $dao->title;
  		     	$e_start_date = $dao->start_date; 
  		     	$e_short_summary = $dao->summary; 
  		     	$e_show_registration_link = $dao->show_registration_link; 
  		     	$registration_link_text = $dao->registration_link_text; 
  		     	
  		     	$register_html = ""; 
  		     	if( $e_show_registration_link == "1" ){
  		     	    
  		     	    $tmp_checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($cid); 
  		     	   // print "<br>checksum: ".$tmp_checksum; 
  		     	   // If you want to use the checksum style URL, then add this to the end of the URL
  		     	   // "&cs=".$tmp_checksum."&cid=".$cid
  		     	   $full_register_link = $event_register_link_url.$eid."&cs=".$tmp_checksum."&cid=".$cid; 
  		     	    $register_html = "<br>&nbsp;<b><a href='".$full_register_link."'>".$registration_link_text."</b></a>"; 
  		     	}else{
  		     	    $register_html = ""; 
  		     	}
  		     	
  		     	
  		     	if(strlen( $e_short_summary  ) > 0){
  		     		$summary_html = " <br>&nbsp;&nbsp;&nbsp;&nbsp; ".$e_short_summary; 
  		     	
  		     	}else{
  		     		$summary_html = ""; 
  		     	
  		     	} 
  		     	$tmp_event_html = $tmp_event_html."\n<br><br><a href='".$event_info_link_url.$eid."'>".$e_title."</a> on ".
  		     		$e_start_date.$register_html.$summary_html; 
  		     }
  		     $dao->free(); 
		    
		    // Populate the token value for this contact. 
		      $values[$cid][$token_to_fill] =  $tmp_event_html;
		          
	            }
	            
	            }
                 }else if( $partial_token == 'event_registrationpage'){
                 
                 // communitynews.event_registrationpage___'.$e_id
                 $token_event_id = $token_as_array[1];
	           
	            
	           
	            $tmp_event_html = ""; 
	            if( is_numeric( $token_event_id) ){ 
	            // get event data 
	            $sql = "SELECT e.id , e.summary,  e.title, e.registration_link_text, 
	             date_format( e.start_date, '%W %b %e at %l:%i %p' ) as start_date  ,
	             if( e.is_online_registration = 1 AND 
	               ( e.registration_end_date is null || now() <= e.registration_end_date ) AND
	               ( e.registration_start_date is null || now() >= e.registration_start_date ), '1', '0') as 
	             show_registration_link  
	             FROM civicrm_event e 
	            WHERE e.is_active = 1 AND e.is_public = 1  AND e.is_template <> 1 AND 
	            e.start_date >= now() AND e.id = '".$token_event_id."'  
	            ORDER BY e.start_date";
	            
	           // print "<br>SQL: ".$sql; 
	            
	         //   $event_info_link_url = $protocol.$website_host_name."/civicrm/event/info?reset=1&id="; 
	            $event_register_link_url = $protocol.$website_host_name."/civicrm/event/register?reset=1&id="; 
		
		  	
  		    // print "<br>contacts: ";
	            // print_r($contactIDs);
		    foreach ( $contactIDs as $cid ) {
		       $tmp_event_html = ""; 
		          $dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;
  		     while($dao->fetch()){
  		     	$eid = $dao->id;
  		     	$e_title = $dao->title;
  		     	$e_start_date = $dao->start_date; 
  		     	$e_short_summary = $dao->summary; 
  		     	$e_show_registration_link = $dao->show_registration_link; 
  		     	$registration_link_text = $dao->registration_link_text; 
  		     	
  		     	$register_html = ""; 
  		     	if( $e_show_registration_link == "1" ){
  		     	    
  		     	    $tmp_checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($cid); 
  		     	   // print "<br>checksum: ".$tmp_checksum; 
  		     	   // If you want to use the checksum style URL, then add this to the end of the URL
  		     	   // "&cs=".$tmp_checksum."&cid=".$cid
  		     	   $full_register_link = $event_register_link_url.$eid."&cs=".$tmp_checksum."&cid=".$cid; 
  		     	    $register_html = "<br>&nbsp;<b><a href='".$full_register_link."'>".$registration_link_text."</b></a>"; 
  		     	}else{
  		     	    $register_html = ""; 
  		     	}
  		     	
  		     	
  		     	if(strlen( $e_short_summary  ) > 0){
  		     		$summary_html = " <br>&nbsp;&nbsp;&nbsp;&nbsp; ".$e_short_summary; 
  		     	
  		     	}else{
  		     		$summary_html = ""; 
  		     	
  		     	} 
  		     	$tmp_event_html = $tmp_event_html."\n<br><br>".$e_title." on ".
  		     		$e_start_date.$register_html.$summary_html; 
  		     }
  		     $dao->free(); 
		    
		    // Populate the token value for this contact. 
		      $values[$cid][$token_to_fill] =  $tmp_event_html;
		          
	            }
	            
	            }
                 
                 }
	     next($tokens['communitynews']);    
	 }
  	
  
  }    
	           
  		  
  

  
  }	

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function fancytokens_civicrm_config(&$config) {
  _fancytokens_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function fancytokens_civicrm_xmlMenu(&$files) {
  _fancytokens_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function fancytokens_civicrm_install() {
  return _fancytokens_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function fancytokens_civicrm_uninstall() {
  return _fancytokens_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function fancytokens_civicrm_enable() {
  return _fancytokens_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function fancytokens_civicrm_disable() {
  return _fancytokens_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function fancytokens_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _fancytokens_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function fancytokens_civicrm_managed(&$entities) {
  return _fancytokens_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function fancytokens_civicrm_caseTypes(&$caseTypes) {
  _fancytokens_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function fancytokens_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _fancytokens_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
