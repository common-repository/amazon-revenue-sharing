<?php
/*
Plugin Name: Amazon Revenue Sharing
Description: This plugin is for Rewarding author’s hard work by sharing Amazon Associate revenue on each author’s specific post and localizing links to maximize revenue. 
Version: 1.1
Author: philipbaxter
Author URI: http://www.super-serious.com/
License: GPL2
*/

/************Include Admin Menu Pages************/ 	
function ars_setting_callback(){
	global $wpdb;
	include('revenue-setting.php');
}

/************Registering Admin Menus************/ 			
function ars_setting_admin_menu(){
	add_menu_page('Revenue Sharing setting', 'Revenue Sharing setting', 'administrator', 'revenue_setting', 'ars_setting_callback');
}
add_action("admin_menu", "ars_setting_admin_menu");

/************Plugin Activation Hook************/ 
function ars_setting_activation_hook(){
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$create_tbl_revenue_setting = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."revenue_setting` (
								  `id` int(11) NOT NULL AUTO_INCREMENT,
								  `domain_name` text NOT NULL,
								  `affiliate_id` text NOT NULL,
								  `user_id` int(11),
								  `user_role` ENUM('administrator','author','developer'),
								  PRIMARY KEY (`id`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";
	dbDelta($create_tbl_revenue_setting);
	
	$create_tbl_clicks_data = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."clicks_data` (
								  `id` int(11) NOT NULL AUTO_INCREMENT,
								  `post_id` int(11) NOT NULL,
								  `author_id` int(11) NOT NULL,
								  `user_role` ENUM('admin','author','developer'),
								  `click_time` DATETIME,
								  PRIMARY KEY (`id`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";
	dbDelta($create_tbl_clicks_data);
	
	$domains = array('amazon.com' => 'philipbaxter-20', 'amazon.co.uk' => 'philipbaxter-21', 'amazon.ca' => 'philipbaxter03-20', 'amazon.de' => 'philipbaxter00-21', 'amazon.fr' => 'philipbaxter01-21', 'amazon.jp' => 'philipbaxter-22', 'amazon.it' => 'philipbaxter04-21', 'amazon.cn' => 'philipbaxter-23', 'amazon.es' => 'philipbaxter02-21', 'amazon.in' => 'gemyga00-21');
	
	foreach($domains as $k=>$affiliate_id){
		$domain_name = $k;
		$aff_id = $affiliate_id;
		
		$insert_developer_id = "INSERT INTO ".$wpdb->prefix."revenue_setting SET 
								`domain_name` = '".$domain_name."',
								`affiliate_id` = '".$aff_id."',
								`user_role` = 'developer' ";
		$exec_insert_developer_id = mysql_query($insert_developer_id);
		
	}
	
}

register_activation_hook( __FILE__, 'ars_setting_activation_hook' );
/************Plugin DeActivation Hook************/ 
function ars_deactivation_hook(){
 	global $wpdb;
    $revenue_sharing_tbl = $wpdb->prefix."revenue_setting";
	$tbl_clicks_data = $wpdb->prefix."clicks_data";
	$wpdb->query("DROP TABLE IF EXISTS $revenue_sharing_tbl");
	$wpdb->query("DROP TABLE IF EXISTS $tbl_clicks_data");
	delete_option( 'Sharing_revenue_amount' ); 
 
}
register_deactivation_hook( __FILE__, 'ars_deactivation_hook' );

add_action('init', 'arsStartSession', 1);
function arsStartSession() {
    if(!session_id()) {
        session_start();
    }
}
//amazon fields on user profile page start here//

add_action( 'show_user_profile', 'ars_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'ars_show_extra_profile_fields' );

function ars_show_extra_profile_fields( $user ) { 
?>
	<script>
	jQuery(document).ready(function(e) {
		jQuery('#wpfooter').hide();
    });
	
	jQuery( "#your-profile" ).submit(function( event ) {
		var getAuthorShare = jQuery('#amount_share_author').val();		
		var getYourShare = jQuery('#amount_your').val();		
		var getPluginShare = jQuery('#amount_plugin').val();		
		
		var authorShare = getAuthorShare.split('%');
		var yourShare = getYourShare.split('%');
		var pluginShare = getPluginShare.split('%');	
		
		if(authorShare[0]<0 || yourShare[0]<0 || pluginShare[0]<0){
			alert('Negative value is not allowed');
			return false;
		}else if(parseInt(authorShare[0])+parseInt(yourShare[0])+parseInt(pluginShare[0])!= 100){
				alert('Values must add to 100%');
				return false;
		}else{
				return true;
		}
	});

	var shareId, shareVal;
	function arsShareRevenue(shareId, shareVal){
		var splitPerSign = shareVal.split('%');
		if(!isNaN(splitPerSign[0]) && splitPerSign[0]>=0 && splitPerSign[0]<=100 && splitPerSign[0]!=''){
			
			if(shareId=="amount_share_author"){
			
				var calcShare = parseInt(shareVal)+parseInt(jQuery('#amount_plugin').val());
				var yourShare = 100-parseInt(calcShare);
				jQuery('#'+shareId).val(splitPerSign[0]+'%');
				jQuery('#amount_your').val(yourShare+'%');
				
			}else if(shareId=="amount_your"){
			
				var calcShare = parseInt(shareVal)+parseInt(jQuery('#amount_plugin').val());
				var authorShare = 100-parseInt(calcShare);
				jQuery('#'+shareId).val(splitPerSign[0]+'%');
				jQuery('#amount_share_author').val(authorShare+'%');
				
			}else{
				
				var yourShare = jQuery('#amount_your').val()-parseInt(shareVal);
				var authorShare = 100 - yourShare;
				jQuery('#'+shareId).val(splitPerSign[0]+'%');
				jQuery('#amount_your').val('0');
				jQuery('#amount_share_author').val('0');
				alert('Please set author and your amount again');
				
			}
			
		}else{
		
			alert('Amount must be numaric, and from 0-100');
			jQuery('#'+shareId).val('');
			return false;
			
		}
	}
</script>



<?php	
    
    global $wpdb;
	global $wp_roles;
	
	$tbl_revenue_setting = $wpdb->prefix."revenue_setting";
	$user_id = $user->ID;
	$my_id = get_current_user_id();
	
	$current_user = wp_get_current_user();
	$role = $current_user->roles;
	
	if($role[0]=="administrator" and $user_id!=$my_id){
		$where = " user_id = '".$user_id."'";
	}
	else if($role[0]!="administrator" and $user_id==$my_id){
		$where = " user_id = '".$user_id."'";
	}
	else{
		$where = " user_role = 'administrator'";
	}
	//$where = " user_id = '".$user_id."'";
	$get_data = "SELECT * 
				FROM ".$tbl_revenue_setting."
				WHERE $where";
	$exec_query = mysql_query($get_data);
	$revenue_data = array();
	while($val = mysql_fetch_array($exec_query)){
		$revenue_data[] = $val;
	}
	$get_amount_data = unserialize(get_option('Sharing_revenue_amount'));
 ?>   

    <h2 id="add-new-affiliate-id"> Add New Affiliate ID</h2>
    
    <p>Enter admin affiliate IDs. If you have not set up IDs for each country.</p>
          <table class="form-table">
                <tbody>
                 <tr>
                    <th scope="row"><label for="amazon.com">Amazon.com</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[0]['affiliate_id'];} ?>" id="amazon_com" name="amazon_com"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.co.uk ">Amazon.co.uk</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[1]['affiliate_id'];} ?>" id="amazon_co_uk" name="amazon_co_uk"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.ca">Amazon.ca</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[2]['affiliate_id'];} ?>" id="amazon_ca" name="amazon_ca"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.de">Amazon.de</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[3]['affiliate_id'];} ?>" id="amazon_de" name="amazon_de"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.fr">Amazon.fr</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[4]['affiliate_id']; }?>" id="amazon_fr" name="amazon_fr"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.jp">Amazon.jp</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[5]['affiliate_id'];} ?>" id="amazon_jp" name="amazon_jp"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.it">Amazon.it</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[6]['affiliate_id'];} ?>" id="amazon_it" name="amazon_it"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.cn">Amazon.cn</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[7]['affiliate_id'];} ?>" id="amazon_cn" name="amazon_cn"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.es">Amazon.es</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[8]['affiliate_id'];} ?>" id="amazon_es" name="amazon_es"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="Amazon.in">Amazon.in</label></th>
                    <td><input type="text" value="<?php if(!empty($revenue_data)){ echo $revenue_data[9]['affiliate_id'];} ?>" id="amazon_in" name="amazon_in"></td>
                </tr>
                
                </tbody>
            </table>
    <?php if($role[0]=="administrator" and $user_id==$my_id) { ?>	
    <h2 id="revenue-sharing"> Revenue Sharing </h2>
            <table class="form-table">
                <tbody>
                     <tr>
                        <th scope="row"><label for="Amount_share_with_author">Enter the amount you would like to share with authors</label></th>
                        <td>
                            <input type="text" onblur="arsShareRevenue(this.id, this.value);" value="<?php echo $get_amount_data['author_amount']; ?>" id="amount_share_author" name="amount_share_author">
                            <p class="description indicator-hint">If you enter 25 then on posts by other authors 25 out of 100 links will be with their IDs and 75 will be with yours.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="Your_amount">Your Amount</label></th>
                        <td>
                            <input type="text" onblur="arsShareRevenue(this.id, this.value);" value="<?php echo $get_amount_data['your_amount']; ?>" id="amount_your" name="amount_your">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="Amount_share_with_plugin">Enter the amount you would like to donate to the creator of this plugin</label></th>
                        <td>
                            <input type="text" onblur="arsShareRevenue(this.id, this.value);" value="<?php if($get_amount_data['plugin_amount']!=""){ echo $get_amount_data['plugin_amount'];} else { echo '0%';} ?>" id="amount_plugin" name="amount_plugin">
                            <p class="description indicator-hint">Please consider donating a portion of your links to help support this plugin. Any amount put here will help tremendously!
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
    <?php }  ?>
    	<input type="hidden" name="add_aff_id" id="add_aff_id" value="Add affiliate data" />
    </div>
<?php }

add_action( 'personal_options_update', 'ars_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'ars_save_extra_profile_fields' );

function ars_save_extra_profile_fields( $user_id ) {
	
	global $wpdb;
	global $wp_roles;
	
	$tbl_revenue_setting = $wpdb->prefix."revenue_setting";
	$my_id = get_current_user_id();
	
	$user_info = get_userdata($user_id);
    $user_role = implode(', ', $user_info->roles);
	
	
	$current_user = wp_get_current_user();
	$role = $current_user->roles;
	
	$myRole = $role[0];
	
	if($role[0]=="administrator" and $user_id!=$my_id){
		
		$where = " user_id = '".$user_id."'";
	}
	else if($role[0]!="administrator" and $user_id==$my_id){
		
		$where = " user_id = '".$user_id."'";
	}
	else{
		
		$where = " user_role = 'administrator'";
	}
	
	$get_data = "SELECT * 
				FROM ".$tbl_revenue_setting."
				WHERE $where";
	$exec_query = mysql_query($get_data);
	$revenue_data = array();
	while($val = mysql_fetch_array($exec_query)){
		$revenue_data[] = $val;
	}
	
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	if(!empty($_POST['add_aff_id'])){

		foreach($_POST as $ind=>$value){
			$$ind = $value;
			if(strpos($ind, 'amazon') !== false and count($revenue_data)==0){
				$domain_name = str_replace('_', '.', $ind);
				$wpdb->insert( 
					$tbl_revenue_setting, 
					 array( 
						'domain_name' => $domain_name, 
						'affiliate_id' => $value,
						'user_id' => $user_id,
						'user_role' => $user_role 
					 ) 
				 );	
			}else if(strpos($ind, 'amazon') !== false and count($revenue_data)>0){
				$domain_name = str_replace('_', '.', $ind);
				
				if($myRole=="administrator" and $user_id!=$my_id){
					
					$wpdb->update( 
							$tbl_revenue_setting, 
							array( 
								'affiliate_id' => $value
							), 
							array( 'domain_name' => $domain_name, 'user_id' => $user_id) 
						);
						
				}else if($myRole!="administrator" and $user_id==$my_id){
					
					$wpdb->update( 
							$tbl_revenue_setting, 
							array( 
								'affiliate_id' => $value
							), 
							array( 'domain_name' => $domain_name, 'user_id' => $user_id) 
						);
						
				}else{
	
					$wpdb->update( 
							$tbl_revenue_setting, 
							array( 
								'affiliate_id' => $value
							), 
							array( 'domain_name' => $domain_name, 'user_role' => 'administrator') 
						);
				
				}
			}
		}
		if(!empty($amount_share_author) and !empty($amount_your) and !empty($amount_plugin)){
			$amount_data = array( 'author_amount'=> $amount_share_author, 'your_amount'=>$amount_your , 'plugin_amount'=>$amount_plugin );
			update_option( "Sharing_revenue_amount", serialize( $amount_data ) );
		}
	}
}

add_action( 'delete_user', 'ars_delete_affiliate_ids' );
function ars_delete_affiliate_ids( $user_id ) {
	
	global $wpdb;
	$tbl_revenue_setting = $wpdb->prefix."revenue_setting";
	$wpdb->delete( $tbl_revenue_setting , array( 'user_id' => $user_id ) );
 
}






//amazon fields on user profile page end here//


require_once('geoplugin.class.php');

add_filter('the_content','ars_replace_content');
function ars_replace_content($content)
{

	global $wpdb;
	global $post;
	global $wp_query;
	
	$geoplugin = new geoPlugin();
	$geoplugin->locate();
	
    $code = $geoplugin->countryCode;
	$countryCode = strtolower($code);
	
	if($countryCode=="uk"){
		$countryCode = "co.uk";
	}
	
	
	$tbl_clicks_data = $wpdb->prefix."clicks_data";
	$author_id=$post->post_author;
	//$postid = get_the_ID();
	$postid = $post->ID;
	$_SESSION['turn'] = "";
	
	$get_amount_data = unserialize(get_option('Sharing_revenue_amount'));
	
	
	$revenue_sharing_tbl = $wpdb->prefix."revenue_setting";
	$tbl_clicks_data = $wpdb->prefix."clicks_data";

	
	$checkDomainName = 'amazon.'.$countryCode;
	$checkAmazonDomain = "SELECT * 
						  FROM ".$revenue_sharing_tbl."
						  WHERE `domain_name` = '".$checkDomainName."'";
	$exec_query = mysql_query($checkAmazonDomain);
	$numRows = mysql_num_rows($exec_query);

	
	
	//total clicks
	$get_total_clicks = "SELECT * 
					 	 FROM ".$tbl_clicks_data."
					     WHERE `id` > 0 AND `author_id` = '".$author_id."'";
	$exec_total_clicks = mysql_query($get_total_clicks);					 

	
	
	//author clicks
	$get_author_clicks = "SELECT * 
						  FROM ".$tbl_clicks_data."
						  WHERE `user_role` = 'author' AND `author_id` = '".$author_id."'";
	$exec_author_query = mysql_query($get_author_clicks);
	$author_clicks_data = array();
	while($author_val = mysql_fetch_array($exec_author_query)){
		$author_clicks_data[] = $author_val;
	} 
	
	//admin clicks
	$get_admin_clicks = "SELECT * 
						 FROM ".$tbl_clicks_data."
						 WHERE `user_role` = 'admin' AND `author_id` = '".$author_id."'";
	$exec_admin_query = mysql_query($get_admin_clicks);
	$admin_clicks_data = array();
	while($admin_val = mysql_fetch_array($exec_admin_query)){
		$admin_clicks_data[] = $admin_val;
	} 
	//developer clicks
	$get_developer_clicks = "SELECT * 
						     FROM ".$tbl_clicks_data."
						     WHERE `user_role` = 'developer' AND `author_id` = '".$author_id."'";
	$exec_developer_query = mysql_query($get_developer_clicks);
	$developer_clicks_data = array();
	while($developer_val = mysql_fetch_array($exec_developer_query)){
		$developer_clicks_data[] = $developer_val;
	}
	 
	$total_clicks = mysql_num_rows($exec_total_clicks);
	$author_clicks = count($author_clicks_data);
	$admin_clicks = count($admin_clicks_data);
	$developer_clicks = count($developer_clicks_data);
	
	
	$regex = '|<a.*?href=[""\'](?<url>.*?amazon.*?)[""\'].*?>.*?</a>|i';
	
	preg_match_all($regex, $content, $matches, PREG_PATTERN_ORDER);
	
	$filtered_matches = $matches['url'];

	if(count($filtered_matches)>0){
		foreach ($filtered_matches as $key => $match) {
			if(strpos($match,'amazon.com')!=false){
				$domain_name = " AND domain_name = 'amazon.com'";
			}
			else if(strpos($match,'amazon.co.uk')!=false){
				$domain_name = " AND domain_name = 'amazon.co.uk'";
			}
			else if(strpos($match,'amazon.ca')!=false){
				$domain_name = " AND domain_name = 'amazon.ca'";
			}
			else if(strpos($match,'amazon.de')!=false){
				$domain_name = " AND domain_name = 'amazon.de'";
			}
			else if(strpos($match,'amazon.fr')!=false){
				$domain_name = " AND domain_name = 'amazon.fr'";
			}
			else if(strpos($match,'amazon.jp')!=false){
				$domain_name = " AND domain_name = 'amazon.jp'";
			}
			else if(strpos($match,'amazon.it')!=false){
				$domain_name = " AND domain_name = 'amazon.it'";
			}
			else if(strpos($match,'amazon.cn')!=false){
				$domain_name = " AND domain_name = 'amazon.cn'";
			}
			else if(strpos($match,'amazon.es')!=false){
				$domain_name = " AND domain_name = 'amazon.es'";
			}
			else {
				$domain_name = " AND domain_name = 'amazon.in'";
			}
		
	
			// first time case in case of no click so far
			if($total_clicks==0){
				
				$get_affiliate_data = "SELECT * 
									  FROM ".$revenue_sharing_tbl."
									  WHERE user_role = 'administrator' $domain_name";
				$exec_query = mysql_query($get_affiliate_data);
				$affiliate_data = mysql_fetch_array($exec_query);

				if(!isset($_SESSION['turn'])){
					$_SESSION['turn'] = 'admin';
				}
				
			} else{
		
				//author clicks ratio
				
				$author_click_percent = ($author_clicks / $total_clicks) * 100;
				$author_click_per = round($author_click_percent);
				
				//admin clicks ratio
				$admin_click_percent = ($admin_clicks / $total_clicks) * 100;
				$admin_click_per = round($admin_click_percent);
				
				//developer clicks ratio	
				$developer_click_percent = ($developer_clicks / $total_clicks) * 100;
				$developer_click_per = round($developer_click_percent);
				
		
				
				$affiliate_data = array();
				
				
				// replace % sign
				
				$admin_precentage = preg_replace('/[\%,]/', '', $get_amount_data['your_amount'] );
				$author_precentage = preg_replace('/[\%,]/', '', $get_amount_data['author_amount'] );
				$developer_precentage = preg_replace('/[\%,]/', '', $get_amount_data['plugin_amount'] );
				
				
				//$admin_pre = $get_amount_data['your_amount']   //explod this 
				
				
				if( intval ($admin_click_per) <  intval ($admin_precentage)){
		
					$get_affiliate_data = "SELECT * 
										  FROM ".$revenue_sharing_tbl."
										  WHERE user_role = 'administrator' $domain_name";
					$exec_query = mysql_query($get_affiliate_data);
					$affiliate_data = mysql_fetch_array($exec_query);

					if(!isset($_SESSION['turn'])){
						$_SESSION['turn'] = 'admin';
					}
		
				}else if( intval ($author_click_per) <  intval ($author_precentage)){
		
					$get_affiliate_data = "SELECT * 
										  FROM ".$revenue_sharing_tbl."
										  WHERE user_id = '".$author_id."' $domain_name";
					$exec_query = mysql_query($get_affiliate_data);
					$affiliate_data = mysql_fetch_array($exec_query);
					
					if(!isset($_SESSION['turn'])){
						$_SESSION['turn'] = 'author';
					}
					
				}else{
				
				   $get_affiliate_data = "SELECT * 
										  FROM ".$revenue_sharing_tbl."
										  WHERE user_role = 'developer' $domain_name";
					$exec_query = mysql_query($get_affiliate_data);
					$affiliate_data = mysql_fetch_array($exec_query);
					
					if(!isset($_SESSION['turn'])){
						$_SESSION['turn'] = 'developer';
					}
					
				}
			}
			
		    $content = str_replace('href="http://www.amazon','target=_blank" href="http://www.amazon',$content);
			
			$tag = $affiliate_data['affiliate_id'];	
			$orig_str = $match;
			$pos = strpos($orig_str,'tag'); // if tag exist in url 
			
			
			if($pos!=false){
				if(strpos($orig_str,'tag%')!=false){
					
					$explodePerc = explode('tag%',$orig_str);
					$explodeNextPer = explode('%',$explodePerc[1]);
					$explodedTag = $explodeNextPer[0];
					
				}else{
					
					$explodeTag = explode('tag=',$orig_str);
					$explodedTag = $explodeTag[1];
					
				}
				if($numRows>0){
					$explodeAmazonDomain = explode('http://www.amazon.',$orig_str);
					$explodeHost = explode('/',$explodeAmazonDomain[1]);
				
					$replaceHost = str_replace($explodeHost[0],$countryCode,$orig_str);
					$content = str_replace($orig_str, $replaceHost, $content);
					
					$changeTag = $tag;
			
					$new_str = str_replace($explodedTag, $changeTag, $replaceHost);
					$content = str_replace($replaceHost, $new_str, $content);
				}
				else{
					$explodeAmazonDomain = explode('http://www.amazon.',$orig_str);
					$explodeHost = explode('/',$explodeAmazonDomain[1]);
				
					$replaceHost = str_replace($explodeHost[0],'com',$orig_str);
					$content = str_replace($orig_str, $replaceHost, $content);
								
					$changeTag = $tag;
					
					$new_str = str_replace($explodedTag, $changeTag, $replaceHost);
					$content = str_replace($replaceHost, $new_str, $content);
				}
				
			}else{
				$qMarkPos = strpos($orig_str,'?'); //check ? sign
				if($qMarkPos!=false){
					$new_str = '"'.$orig_str.'&tag='.$tag.'"';
				}else{
					$new_str = '"'.$orig_str.'?tag='.$tag.'"';
				}
				if($numRows>0){
					
					$content = str_replace('"'.$orig_str.'"', $new_str, $content);
					
					$explodeAmazonDomain = explode('http://www.amazon.',$orig_str);
					$explodeHost = explode('/',$explodeAmazonDomain[1]);
					
					$replaceHost = str_replace($explodeHost[0],$countryCode,$orig_str);
					$content = str_replace($orig_str, $replaceHost, $content);
				
				}
				else{
					
					$content = str_replace('"'.$orig_str.'"', $new_str, $content);
					
					$explodeAmazonDomain = explode('http://www.amazon.',$orig_str);
					$explodeHost = explode('/',$explodeAmazonDomain[1]);
					
					$replaceHost = str_replace($explodeHost[0],'com',$orig_str);
					$content = str_replace($orig_str, $replaceHost, $content);
				}

			}
			
	}
}
		if(is_single() && is_main_query()){

			$postId = $wp_query->post->ID;
			$click_time = date('Y-m-d H:i:s');
			if($postid==$postId){
				$wpdb->insert( 
		
						$tbl_clicks_data, 
		
						 array( 
		
							'post_id' => $postId, 
		
							'author_id' => $author_id,
		
							'user_role' => $_SESSION['turn'],
		
							'click_time' => $click_time
		
						 ) 
		
					 );
		
				
		
				unset($_SESSION['turn']);
		
			}
		}
	
	return $content;
}
?>