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
require_once("lib/std.encryption.class.inc.php");

$bDev = false;
if ($bDev) {
	$url_mysolrserver = 'http://localhost/';
	$url_extraparam = "&config=config-dev.ini";
}
else {
	$url_mysolrserver = "http://manager.mysolrserver.com/";
	$url_extraparam = "";
}
$url_mysolrserver .= '/mysolrserver_ws/manager.php';

function encrypt($value) {
	$crypt = new encryption_class;
	return $crypt->encrypt("mysolrserver", $value, strlen($value));
}
function decrypt($value) {
	$crypt = new encryption_class;
	return $crypt->decrypt("mysolrserver", $value);

}

function POSTGET($param){
	if (isset($_POST[$param]) && $_POST[$param]!="")
		return $_POST[$param];
	if (isset($_GET[$param]) && $_GET[$param]!="")
		return $_GET[$param];
	return "";
}

function check_w4s() {
	if (get_option("s4w_solr_initialized") != '1') {

		update_site_option('s4w_index_all_sites', '0');
		update_option('s4w_solr_host', 'localhost');
		update_option('s4w_solr_port', '8983');
		update_option('s4w_solr_path', '/solr');
		update_option('s4w_index_pages', '1');
		update_option('s4w_index_posts', '1');
		update_option('s4w_delete_page', '1');
		update_option('s4w_delete_post', '1');
		update_option('s4w_private_page', '1');
		update_option('s4w_private_post', '1');
		update_option('s4w_output_info', '1');
		update_option('s4w_output_pager', '1');
		update_option('s4w_output_facets', '1');
		update_option('s4w_exclude_pages', ''); 
		update_option('s4w_num_results', '5');
		update_option('s4w_cat_as_taxo', '1');
		update_option('s4w_solr_initialized', '1');
		update_option('s4w_max_display_tags', '10');
		update_option('s4w_facet_on_categories', '1');
		update_option('s4w_facet_on_tags', '1');
		update_option('s4w_facet_on_author', '1');
		update_option('s4w_facet_on_type', '1');
		update_option('s4w_enable_dym', '1');
		update_option('s4w_index_comments', '1');
		update_option('s4w_connect_type', 'solr');
		update_option('s4w_index_custom_fields', ''); 
		update_option('s4w_facet_on_custom_fields', 'NA'); 

		//		<p>It seems that <strong>Solr for Wordpress plugin</strong> is not initialized. Prior to continue with <strong>My Solr Server plugin</strong> configuration, install
		//		<a href='http://wordpress.org/extend/plugins/solr-for-wordpress/' target='_mss'>Solr for Wordpress plugin</a> then go at least once in <strong>Solr Options</strong> page.
		//	</div>
		//</div>
		//</div>
	}
}

function getAccountInfo($url_mysolrserver, $url_extraparam, $mss_id, $mss_passwd) {
	$url = $url_mysolrserver . '?action=accountgetinfo&name=' . $mss_id . '&passwd=' . $mss_passwd . '&type=wp' . $url_extraparam;
	log_message("getAccountInfo - url = " . $url_mysolrserver);
	$json = file_get_contents($url);
	log_message("getAccountInfo - json = " . $json);
	return $json;
}

function log_message($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}