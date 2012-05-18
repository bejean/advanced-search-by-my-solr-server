=== Advanced Search by My Solr Server ===
Contributors: www.mysolrserver.com
Author URI: http://www.mysolrserver.com
Plugin URI: http://wordpress.org/extend/plugins/advanced-search-by-my-solr-server/
Tags: solr, search, search results, search integration, custom search, better search, search replacement, category search, comment search, tag search, page search, post search, search highlight, seo
Requires at least: 3.0.0
Tested up to: 3.3.1
Stable tag: 2.0.2


A WordPress plugin that replaces the default WordPress search with a lot of benefits


== Description ==

In order to make Advanced Search by My Solr Server plugin work, you need a Solr server installed and configured with the provided schema.xml file. 
If you don't have the time or resources to install, configure and maintain a Solr server, My Solr Server do it for you ! 


= What is My Solr Server ? =

My Solr Server is a Software as a Service Apache Solr enterprise search platform (http://www.mysolrserver.com). My Solr Server provides hosted instances of the Apache Solr server. 
In a couple of minutes, you can create your own Solr server instance, manage it in our manager and integrate it with your favourite CMS by using its standard Solr plugin or module. Wordpress is one of the supported CMS.


= What Advanced Search by My Solr Server plugin does ? =

Advanced Search by My Solr Server plugin replaces the default WordPress search. Features and benefits include:

*   Index pages, posts and custom post types
*   Enable search and faceting on fields such as tags, categories, author, page type, custom fields and custom taxonomies
*   Add special template tags so you can create your own custom result pages to match your theme.
*   Search term suggestions (AutoComplete)
*   Provides better search results based on relevancy
*   Create custom summarys with the search terms highlighted
*   Completely integrated into default WordPress theme and search widget.
*   Configuration options allow you to select pages to ignore


== Installation ==

= Prerequisite = 

A Solr server installed and configured with the provided schema.xml file.
In order to have spell checking work, in the solrconfig.xml file, check :

1. the spellchecker component have to be correctly configured :

    &lt;lst name="spellchecker"&gt;
      &lt;str name="name">default&lt;/str&gt;
      &lt;str name="field">spell&lt;/str&gt;
      &lt;str name="spellcheckIndexDir"&gt;spellchecker&lt;/str&gt;
      &lt;str name="buildOnOptimize"&gt;true&lt;/str&gt;
    &lt;/lst&gt;
   
2. the request handler includes the spellchecker component

     &lt;arr name="last-components"&gt;
       &lt;str&gt;spellcheck&lt;/str&gt;
     &lt;/arr&gt;  
    
If you are using "Solr for Wordpress" plugin, deactivate and uninstall it (in previous version, "Solr for Wordpress" plugin was a pre-requisite).


= Installation =

1. Upload the `advanced-search-by-my-solr-server` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go in Advanced Search by My Solr Server settings page ("Advanced Search by My Solr Server"), configure the plugin and Load your blog content in Solr ("Load Content" button)


== Frequently Asked Questions ==

= What version of WordPress does Advanced Search by My Solr Server plugin work with? =

Advanced Search by My Solr Server plugin works with WordPress 3.0.0 and greater.

= What version of Solr does Advanced Search by My Solr Server plugin work with? =

Advanced Search by My Solr Server plugin works with Solr 1.4.x and 3.x

= How to manage Custom Post type, custom taxonomies and custom fields? =

Advanced Search by My Solr Server plugin is tested with "Custom Post Type UI" plugin for Custom Post type and custom taxonomies management and with "Custom Field Template" plugin for custom fields management


== Screenshots ==

1. Configuration page


2. Configuration page (facets)


== Changelog ==

= 2.0.2 =

* Bug fix while checking Solr connection

= 2.0.1 =

* Update installation prerequisites in order to have spell checking work.

= 2.0.0 =

* Includes all indexing and searching features
* "Solr for Wordpress" plugin is not a pre-requisite anymore
* Add support for custom post types and custom taxonomies
* Settings page refactoring
* Bug fixing

= 1.0.2 =

* Bug fixing

= 1.0.1 =

* Bug fixing

= 1.0.0 =

* Initial version just for My Solr Server connection management.
* "Solr for Wordpress" plugin is a pre-requisite

