<script>

	function arsCalcPercentage(){
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
	}
	

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
	$user_id = get_current_user_id();
	
	$current_user = wp_get_current_user();
	$role = $current_user->roles;
	
	if($role[0]=="administrator"){
		$where = " user_role = 'administrator'";
	}
	else{
		$where = " user_id = '".$user_id."'";
	}
	$get_data = "SELECT * 
				FROM ".$tbl_revenue_setting."
				WHERE $where";
	$exec_query = mysql_query($get_data);
	$revenue_data = array();
	while($val = mysql_fetch_array($exec_query)){
		$revenue_data[] = $val;
	}
			
	if(isset($_POST['add_aff_id'])){
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
						'user_role' => $role[0] 
					 ) 
				 );	
			}else if(strpos($ind, 'amazon') !== false and count($revenue_data)>0){
				$domain_name = str_replace('_', '.', $ind);
				if($role[0]=="administrator"){
					$wpdb->update( 
							$tbl_revenue_setting, 
							array( 
								'affiliate_id' => $value
							), 
							array( 'domain_name' => $domain_name, 'user_role' => 'administrator') 
						);
				}else{
					$wpdb->update( 
							$tbl_revenue_setting, 
							array( 
								'affiliate_id' => $value
							), 
							array( 'domain_name' => $domain_name, 'user_id' => $user_id) 
						);
				}
			}
		}
		if(!empty($amount_share_author) and !empty($amount_your) and !empty($amount_plugin)){
			$amount_data = array( 'author_amount'=> $amount_share_author, 'your_amount'=>$amount_your , 'plugin_amount'=>$amount_plugin );
			update_option( "Sharing_revenue_amount", serialize( $amount_data ),true );
		}
	}
	
	$get_data = "SELECT * 
				 FROM ".$tbl_revenue_setting."
				 WHERE $where";
	$exec_query = mysql_query($get_data);
	$revenue_data = array();
	while($val = mysql_fetch_array($exec_query)){
		$revenue_data[] = $val;
	}

//	print_r( maybe_unserialize('Sharing_revenue_amount'));
	
	$sharingAmounts = get_option('Sharing_revenue_amount');
?>

<div class="wrap">
<h2 id="add-new-affiliate-id"> Add New Affiliate ID</h2>

<p>Enter admin affiliate IDs. If you have not set up IDs for each country just leave it blank for 
now.</p>
	<form id="add_affiliate_id" name="add_affiliate_id" method="post" action="" onsubmit="return arsCalcPercentage();">
		
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
<?php if($role[0]=="administrator") { 
			$get_amount_data = maybe_unserialize($sharingAmounts);
?>	
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
		<p class="submit"><input type="submit" value="Add Amazon Affiliates" class="button button-primary" id="add_aff_id" name="add_aff_id"></p>
	</form>
</div>
