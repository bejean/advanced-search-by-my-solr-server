<?php
/*
 Copyright (c) 2011-2013 www.mysolrserver.com

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
require_once("SolrPhpClient/Apache/Solr/Service.php");
require_once("solr.class.inc.php");

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
$this_plugin_dir_url = plugin_dir_url("") . 'advanced-search-by-my-solr-server/';


function mss_startswith($str, $sub) {
	return ( substr( $str, 0, strlen( $sub )) === $sub );
}
function mss_endswith($str, $sub) {
	return ( substr( $str, strlen( $str ) - strlen( $sub )) === $sub );
}

function encrypt($value) {
	$crypt = new encryption_class;
	return $crypt->encrypt("mysolrserver", $value, strlen($value));
}

function decrypt($value) {
	$crypt = new encryption_class;
	return $crypt->decrypt("mysolrserver", $value);

}

function mss_get_option() {
	return get_option('plugin_mss_settings');
}

function mss_update_option($optval) {
	update_option('plugin_mss_settings', $optval);
}

function POSTGET($param){
	if (isset($_POST[$param]) && $_POST[$param]!="")
	return $_POST[$param];
	if (isset($_GET[$param]) && $_GET[$param]!="")
	return $_GET[$param];
	return "";
}

function getMssAccountInfo($url_mysolrserver, $url_extraparam, $mss_id, $mss_passwd, $proxy, $proxyport, $proxyusername, $proxypassword) {
	$url = $url_mysolrserver . '?action=accountgetinfo&name=' . $mss_id . '&passwd=' . $mss_passwd . '&type=wp' . $url_extraparam;
	log_message("getMssAccountInfo - url = " . $url_mysolrserver);

	if ($proxy!='' && $proxyport!='') {

		if ($proxyusername!='' && $proxypassword!='') {

			// Encodage de l'autentification
			$authProxy = base64_encode("$proxyusername:$proxypassword");
			// Création des options de la requête
			$opts = array(
			'http' => array (
			'method'=>'GET',
			'proxy'=>"tcp://$proxy:$proxyport",
			'request_fulluri' => true,
			'header'=>"Proxy-Authorization: Basic $authProxy"
			)
			);
		} else {
				
			// Création des options de la requête
			$opts = array(
			'http' => array (
			'proxy'=>"tcp://$proxy:$proxyport",
			'method'=>'GET',
			'request_fulluri' => true
			)
			);
		}
		// Création du contexte de transaction
		$ctx = stream_context_create($opts);
		$json = file_get_contents($url,false,$ctx);

	} else {
		$json = file_get_contents($url);
	}
	log_message("getMssAccountInfo - json = " . $json);
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

/*
 * Indexing functions
*/

function mss_load_all($options, $prev) {
	global $wpdb;
	$documents = array();
	$cnt = 0;
	$batchsize = 100;
	$last = "";
	$found = FALSE;
	$end = FALSE;
	$percent = 0;

	$post_type = $options['mss_post_types'];
	if ($post_type=='') {
		printf("{\"last\": \"0\", \"end\": true, \"percent\": \"100\"}");
		return;
	}
	$aPostType=explode(',', $post_type);
	$wherePostType = '';
	for ($i=0;$i<count($aPostType);$i++) {
		if ($wherePostType!='') $wherePostType .= ' OR ';
		$wherePostType .= " post_type = '" .  $aPostType[$i] . "'";
	}

	$posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND ($wherePostType) ORDER BY ID;" );
	$postcount = count($posts);
	for ($idx = 0; $idx < $postcount; $idx++) {
		$postid = $posts[$idx]->ID;
		$last = $postid;
		$percent = (floatval($idx) / floatval($postcount)) * 100;
		if ($prev && !$found) {
			if ($postid === $prev) {
				$found = TRUE;
			}
			continue;
		}

		if ($idx === $postcount - 1) {
			$end = TRUE;
		}

		$documents[] = mss_build_document($options, get_post($postid) );
		$cnt++;
		if ($cnt == $batchsize) {
			mss_post( $options, $documents, FALSE, FALSE);
			$cnt = 0;
			$documents = array();
			break;
		}
	}

	if ( $documents ) {
		mss_post( $options, $documents , FALSE, FALSE);
	}

	if ($end) {
		mss_post($options, FALSE, TRUE, FALSE);
		printf("{\"last\": \"%s\", \"end\": true, \"percent\": \"%.2f\"}", $last, $percent);
	} else {
		printf("{\"last\": \"%s\", \"end\": false, \"percent\": \"%.2f\"}", $last, $percent);
	}
}

function mss_build_document( $options, $post_info ) {
	global $current_site, $current_blog;
	$doc = NULL;
	$exclude_ids = $options['mss_exclude_pages'];
	$categoy_as_taxonomy = $options['mss_cat_as_taxo'];
	$index_comments = $options['mss_index_comments'];

	if ($post_info) {
		// check if we need to exclude this document
		if (in_array($post_info->ID, (array)$exclude_ids) ) {
			return NULL;
		}
		$doc = new Apache_Solr_Document();
		$auth_info = get_userdata( $post_info->post_author );

		$doc->setField( 'id', $post_info->ID );
		$doc->setField( 'permalink', get_permalink( $post_info->ID ) );
		$doc->setField( 'wp', 'wp');

		$numcomments = 0;
		if ($index_comments) {
			$comments = get_comments("status=approve&post_id={$post_info->ID}");
			foreach ($comments as $comment) {
				$doc->addField( 'comments', $comment->comment_content );
				$numcomments += 1;
			}
		}

		$doc->setField( 'title', $post_info->post_title );
		$doc->setField( 'content', strip_tags($post_info->post_content) );
		$doc->setField( 'numcomments', $numcomments );
		$doc->setField( 'author', $auth_info->display_name );
		$doc->setField( 'author_s', get_author_posts_url($auth_info->ID, $auth_info->user_nicename));
		$doc->setField( 'type', $post_info->post_type );
		$doc->setField( 'date', mss_format_date($post_info->post_date_gmt) );
		$doc->setField( 'modified', mss_format_date($post_info->post_modified_gmt) );
		$doc->setField( 'displaydate', $post_info->post_date );
		$doc->setField( 'displaymodified', $post_info->post_modified );

		$categories = get_the_category($post_info->ID);
		if ( ! $categories == NULL ) {
			foreach( $categories as $category ) {
				if ($categoy_as_taxonomy) {
					$doc->addField('categories', get_category_parents($category->cat_ID, FALSE, '^^'));
				} else {
					$doc->addField('categories', $category->cat_name);
				}
			}
		}

		$tags = get_the_tags($post_info->ID);
		if ( ! $tags == NULL ) {
			foreach( $tags as $tag ) {
				$doc->addField('tags', $tag->name);
			}
		}
			
		// custom taxonomies
		$taxo = $options['mss_custom_taxonomies'];
		$aTaxo = explode(',', $taxo);
		$taxonomies = (array)get_taxonomies(array('_builtin'=>FALSE),'names');
		foreach($taxonomies as $parent) {
			if (in_array($parent, $aTaxo)) {
				$terms = get_the_terms( $post_info->ID, $parent );
				if ((array) $terms === $terms) {
					$parent =  strtolower(str_replace(' ', '_', $parent));
					foreach ($terms as $term) {
						$doc->addField($parent . '_str', $term->name);
						$doc->addField($parent . '_srch', $term->name);
					}
				}
			}
		}

		// custom fields
		$custom = $options['mss_custom_fields'];
		$aCustom = explode(',', $custom);
		if (count($aCustom)>0) {
			if (count($custom_fields = get_post_custom($post_info->ID))) {
				foreach ((array)$aCustom as $field_name ) {
					$field = (array)$custom_fields[$field_name];
					$field_name =  strtolower(str_replace(' ', '_', $field_name));
					foreach ( $field as $key => $value ) {
						$doc->addField($field_name . '_str', $value);
						$doc->addField($field_name . '_srch', $value);
					}
				}
			}
		}

	} else {
		_e('Post Information is NULL', 'solrmss');
	}

	return $doc;
}

function mss_format_date( $thedate ) {
	$datere = '/(\d{4}-\d{2}-\d{2})\s(\d{2}:\d{2}:\d{2})/';
	$replstr = '${1}T${2}Z';
	return preg_replace($datere, $replstr, $thedate);
}

function mss_post( $options, $documents, $commit = true, $optimize = false) {
	try {
		$solr = new Mss_Solr();
		if ($solr->connect($options, true)) {

			if ($documents) {
				$solr->addDocuments( $documents );
			}

			if ($commit) {
				$solr->commit();
			}

			if ($optimize) {
				$solr->optimize();
			}
		}
	} catch ( Exception $e ) {
		echo $e->getMessage();
	}
}

/*
 * Search functions
*/
function mss_query( $qry, $offset, $count, $fq, $sortby, $options) {
	$response = NULL;
	$facet_fields = array();
	$options = mss_get_option(); // uncommented in 2.0.3

	$solr = new Mss_Solr();
	if ($solr->connect($options, true)) {

		$facets = $options['mss_facets'];
		$aFacets = explode(',', $facets);

		foreach($aFacets as $facet_field) {
			$facet_field_add = $facet_field . "_str";
			if ($facet_field=='category') $facet_field_add = 'categories';
			if ($facet_field=='tag') $facet_field_add = 'tags';
			if ($facet_field=='author') $facet_field_add = 'author';
			if ($facet_field=='type') $facet_field_add = 'type';
			$facet_field_add =  strtolower(str_replace(' ', '_', $facet_field_add));
			$facet_fields[] = $facet_field_add;
		}

		$params = array();
		$params['defType'] = 'dismax';
		$params['qf'] = 'tagssrch^5 title^10 categoriessrch^5 content^3.5 comments^1.5'; // TODO : Add "_srch" custom fields ?
		/*
		2.0.3 change:
		added this section to _srch versions for each custom field and each custom taxonomy that's checked in the plugin options area
		*/
		//$facet_search = $options['mss_facets_search'];
		//if ($facet_search) {
			$cust_array = array();
			$aCustom = explode(',', $options["mss_custom_fields"]);
			if (count($aCustom)>0) {
				foreach($aCustom as $aCustom_item){
					$cust_array[] = $aCustom_item . '_srch';
				}
			}
			$aCustom = explode(',', $options["mss_custom_taxonomies"]);
			if (count($aCustom)>0) {
				foreach($aCustom as $aCustom_item){
					$cust_array[] = $aCustom_item . '_srch';
				}
			}
			if (count($cust_array)>0) {
				foreach($cust_array as $custom_item){
					$params['qf'] .= " $custom_item^3";
				}
			}
		//}
					
		if (empty($qry) || $qry=='*' || $qry=='*:*') {
			$params['q.alt']="*:*";
			$qry = '';
		}
				
		/* end 2.0.3 change added section */
		//var_dump($params['qf']);
		$params['pf'] = 'title^15 text^10';
		$params['facet'] = 'true';
		$params['facet.field'] = $facet_fields;
		$params['facet.mincount'] = '1';
		$params['fq'] = $fq;
		$params['fl'] = '*,score';
		$params['hl'] = 'on';
		$params['hl.fl'] = 'content';
		$params['hl.snippets'] = '3';
		$params['hl.fragsize'] = '50';
		$params['sort'] = $sortby;
		$params['spellcheck.onlyMorePopular'] = 'true';
		$params['spellcheck.extendedResults'] = 'false';
		$params['spellcheck.collate'] = 'true';
		$params['spellcheck.count'] = '1';
		$params['spellcheck'] = 'true';
		//$params['debug'] = 'true';
		
		//if ($facet_on_tags) {
		//	$number_of_tags = $options['mss_max_display_tags'];
		//	$params['f.tags.facet.limit'] = $number_of_tags;
		//}

		$response = $solr->search($qry, $offset, $count, $params);
		//print($response->getRawResponse());
		if ( ! $response->getHttpStatus() == 200 ) {
			$response = NULL;
		}
	}
	return $response;
}

function mss_autocomplete($q, $limit) {
	$options = mss_get_option();

	$solr = new Mss_Solr();
	if ($solr->connect($options, true)) {
		$params = array();
		$params['terms'] = 'true';
		$params['terms.fl'] = 'spell';
		$params['terms.lower'] = $q;
		$params['terms.prefix'] = $q;
		$params['terms.lower.incl'] = 'false';
		$params['terms.limit'] = $limit;
		$params['qt'] = '/terms';

		$response = $solr->search($q, 0, $limit, $params);
		if ( ! $response->getHttpStatus() == 200 ) {
			return;
		}

		$terms = get_object_vars($response->terms->spell);
		foreach($terms as $term => $count) {
			printf("%s\n", $term);
		}
	}
}


?>