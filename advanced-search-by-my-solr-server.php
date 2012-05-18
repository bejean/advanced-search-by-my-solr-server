<?php
/*
 Plugin Name: Advanced Search by My Solr Server
Plugin URI: http://wordpress.org/extend/plugins/advanced-search-by-my-solr-server/
Description: Indexes, removes, and updates documents in the Solr search engine.
Version: 2.0.2
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
/*
 Copyright (c) 2011 Matt Weber

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
	add_options_page('Advanced Search by My Solr Server', 'Advanced Search by My Solr Server', 'manage_options', 'MySolrServerSettings', 'mss_plugin_admin_settings');
}

function mss_plugin_admin_settings() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	if ( file_exists ( dirname(__FILE__) . '/advanced-search-by-my-solr-server-options-page.php' )) {
		include( dirname(__FILE__) . '/advanced-search-by-my-solr-server-options-page.php' );
	} else {
		_e("<p>Couldn't locate the options page.</p>", 'solrmss');
	}
}

function mss_admin_head() {
	global $this_plugin_dir_url;
	// include css

	if (file_exists(dirname(__FILE__) . '/admin.css')) {
		printf(__("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" media=\"screen\" />\n"), $this_plugin_dir_url . 'admin.css');
	}
	?>
<script type="text/javascript">
	
	function clearConnectStatus() {
        jQuery('#mss_connect_status').html('');
	}

	function clearSaveStatus(id) {
        jQuery(id).html('');
	}

	var ajax_url = 'options-general.php?page=MySolrServerSettings';

    var $j = jQuery.noConflict();
    
    function mss_switch1() {
        if ($j('#selfhosted').is(':checked')) {
            $j('#solr_admin_tab_mysolrserver').css('display', 'none');
            $j('#mss_solr_host').removeAttr('disabled');
            $j('#mss_solr_port').removeAttr('disabled');
            $j('#mss_solr_path').removeAttr('disabled');
        }
        if ($j('#mysolrserver').is(':checked')) {
            $j('#solr_admin_tab_mysolrserver').css('display', 'block');
            $j('#mss_solr_host').attr('disabled','disabled');
            $j('#mss_solr_port').attr('disabled','disabled');
            $j('#mss_solr_path').attr('disabled','disabled');
        }
    }

    Array.prototype.inArray = function(p_val) {
        var l = this.length;
        for(var i = 0; i < l; i++) {
            if(this[i] == p_val) {
                return true;
            }
        }
        return false;
    }

    function drawAvailableFacets() {
		var available_facets = '<table>';

		available_facets += '<tr><th>Built-in attributes</th></tr>';
		available_facets += '<tr><td>type</td><td><a href="javascript:void(0)" onClick="addToFacet(\'type\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-right-12.png"></a></td></tr>';		    
		available_facets += '<tr><td>author</td><td><a href="javascript:void(0)" onClick="addToFacet(\'author\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-right-12.png"></a></td></tr>';		    
		available_facets += '<tr><td>category</td><td><a href="javascript:void(0)" onClick="addToFacet(\'category\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-right-12.png"></a></td></tr>';		    
		available_facets += '<tr><td>tag</td><td><a href="javascript:void(0)" onClick="addToFacet(\'tag\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-right-12.png"></a></td></tr>';		    
		available_facets += '<tr><th>Custom taxonomies</th></tr>';
		
		var temp = "";
		$j('input[name="custom_taxonomies"]:checked').each(function(index) {
			temp += '<tr><td>' + this.value + '</td><td><a href="javascript:void(0)" onClick="addToFacet(\'' + this.value + '\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-right-12.png"></a></td></tr>';		    
		});
		if (temp=="") temp='<tr><td>none selected in "Indexing Options"</td></tr>';
		available_facets += temp;
		
		available_facets += '<tr><th>Custom fields</th></tr>';
		
		temp = "";
		$j('input[name="custom_fields"]:checked').each(function(index) {
			temp += '<tr><td>' + this.value + '</td><td><a href="javascript:void(0)" onClick="addToFacet(\'' + this.value + '\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-right-12.png"></a></td></tr>';		    
		});
		if (temp=="") temp='<tr><td>none selected in "Indexing Options"</td></tr>';
		available_facets += temp;

		available_facets += '</table>';	    
		$j('#available_facets').html(available_facets);
    }
    
	function upFacet(value) {
		var currentFacets = $j("#mss_facets").val();
		var aCurrentFacets = currentFacets.split(',');
		var index = aCurrentFacets.indexOf(value);
		if (index!=-1) {
			aCurrentFacets.splice(index,1);
			aCurrentFacets.splice(index-1,0,value);
			$j("#mss_facets").val(aCurrentFacets.toString());
			drawSelectedFacets();
		}
	}

	function downFacet(value) {
		var currentFacets = $j("#mss_facets").val();
		var aCurrentFacets = currentFacets.split(',');
		var index = aCurrentFacets.indexOf(value);
		if (index!=-1) {
			aCurrentFacets.splice(index,1);
			aCurrentFacets.splice(index+1,0,value);
			$j("#mss_facets").val(aCurrentFacets.toString());
			drawSelectedFacets();
		}
	}
    
    function removeFromFacet(value) {
		var currentFacets = $j("#mss_facets").val();
		var aCurrentFacets = currentFacets.split(',');
		var index = aCurrentFacets.indexOf(value);
		if (index!=-1) {
			aCurrentFacets.splice(index,1);
			$j("#mss_facets").val(aCurrentFacets.toString());
			drawSelectedFacets();
		}
	}

	function addToFacet(value) {
		var currentFacets = $j("#mss_facets").val();
		if (currentFacets=='') {
			var aCurrentFacets = new Array();
		}
		else {		
			var aCurrentFacets = currentFacets.split(',');
		}

		if (!aCurrentFacets.inArray(value)) {
			aCurrentFacets.push(value);
			$j("#mss_facets").val(aCurrentFacets.toString());
			drawSelectedFacets();
		}
	}

	function drawSelectedFacets() {
		var currentFacets = $j("#mss_facets").val();
		if (currentFacets==undefined || currentFacets=='') {
			$j('#selected_facets').html('');
			return;
		} 
		var aCurrentFacets = currentFacets.split(',');

		var selected_facets = '<table>';
		for (var i=0; i<aCurrentFacets.length; i++) {
			selected_facets += '<tr><td>' + aCurrentFacets[i] + '</td><td>';
			selected_facets += '<a href="javascript:void(0)" onClick="removeFromFacet(\'' + aCurrentFacets[i] + '\')"><img src="<?php print $this_plugin_dir_url; ?>images/cross-red-12.png"></a>';
			if (i>0) selected_facets += '&nbsp;<a href="javascript:void(0)" onClick="upFacet(\'' + aCurrentFacets[i] + '\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-up-12.png"></a>';
			if (i<aCurrentFacets.length-1) selected_facets += '&nbsp;<a href="javascript:void(0)" onClick="downFacet(\'' + aCurrentFacets[i] + '\')"><img src="<?php print $this_plugin_dir_url; ?>images/arrow-down-12.png"></a>';
			selected_facets += '</td></tr>';
		}	
		selected_facets += '</table>';	    
		$j('#selected_facets').html(selected_facets);
	}
    
	function doIndex(prev) {
		$j('#mss_index_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/ajax-circle.gif"> 0%');
		$j.get(ajax_url, {action: 'index', prev: prev}, doIndexHandleResults, "json");
	}
	function doIndexHandleResults(data) {
		$j('#mss_index_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/ajax-circle.gif"> ' + data.percent + '%');
		if (!data.end) {
			doIndex(data.last);
		} else {
			$j('#mss_index_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png"> 100%');
			setTimeout("clearSaveStatus('#mss_index_status')",1000);
		}
	}

    $j(document).ready(function($) {

    	/*
    	 * at load time
    	 */
    	mss_switch1();
    	drawAvailableFacets();
    	drawSelectedFacets();
    	
		/*
		 * on... handlers
		 */
        $('[name=mss_btn_connect]').click(function() {
			var name = $('#mss_id').val();
			var passwd = $('#mss_passwd').val();
  
     	    $.get(ajax_url, {action: 'accountgetinfo', name: name, passwd : passwd }, 
        		function(data) {
     	    		var resp = JSON.parse(data);
					if (resp.status == 'ok') {
						$('#mss_connect_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png">');
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
						$('#mss_connect_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">');
					}
					
        		});
        	
            return false;     
        });

    	$('#mss_instances').change(function() {
    		if (this.value=='') {
        		$j('#mss_solr_host').val('');
        		$j('#mss_solr_port').val('');
        		$j('#mss_solr_path').val('');
    		}
    		else {
	    	    var a = document.createElement('a');
	    	    a.href = this.value;
	    	    var port = a.port;
	    	    if (port=='') port='80';
        		$j('#mss_solr_host').val(a.hostname);
        		$j('#mss_solr_port').val(port);
        		$j('#mss_solr_path').val(a.pathname);
    		}
    		
    	;});
    	
    	$('[name=mss_btn_save]').click(function() {        	
			var name = $('#mss_id').val();
			var passwd = $('#mss_passwd').val();
			var url = $('#mss_instances').val();
  
     	    $.get(ajax_url, {action: 'save', name: name, passwd : passwd, url : url }, 
        		function(data) {
     	    		var resp = JSON.parse(data);
					if (resp.status == 'ok') {
						$('#mss_save_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png">');
						setTimeout("clearSaveStatus('#mss_save_status')",1000);
					}
					else {
						$('#mss_save_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">');
					}
        		});
        	
            return false;     
        });       

    	$('[name=mss_btn_save_options]').click(function() {
  
       		$('#mss_save_option_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/ajax-circle.gif">');
       		var post_types = '';
    		$('input[name="post_types"]:checked').each(function(index) {
    		    if (post_types!='') post_types += ',';
    		    post_types += this.value;
    		});
    		post_types = $.trim(post_types);
    		$('#mss_post_types').val(post_types);
  		  
    		var builtin_taxonomies = '';
    		$('input[name="builtin_taxonomies"]:checked').each(function(index) {
    		    if (builtin_taxonomies!='') builtin_taxonomies += ',';
    		    builtin_taxonomies += this.value;
    		});
    		builtin_taxonomies = $.trim(builtin_taxonomies);
    		$('#mss_builtin_taxonomies').val(builtin_taxonomies);

    		var custom_taxonomies = '';
    		$('input[name="custom_taxonomies"]:checked').each(function(index) {
    		    if (custom_taxonomies!='') custom_taxonomies += ',';
    		    custom_taxonomies += this.value;
    		});
    		custom_taxonomies = $.trim(custom_taxonomies);
    		$('#mss_custom_taxonomies').val(custom_taxonomies);

    		var custom_fields = '';
    		$('input[name="custom_fields"]:checked').each(function(index) {
    		    if (custom_fields!='') custom_fields += ',';
    		    custom_fields += this.value;
    		});
    		custom_fields = $.trim(custom_fields);
    		$('#mss_custom_fields').val(custom_fields);

    		var str = $("form").serialize();
    		//alert(str.replace(/&/g, '\n'));
    		if (str=='') {
    			$('#mss_save_option_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">&nbsp;Save failed. Try again !');
            	return false;
    		}
     	    $.post(ajax_url, 'action=saveall&'+str, 
        		function(data) {
     	    		var resp = JSON.parse(data);
					if (resp.status == 'ok') {
						$('#mss_save_option_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png">');
						setTimeout("clearSaveStatus('#mss_save_option_status')",1000);
					}
					else {
						$('#mss_save_option_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">');
					}
        		});
    		
            return false;     
        });

       	$('[name=mss_btn_ping]').click(function() {

       		$('#mss_ping_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/ajax-circle.gif">');
     	    $.get(ajax_url, {action: 'ping'}, 
           		function(data) {
         			var resp = JSON.parse(data);
    				if (resp.status == 'ok') {
    					$('#mss_ping_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png">');
    					setTimeout("clearSaveStatus('#mss_ping_status')",2000);
    				}
    				else {
    					$('#mss_ping_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">');
    				}
           		});
            	
                return false;    
        });

       	$('[name=mss_btn_optimize]').click(function() {

       		$('#mss_optimize_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/ajax-circle.gif">');
     	    $.get(ajax_url, {action: 'optimize'}, 
           		function(data) {
         			var resp = JSON.parse(data);
    				if (resp.status == 'ok') {
    					$('#mss_optimize_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png">');
    					setTimeout("clearSaveStatus('#mss_optimize_status')",2000);
    				}
    				else {
    					$('#mss_optimize_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">&nbsp' + resp.message);
    				}
           		});
            	
                return false;    
        });

       	
    	$('[name=mss_btn_index]').click(function() {			
			doIndex(0);
        });

       	$('[name=mss_btn_deleteall]').click(function() {

       		$('#mss_deleteall_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/ajax-circle.gif">');
     	    $.get(ajax_url, {action: 'deleteall'}, 
           		function(data) {
         			var resp = JSON.parse(data);
    				if (resp.status == 'ok') {
    					$('#mss_deleteall_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/success.png">');
    					setTimeout("clearSaveStatus('#mss_deleteall_status')",2000);
    				}
    				else {
    					$('#mss_deleteall_status').html('&nbsp;<img src="<?php print $this_plugin_dir_url; ?>images/warning.png">&nbsp' + resp.message);
    				}
           		});
            	
                return false;    
        });

       	$('[name=custom_taxonomies]').click(function() {
       		drawAvailableFacets();
       		removeFromFacet(this.value);
       	});
       	$('[name=custom_fields]').click(function() {
       		drawAvailableFacets();
       		removeFromFacet(this.value);
       	});       	
	});
</script>


<?php
}

add_action('admin_menu', 'mss_plugin_admin_menu');
add_action('admin_head', 'mss_admin_head');

function mss_default_head() {
	global $this_plugin_dir_url;

	if (file_exists(TEMPLATEPATH . '/mss_search.css')) {
		// use theme file
		printf(__("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" media=\"screen\" />\n"), bloginfo(template_url) . '/mss_search.css');
	} else if (file_exists(dirname(__FILE__) . '/template/mss_search.css')) {
		// use plugin supplied file
		printf(__("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" media=\"screen\" />\n"), $this_plugin_dir_url . 'template/mss_search.css');
	}
}

function mss_autosuggest_head() {
	global $this_plugin_dir_url;

	if (file_exists(TEMPLATEPATH . '/mss_autocomplete.css')) {
		// use theme file
		printf(__("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" media=\"screen\" />\n"), bloginfo(template_url) . '/mss_autocomplete.css');
	} else if (file_exists(dirname(__FILE__) . '/template/mss_autocomplete.css')) {
		// use plugin supplied file
		printf(__("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" media=\"screen\" />\n"), $this_plugin_dir_url . 'template/mss_autocomplete.css');
	}
	?>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        $("#s").suggest("?method=autocomplete",{});
        $("#qrybox").suggest("?method=autocomplete",{});
    });
</script>



<?php
}

function mss_template_redirect() {
	wp_enqueue_script('suggest');

	// not a search page; don't do anything and return
	// thanks to the Better Search plugin for the idea:  http://wordpress.org/extend/plugins/better-search/
	$search = stripos($_SERVER['REQUEST_URI'], '?s=');
	$autocomplete = stripos($_SERVER['REQUEST_URI'], '?method=autocomplete');

	if ( ($search || $autocomplete) == FALSE ) {
		return;
	}

	if ($autocomplete) {
		$q = stripslashes($_GET['q']);
		$limit = (isset($_GET['limit'])) ? $_GET['limit'] : 10;
		mss_autocomplete($q, $limit);
		exit;
	}

	// If there is a template file then we use it
	if (file_exists(TEMPLATEPATH . '/mss_search.php')) {
		// use theme file
		include_once(TEMPLATEPATH . '/mss_search.php');
	} else if (file_exists(dirname(__FILE__) . '/template/mss_search.php')) {
		// use plugin supplied file
		add_action('wp_head', 'mss_default_head');
		include_once(dirname(__FILE__) . '/template/mss_search.php');
	} else {
		// no template files found, just continue on like normal
		// this should get to the normal WordPress search results
		return;
	}

	exit;
}

function mss_handle_save( $post_id ) {
	global $current_blog;
	$post_info = get_post( $post_id );
	if ($post_info->post_status=='auto-draft' || $post_info->post_status=='draft') return;
	$options = mss_get_option();
	$post_type = $options['mss_post_types'];
	$aPostTypes = explode(',', $post_type);

	if (in_array($post_info->post_type, $aPostTypes)) {
		$docs = array();
		$doc = mss_build_document( $options, $post_info );
		if ( $doc ) {
			$docs[] = $doc;
			mss_post( $options, $docs );
		}
	}
}

function mss_handle_modified( $post_id ) {
	global $current_blog;
	$post_info = get_post( $post_id );
	$options = mss_get_option();
	$post_type = $options['mss_post_types'];
	$aPostTypes = explode(',', $post_type);

	if (in_array($post_info->post_type, $aPostTypes)) {
		$docs = array();
		$doc = mss_build_document( $options, $post_info );
		if ( $doc ) {
			$docs[] = $doc;
			mss_post( $options, $docs );
		}
	}
}

function mss_handle_status_change( $post_id ) {
	global $current_blog;
	$post_info = get_post( $post_id );
	$options = mss_get_option();
	$post_type = $options['mss_post_types'];
	$aPostTypes = explode(',', $post_type);

	if (in_array($post_info->post_type, $aPostTypes)) {
		if ( ($_POST['prev_status'] == 'publish' || $_POST['original_post_status'] == 'publish') && ($post_info->post_status == 'draft' || $post_info->post_status == 'private') ) {
			$solr = new Mss_Solr();
			if ($solr->connect($options, true)) {
				$solr->deleteById( $post_info->ID );
			}
		}
	}
}

function mss_handle_delete( $post_id ) {
	global $current_blog;
	$post_info = get_post( $post_id );
	$options = mss_get_option();
	$post_type = $options['mss_post_types'];
	$aPostTypes = explode(',', $post_type);

	if (in_array($post_info->post_type, $aPostTypes)) {
		$solr = new Mss_Solr();
		if ($solr->connect($options, true)) {
			$solr->deleteById( $post_info->ID );
		}
	}
}

function mss_search_form() {
	$sort = $_GET['sort'];
	$order = $_GET['order'];

	if ($sort == 'date') {
		$sortval = __('<option value="score">Score</option><option value="date" selected="selected">Date</option><option value="modified">Last Modified</option>');
	} else if ($sort == 'modified') {
		$sortval = __('<option value="score">Score</option><option value="date">Date</option><option value="modified" selected="selected">Last Modified</option>');
	} else {
		$sortval = __('<option value="score" selected="selected">Score</option><option value="date">Date</option><option value="modified">Last Modified</option>');
	}

	if ($order == 'asc') {
		$orderval = __('<option value="desc">Descending</option><option value="asc" selected="selected">Ascending</option>');
	} else {
		$orderval = __('<option value="desc" selected="selected">Descending</option><option value="asc">Ascending</option>');
	}

	$form = __('<form name="searchbox" method="get" id="searchbox" action=""><input type="text" id="qrybox" name="s" value="%s"/><input type="submit" id="searchbtn" /><label for="sortselect" id="sortlabel">Sort By:</label><select name="sort" id="sortselect">%s</select><label for="orderselect" id="orderlabel">Order By:</label><select name="order" id="orderselect">%s</select></form>');

	printf($form, htmlspecialchars(stripslashes($_GET['s'])), $sortval, $orderval);
}

function mss_search_results() {
	$plugin_mss_settings = mss_get_option();
	$output_info = (isset($plugin_mss_settings['mss_output_info'])) ? $plugin_mss_settings['mss_output_info'] : false;
	$output_facets = (isset($plugin_mss_settings['mss_output_facets'])) ? $plugin_mss_settings['mss_output_facets'] : false;
	$results_per_page = $plugin_mss_settings['mss_num_results'];
	$categoy_as_taxonomy = (isset($plugin_mss_settings['mss_cat_as_taxo'])) ? $plugin_mss_settings['mss_cat_as_taxo'] : false;
	$dym_enabled = $plugin_mss_settings['mss_enable_dym'];

	$qry = stripslashes($_GET['s']);
	$offset = (isset($_GET['offset'])) ? $_GET['offset'] : 0;
	$count = (isset($_GET['count'])) ? $_GET['count'] : $results_per_page;
	$fq = (isset($_GET['fq'])) ? $_GET['fq'] : '';
	$sort = (isset($_GET['sort'])) ? $_GET['sort'] : '';
	$order = (isset($_GET['order'])) ? $_GET['order'] : '';
	$isdym = (isset($_GET['isdym'])) ? $_GET['isdym'] : 0;

	$out = array();

	if ( ! $qry ) {
		$qry = '';
	}

	if ( $sort && $order ) {
		$sortby = $sort . ' ' . $order;
	} else {
		$sortby = '';
		$order = '';
	}

	$fqstr = '';
	$fqitms = split('\|\|', stripslashes($fq));
	$selectedfacets = array();
	foreach ($fqitms as $fqitem) {
		if ($fqitem) {
			$splititm = split(':', $fqitem, 2);
			$selectedfacet = array();
			$label = $splititm[1];
			if (mss_endswith($label, '^^"')) $label = substr($label, 0, -3) . '"';
			$selectedfacet['name'] = sprintf(__("%s:&nbsp;%s"), ucwords(str_replace('_', ' ', preg_replace('/_str$/i', '', $splititm[0]))), str_replace("^^", "/", $label));
			$removelink = '';
			foreach($fqitms as $fqitem2) {
				if ($fqitem2 && !($fqitem2 === $fqitem)) {
					$splititm2 = split(':', $fqitem2, 2);
					$removelink = $removelink . urlencode('||') . $splititm2[0] . ':' . urlencode($splititm2[1]);
				}
			}

			if ($removelink) {
				$selectedfacet['removelink'] = htmlspecialchars(sprintf(__("?s=%s&fq=%s"), urlencode($qry), $removelink));
			} else {
				$selectedfacet['removelink'] = htmlspecialchars(sprintf(__("?s=%s"), urlencode($qry)));
			}

			$fqstr = $fqstr . urlencode('||') . $splititm[0] . ':' . urlencode($splititm[1]);

			$selectedfacets[] = $selectedfacet;
		}
	}

	if ($qry) {
		$results = mss_query( $qry, $offset, $count, $fqitms, $sortby, $plugin_mss_settings );

		if ($results) {
			$response = $results->response;
			//echo $results->getRawResponse();
			$header = $results->responseHeader;
			$teasers = get_object_vars($results->highlighting);
			if (is_object($results->spellcheck))
			$didyoumean = $results->spellcheck->suggestions->collation;
			else
			$didyoumean= false;

			$out['hits'] = sprintf(__("%d"), $response->numFound);
			$out['qtime'] = false;
			if ($output_info) {
				$out['qtime'] = sprintf(__("%.3f"), $header->QTime/1000);
			}
			$out['dym'] = false;
			if ($didyoumean && !$isdym && $dym_enabled) {
				$dymout = array();
				$dymout['term'] = htmlspecialchars($didyoumean);
				$dymout['link'] = htmlspecialchars(sprintf(__("?s=%s&isdym=1"), urlencode($didyoumean)));
				$out['dym'] = $dymout;
			}

			// calculate the number of pages
			$numpages = ceil($response->numFound / $count);
			$currentpage = ceil($offset / $count) + 1;
			$pagerout = array();

			if ($numpages == 0) {
				$numpages = 1;
			}

			foreach (range(1, $numpages) as $pagenum) {
				if ( $pagenum != $currentpage ) {
					$offsetnum = ($pagenum - 1) * $count;
					$pageritm = array();
					$pageritm['page'] = sprintf(__("%d"), $pagenum);
					//$pageritm['link'] = htmlspecialchars(sprintf(__("?s=%s&offset=%d&count=%d"), urlencode($qry), $offsetnum, $count));
					$pagerlink = sprintf(__("?s=%s&offset=%d&count=%d"), urlencode($qry), $offsetnum, $count);
					if($fqstr) $pagerlink .= '&fq=' . $fqstr;
					$pageritm['link'] = htmlspecialchars($pagerlink);
					$pagerout[] = $pageritm;
				} else {
					$pageritm = array();
					$pageritm['page'] = sprintf(__("%d"), $pagenum);
					$pageritm['link'] = "";
					$pagerout[] = $pageritm;
				}
			}

			$out['pager'] = $pagerout;

			if ($output_facets) {
				// handle facets
				$facetout = array();

				if($results->facet_counts) {
					foreach ($results->facet_counts->facet_fields as $facetfield => $facet) {
						if ( ! get_object_vars($facet) ) {
							continue;
						}

						$facetinfo = array();
						$facetitms = array();
						$facetinfo['name'] = ucwords(str_replace('_', ' ', preg_replace('/_str$/i', '', $facetfield)));

						// categories is a taxonomy
						if ($categoy_as_taxonomy && $facetfield == 'categories') {
							// generate taxonomy and counts
							$taxo = array();
							foreach ($facet as $facetval => $facetcnt) {
								$taxovals = explode('^^', rtrim($facetval, '^^'));
								$taxo = mss_gen_taxo_array($taxo, $taxovals);
							}

							$facetitms = mss_get_output_taxo($facet, $taxo, '', $fqstr, $facetfield);

						} else {
							foreach ($facet as $facetval => $facetcnt) {
								$facetitm = array();
								$facetitm['count'] = sprintf(__("%d"), $facetcnt);
								$facetitm['link'] = htmlspecialchars(sprintf(__('?s=%s&fq=%s:%s%s', 'solrmss'), urlencode($qry), $facetfield, urlencode('"' . $facetval . '"'), $fqstr));
								$facetitm['name'] = $facetval;
								$facetitms[] = $facetitm;
							}
						}

						$facetinfo['items'] = $facetitms;
						$facetout[$facetfield] = $facetinfo;
					}
				}

				$facetout['selected'] = $selectedfacets;
				$out['facets'] = $facetout;
				$out['facets']['output']=true;
			}
			else {
				$out['facets']['output']=false;
			}

			$resultout = array();

			if ($response->numFound != 0) {
				foreach ( $response->docs as $doc ) {
					$resultinfo = array();
					$docid = strval($doc->id);
					$resultinfo['permalink'] = $doc->permalink;
					$resultinfo['title'] = $doc->title;
					$resultinfo['author'] = $doc->author;
					$resultinfo['authorlink'] = htmlspecialchars($doc->author_s);
					$resultinfo['numcomments'] = $doc->numcomments;
					$resultinfo['date'] = $doc->displaydate;

					if ($doc->numcomments === 0) {
						$resultinfo['comment_link'] = $doc->permalink . "#respond";
					} else {
						$resultinfo['comment_link'] = $doc->permalink . "#comments";
					}

					$resultinfo['score'] = $doc->score;
					$resultinfo['id'] = $docid;
					$docteaser = $teasers[$docid];
					if ($docteaser->content) {
						$resultinfo['teaser'] = sprintf(__("...%s..."), implode("...", $docteaser->content));
					} else {
						$words = split(' ', $doc->content);
						$teaser = implode(' ', array_slice($words, 0, 30));
						$resultinfo['teaser'] = sprintf(__("%s..."), $teaser);
					}
					$resultout[] = $resultinfo;
				}
			}
			$out['results'] = $resultout;
		}
	} else {
		$out['hits'] = "0";
	}

	# pager and results count helpers
	$out['query'] = htmlspecialchars($qry);
	$out['offset'] = strval($offset);
	$out['count'] = strval($count);
	$out['firstresult'] = strval($offset + 1);
	$out['lastresult'] = strval(min($offset + $count, $out['hits']));
	$out['sortby'] = $sortby;
	$out['order'] = $order;
	$out['sorting'] = array(
							'scoreasc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=score&order=asc', urlencode($qry), stripslashes($fq))),
							'scoredesc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=score&order=desc', urlencode($qry), stripslashes($fq))),
							'dateasc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=date&order=asc', urlencode($qry), stripslashes($fq))),
							'datedesc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=date&order=desc', urlencode($qry), stripslashes($fq))),
							'modifiedasc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=modified&order=asc', urlencode($qry), stripslashes($fq))),
							'modifieddesc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=modified&order=desc', urlencode($qry), stripslashes($fq))),
                        	'commentsasc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=numcomments&order=asc', urlencode($qry), stripslashes($fq))),
							'commentsdesc' => htmlspecialchars(sprintf('?s=%s&fq=%s&sort=numcomments&order=desc', urlencode($qry), stripslashes($fq)))
	);

	return $out;
}

function mss_print_facet_items($items, $pre = "<ul>", $post = "</ul>", $before = "<li>", $after = "</li>",
$nestedpre = "<ul>", $nestedpost = "</ul>", $nestedbefore = "<li>", $nestedafter = "</li>") {
	if (!$items) {
		return;
	}
	printf(__("%s\n"), $pre);
	foreach ($items as $item) {
		printf(__("%s<a href=\"%s\">%s (%s)</a>%s\n"), $before, $item["link"], $item["name"], $item["count"], $after);
	}
	printf(__("%s\n"), $post);
}

function mss_get_output_taxo($facet, $taxo, $prefix, $fqstr, $field) {
	$qry = stripslashes($_GET['s']);

	if (count($taxo) == 0) {
		return;
	} else {
		$facetitms = array();
		foreach ($taxo as $taxoname => $taxoval) {
			$newprefix = $prefix . $taxoname . '^^';
			$facetvars = get_object_vars($facet);
			$facetitm = array();
			$facetitm['count'] = sprintf(__("%d"), $facetvars[$newprefix]);
			$facetitm['link'] = htmlspecialchars(sprintf(__('?s=%s&fq=%s:%s%s', 'solrmss'), $qry, $field,  urlencode('"' . $newprefix . '"'), $fqstr));
			$facetitm['name'] = $taxoname;
			$outitms = mss_get_output_taxo($facet, $taxoval, $newprefix, $fqstr, $field);
			if ($outitms) {
				$facetitm['items'] = $outitms;
			}
			$facetitms[] = $facetitm;
		}

		return $facetitms;
	}
}

function mss_gen_taxo_array($in, $vals) {
	if (count($vals) == 1) {
		if ( ! $in[$vals[0]]) {
			$in[$vals[0]] = array();
		}
		return $in;
	} else {
		$in[$vals[0]] = mss_gen_taxo_array($in[$vals[0]], array_slice($vals, 1));
		return $in;
	}
}


function mss_options_init() {

	$action = strtolower(POSTGET("action"));

	if ($action=="accountgetinfo") {
		Global $url_mysolrserver, $url_extraparam;
		$name = POSTGET("name");
		$passwd = POSTGET("passwd");

		print ($account_info_json = getMssAccountInfo($url_mysolrserver, $url_extraparam, $name, $passwd));
		exit();
	}

	if ($action=="save") {
		$options = mss_get_option();

		$options['mss_id']=POSTGET("name");
		$options['mss_passwd']=encrypt(POSTGET("passwd"));
		$options['mss_url']=POSTGET("url");

		// update mss parameters
		$u = parse_url($options['mss_url']);
		if ($u) {
			$port = ($u['port']=="") ? "80" : $u['port'];
			if ($u['host']=="") $port = "";

			$options['mss_solr_host']=$u['host'];
			$options['mss_solr_port']=$port;
			$options['mss_solr_path']=$u['path'];
		}

		$options['mss_connect_type'] = 'mysolrserver';

		mss_update_option($options);

		$arr = array();
		$arr['status']='ok';
		print(json_encode($arr));
		exit();
	}

	if ($action=="saveall") {
		$options = mss_get_option();

		$options['mss_id']=$_POST['settings']['mss_id'];
		$options['mss_passwd']=$_POST['settings']['mss_passwd'];
		$options['mss_url']=$_POST['settings']['mss_url'];

		if ($_POST['settings']['mss_connect_type']=='mysolrserver') {

			// update mss parameters
			$u = parse_url($options['mss_url']);
			if ($u) {
				$port = (!isset($u['port']) || $u['port']=="") ? "80" : $u['port'];
				if ($u['host']=="") $port = "";

				$options['mss_solr_host']=$u['host'];
				$options['mss_solr_port']=$port;
				$options['mss_solr_path']=$u['path'];
			}
		}

		// lets loop through our options already in database
		foreach ($options as $option => $old_value ) {
			if (!(($_POST['settings']['mss_connect_type']=='mysolrserver') && ($option == 'mss_solr_host' || $option == 'mss_solr_port' || $option == 'mss_solr_path'))) {
				if ($option == 'mss_index_all_sites' || $option == 'mss_solr_initialized') {
					$value = trim($old_value);
				} else {
					if (isset($_POST['settings'][$option]))
					$value = $_POST['settings'][$option];
					else
					$value = '';
				}
				if ($option == 'mss_passwd') $value=encrypt($value);
				if ( !is_array($value) ) $value = trim($value);
				$value = stripslashes_deep($value);
				$options[$option] = $value;
			}
		}

		// lets loops to the posted options $_POST['settings'] and eventualy add new created options (plugin upgrade)
		foreach ($_POST['settings'] as $option => $value ) {
			if (!isset($options[$option]))
			$options[$option] = $value;
		}

		mss_update_option($options);

		$arr = array();
		$arr['status']='ok';
		print(json_encode($arr));
		exit();
	}

	if ($action=="ping") {
		$options = mss_get_option();
		$arr = array();

		$solr = new Mss_Solr();
		if ($solr->connect($options, true)) {
			$arr['status']='ok';
		}
		else {
			$arr['status']='ko';
		}
		print(json_encode($arr));
		exit();
	}

	if ($action=="optimize") {
		$options = mss_get_option();
		$arr = array();

		$solr = new Mss_Solr();
		if ($solr->connect($options, true)) {
			if ($solr->optimize()) {
				$arr['status']='ok';
			}
			else {
				$arr['status']='ko';
			}
		}
		else {
			$arr['status']='ko';
			$arr['code']=$solr->getLastErrorCode();
			$arr['message']=$solr->getLastErrorMessage();
		}

		print(json_encode($arr));
		exit();
	}

	if ($action=="deleteall") {
		$options = mss_get_option();
		$arr = array();

		$solr = new Mss_Solr();
		if ($solr->connect($options, true)) {
			if ($solr->deleteall()) {
				$arr['status']='ok';
			}
			else {
				$arr['status']='ko';
			}
		}
		else {
			$arr['status']='ko';
			$arr['code']=$solr->getLastErrorCode();
			$arr['message']=$solr->getLastErrorMessage();
		}

		print(json_encode($arr));
		exit();
	}
	if ($action=="index") {
		$options = mss_get_option();

		$prev = POSTGET('prev');
		mss_load_all($options, $prev);
		exit();
	}
}

add_action( 'template_redirect', 'mss_template_redirect', 1 );
add_action( 'publish_post', 'mss_handle_modified' );
add_action( 'publish_page', 'mss_handle_modified' );
add_action( 'save_post', 'mss_handle_save' );
add_action( 'edit_post', 'mss_handle_status_change' );
add_action( 'delete_post', 'mss_handle_delete' );
add_action( 'admin_init', 'mss_options_init');

add_action( 'wp_head', 'mss_autosuggest_head');
?>