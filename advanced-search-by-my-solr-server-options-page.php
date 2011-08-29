<?php
/*  
    Copyright (c) 2011 www.mysolrserver.com

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
*/
//require_once("advanced-search-by-my-solr-server.inc.php");
?>
  
 <div class="wrap">
  	<div id="icon-options-general" class="icon32"><br /></div>
	<div id="mss_admin">

  		<h2>My Solr Server Settings</h2>

		<p><a href='http://wordpress.org/extend/plugins/solr-for-wordpress/' target='_mss'>Solr for Wordpress plugin</a> have to be installed prior to use 
		<a href='http://www/mysolrserver.com/' target='_mss'>My Solr Server</a> plugin. <strong>Solr for Wordpress</strong> plugin replaces the default WordPress search with Solr search.</p>

		<p><strong>Solr for Wordpress plugin</strong> requieres you to install <a href='http://lucene.apache.org/solr/' target='_mss'>Solr</a>. If you don't 
		have the time or resources to install, configure and maintain <strong>Solr</strong>, <a href='http://www/mysolrserver.com/' target='_mss'>My Solr Server</a> can host it for you !</p>

		<p>Before setting up <strong>My Solr Server plugin</strong>, you need to <a href='http://manager.mysolrserver.com/account.php' target='_mss'>create an account on My Solr Server</a> (one month free trial).</p>
		
<?php 
check_w4s()	;
$mss_id = get_option('mss_id', '');
$mss_passwd = decrypt(get_option('mss_passwd', ''));
$mss_url = get_option('mss_url', '');
$mss_solr_host = get_option('mss_solr_host', '');
$mss_solr_port = get_option('mss_solr_port', '');
$mss_solr_path = get_option('mss_solr_path', '');
$s4w_solr_host = get_option('s4w_solr_host', '');
$s4w_solr_port = get_option('s4w_solr_port', '');
$s4w_solr_path = get_option('s4w_solr_path', '');

$account_plan='';
$account_status='';
$account_expire='';

global $url_mysolrserver;
global $url_extraparam ;

$connected = false;

if ($mss_id!='' && $mss_passwd!='') {
	$account_info_json = getAccountInfo($url_mysolrserver, $url_extraparam, $mss_id, $mss_passwd);
	$account_info = json_decode ($account_info_json, true);
	//print_r($account_info);
	if ($account_info['status']=='ok') {
		$connected = true;
		$account_plan=$account_info['account_plan'];
		$account_status= ($account_info['account_enabled'] == '1') ? "Enabled" : "Disabled" ;
		$account_expire = strftime("%B %e, %G", strtotime($account_info['account_expiry'])) . " (" . $account_info['account_expirydays'] . " days)";
	}
	$account_instances = $account_info['instances'];
}
?>		
		
	<hr />
		<form method="post" action="">
		<h3><?php _e('My Solr Server account connexion', 'mss') ?></h3>

		<table>
		<tr>
			<td class="label"><label><?php _e('User name', 'mss') ?></label></td>
			<td><input type="text" name="mss_id" id="mss_id" value="<?php print($mss_id); ?>" autocomplete="off" /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="label"><label><?php _e('Password', 'mss') ?></label></td>
			<td><input type="password" name="mss_passwd" id="mss_passwd" value="<?php print($mss_passwd); ?>" autocomplete="off" /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="label">&nbsp;</td>
			<td colspan="2"><input class="button-primary" type="button" name="mss_btn_connect" id="mss_btn_connect" value="Connect" /><span id="mss_connect_status"></span></td>
		</tr>
		<tr>
			<td class="label"><label><?php _e('Select a Solr instance to be used with this blog', 'mss') ?></label></td>
			<td><select name="mss_instances" id="mss_instances">
<?php 	
$url_matching = false;	
$message = "";	
if (!is_array($account_instances) || (count($account_instances)==0)) {
	if ($connected) {
		print ('<option value="">not instance available for this account</option>');
		$message = "Go to <a href='http://manager.mysolrserver.com/account.php' target='_mss'>My Solr Server Manager</a> and create a Wordpress Solr instance for this account !";
	}
	else {
		print ('<option value="">not available (connect first)</option>');
	}
}
else {
	print ('<option value="">choose an instance in the list</option>');
	for ($i=0;$i<count($account_instances);$i++) {
		print ('<option value="' . $account_instances[$i]['url'] . '"');
		if ($mss_url==$account_instances[$i]['url']) {
			print (' selected');
			$url_matching = true;
		}
		print ('>' . $account_instances[$i]['name'] . '</option>');
	}
}	
?>			
			</select>
<?php print ($message); ?>
			</td>	
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="label">&nbsp;</td>
			<td colspan="2"><input class="button-primary" type="button" name="mss_btn_save" id="mss_btn_save" value="Save Changes" /><span id="mss_save_status"></span></td>
		</tr>
		</table>
		</form>

<?php 
if ($account_plan!="") {
?>		
	<hr />
		<h3><?php _e('My Solr Server account details', 'mss') ?></h3>
		User name : <?php echo $mss_id; ?><br/>
		Plan type : <?php echo $account_plan; ?><br/>
		Plan status : <?php echo $account_status; ?><br/>
		Plan expires : <?php echo $account_expire; ?><br/><br/>

	<hr />
		<h3><?php _e('My Solr Server instance details', 'mss') ?></h3>
		Solr instance url : <?php if ($url_matching) print ($mss_url); else _e('no instance selected !', 'mss') ?><br/><br/>

<?php 
	if ($mss_url!="" && $url_matching) {
?>		
	<hr />
		<h3><?php _e('Configure Solr for Wordpress plugin', 'mss') ?></h3>
		<p>In order to configure <strong>Solr for Wordpress plugin</strong>, you have to go in <a href='options-general.php?page=solr-for-wordpress/solr-for-wordpress.php'>Solr for Wordpress plugin options page</a>.</p>
		<p>Setup only <strong>Indexing Options</strong> or <strong>Result Options</strong>, but do not update <strong>Configure Solr</strong> settings!</p>
<?php 
	}
}
?>
	</div>
  	</div>
 </div>