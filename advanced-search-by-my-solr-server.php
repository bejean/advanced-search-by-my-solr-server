<?php
/*
 Plugin Name: Advanced Search by My Solr Server
 Plugin URI: http://wordpress.org/extend/plugins/advanced-search-by-my-solr-server/
 Description: Indexes, removes, and updates documents in the Solr search engine.
 Version: 1.0.2
 Author: www.mysolrserver.com
 Author URI: http://www.mysolrserver.com
*/
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

require_once("advanced-search-by-my-solr-server.inc.php");


function mss_plugin_admin_menu() {
	add_options_page('My Solr Server Settings', 'My Solr Server Settings', 'manage_options', 'MySolrServerSettings', 'mss_plugin_admin_settings');
}

function mss_plugin_admin_settings() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	if ( file_exists ( dirname(__FILE__) . '/advanced-search-by-my-solr-server-options-page.php' )) {
		include( dirname(__FILE__) . '/advanced-search-by-my-solr-server-options-page.php' );
	} else {
		_e("<p>Couldn't locate the options page.</p>", 'solr4wp');
	}
}

function mss_admin_head() {
    // include css 
    if (file_exists(dirname(__FILE__) . '/admin.css')) {
        printf(__("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" media=\"screen\" />\n"), plugins_url('/admin.css', __FILE__));
    }
?>
<script type="text/javascript">
	
	function clearConnectStatus() {
        jQuery('#mss_connect_status').html('');
	}

	function clearSaveStatus() {
        jQuery('#mss_save_status').html('');
        location.reload(true);
	}

	var ajax_url = 'options-general.php?page=MySolrServerSettings';

    var $j = jQuery.noConflict();
    
    $j(document).ready(function($) {

    	$('[name=mss_btn_connect]').click(function() {
			var name = $('#mss_id').val();
			var passwd = $('#mss_passwd').val();
  
     	    $.get(ajax_url, {action: 'accountgetinfo', name: name, passwd : passwd }, 
        		function(data) {
     	    		var resp = JSON.parse(data);
					if (resp.status == 'ok') {
						$('#mss_connect_status').html('&nbsp;<img src="<?php print plugin_dir_url(__FILE__); ?>images/success.png">');
						setTimeout("clearConnectStatus()",5000);
						// populate the instances list
						var url = $('#mss_instances').val();
						var instances = resp.instances;
						if (instances.length==0) {
							var options = "<option value=''>not available (connect first)</option>";
						}
						else {
							var options = "<option value=''>choose an instance in the list</option>";
						}
						for (var i=0; i<instances.length; i++) {
							options += "<option value='" + instances[i].url + "'";
							if (instances[i].url==url) options += " selected";
							options += ">" + instances[i].name + "</option>";	
						}
						$('#mss_instances').html(options);
					}
					else {
						$('#mss_connect_status').html('&nbsp;<img src="<?php print plugin_dir_url(__FILE__); ?>images/warning.png">');
					}
					
        		});
        	
            return false;     
        });

    	$('[name=mss_btn_save]').click(function() {
			var name = $('#mss_id').val();
			var passwd = $('#mss_passwd').val();
			var url = $('#mss_instances').val();
  
     	    $.get(ajax_url, {action: 'save', name: name, passwd : passwd, url : url }, 
        		function(data) {
     	    		var resp = JSON.parse(data);
					if (resp.status == 'ok') {
						$('#mss_save_status').html('&nbsp;<img src="<?php print plugin_dir_url(__FILE__); ?>images/success.png">');
						setTimeout("clearSaveStatus()",1000);
					}
					else {
						$('#mss_save_status').html('&nbsp;<img src="<?php print plugin_dir_url(__FILE__); ?>images/warning.png">');
					}
        		});
        	
            return false;     
        });

        
	});
</script>
<?php
}

$action = strtolower(POSTGET("action"));

if ($action=="accountgetinfo") {
	$name = POSTGET("name");
	$passwd = POSTGET("passwd");

	print ($account_info_json = getAccountInfo($url_mysolrserver, $url_extraparam, $name, $passwd));
	exit();
}

if ($action=="save") {
	$name = POSTGET("name");
	$passwd = POSTGET("passwd");
	$url = POSTGET("url");
	
	update_option('mss_id', $name);
	update_option('mss_passwd', encrypt($passwd));
	update_option('mss_url', $url);
	
	// update s4w parameters
	$u = parse_url($url);
	if ($u) {
		$port = ($u['port']=="") ? "80" :  $u['port']; 
		if ($u['host']=="") $port = "";
		update_option('s4w_solr_host', $u['host']);
		update_option('s4w_solr_port', $port);
		update_option('s4w_solr_path', $u['path']);
	}
	
	$arr = array();
	$arr['status']='ok';	
	print(json_encode($arr));
	exit();	
}

add_action('admin_menu', 'mss_plugin_admin_menu');
add_action('admin_head', 'mss_admin_head');

?>