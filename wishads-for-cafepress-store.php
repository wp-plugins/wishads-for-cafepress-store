<?
/* 
Plugin Name: Wishads for CafePress Store
Plugin URI: http://www.wishads.com/wordpress-plugins/cafepress_store/
Description: A plugin that creates a display grid of products available from your CafePress.com store and creates direct or affiliate links to your products. 
Author: Wishads.com
Version: 1.0
Author URI: http://www.wishads.com/
*/ 

/*	Copyright 2009  Wishads.com  (email : info@wishads.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
$cpstore_my_error_handler = set_error_handler("cpstore_myErrorHandler");


add_shortcode('cpstore', 'wpCPStore');  
add_action('admin_menu', 'wishads_cpstore_plugin_menu');
add_action('admin_menu', 'cpStoreHandleAdminMenu');
add_action('wp_head', 'cpstore_add_css');
add_filter('admin_print_scripts', 'cpStoreAdminHead');

// functions do the work and don't get the credit


// ********
function wpCPStore ($attr, $content) {
global $thisprod;
/*
Categories Order
You can reorder these to rearrange the order of your categories from top to bottom
*/
	$cpstore_category[] = "Shirts (short)";
	$cpstore_category[] = "Shirts (long)";
	$cpstore_category[] = "Kids Clothing";
	$cpstore_category[] = "Outerwear";
	$cpstore_category[] = "Intimate Apparel";
	$cpstore_category[] = "Home & Office";
	$cpstore_category[] = "Fun Stuff";
	$cpstore_category[] = "Cards, Prints & Calendars";
	$cpstore_category[] = "Hats & Caps";
	$cpstore_category[] = "Bags";
	$cpstore_category[] = "Stickers";
	$cpstore_category[] = "Mugs";
	$cpstore_category[] = "Pets";
	$cpstore_category[] = "Buttons & Magnets";
	$cpstore_category[] = "Books & CDs";




// mess with this at your peril

    $attr = shortcode_atts(array('return'   => get_option('wishads_cpstore_numtoshow'),
                                 'preview'   => get_option('wishads_cpstore_numtopreview')), $attr);

	// get css from settings
	$cpstore_css_container = get_option('wishads_cpstore_css_container');
	$cpstore_css_category = get_option('wishads_cpstore_css_category');
	$cpstore_css_float = get_option('wishads_cpstore_css_float');
	$cpstore_css_float_img = get_option('wishads_cpstore_css_float_img');
	$cpstore_css_float_p = get_option('wishads_cpstore_css_float_p');
	$cpstore_css_price_a = get_option('wishads_cpstore_css_price_a');
	$cpstore_css_float_hover = get_option('wishads_cpstore_css_float_hover');
	$cpstore_css_float_hover_img = get_option('wishads_cpstore_css_float_hover_img');
	$cpstore_css_float_hover_p = get_option('wishads_cpstore_css_float_hover_p');
	$cpstore_css_price_hover_a = get_option('wishads_cpstore_css_price_hover_a');
	$cpstore_css_viewall = get_option('wishads_cpstore_css_viewall');
	$cpstore_css_catmenu = get_option('wishads_cpstore_css_catmenu');
	

	// check on the cp api and the cj pid
	$cpstore_cjpid = get_option('wishads_cpstore_cjpid');
	$cpstore_cjsid = get_option('wishads_cpstore_cjsid');
	$cpstore_cjxid = get_option('wishads_cpstore_cjxid');

		$cpApiKey = trim(get_option('wishads_cpstore_cpapikey'));
	if ($cpApiKey == '') {
		return '<div>wishad alert: missing api key. Please check your plugin settings and enter an api key.</div>';
	} else {
	
		$cpstore_preview = $attr['preview'];
		$cpstore_return = $attr['return'];
		
		$cpstore_permalink = get_permalink($post->ID);
		$cpstore_preview = $attr['preview'];
		$cpstore_return = $attr['return'];
		$cpstore_startPage = (isset($_GET['startpage']) && $_GET['startpage']) ? $_GET['startpage'] : "1";
		$cpstore_url = $content;

		$cpstoreArray1 = explode("cafepress.com/",$cpstore_url);
		list($cpstore_storeid,$cpstore_sectionid) = explode("/",$cpstoreArray1[1]);

		// build the file name
		$cpstore_dir = dirname(__FILE__) ;
		$cpstore_cache_dir = $cpstore_dir . "/cache" ;

		cpstore_cleancache($cpstore_cache_dir);

		$cpstore_FileName = $cpstore_cache_dir . "/" . $cpstore_storeid . "_" . $cpstore_sectionid . ".xml";	


$depth = array();
if (!file_exists ($cpstore_FileName)) { // there's no cached file, read from the web and create a local version
	$cpApiRequest = "http://open-api.cafepress.com/product.listByStoreSection.cp?appKey=$cpApiKey&storeId=$cpstore_storeid&sectionId=$cpstore_sectionid&v=3";	
	if (!($fp = fopen($cpApiRequest, 'r'))) {
		// fopen doesn't work for this setup, try curl
		$result = cpstore_get_web_page( $cpApiRequest );		
		$file_content = $result['content'];

	   // do something with the content here
		$fh = fopen($cpstore_FileName, 'w') or die("can't create local file");
		fwrite($fh, $file_content);
		fclose($fh);
	}
	else 
	{
	   // keep reading until there's nothing left
		//echo "<div>opened CP file for reading...</div>";
	   while ($line = fgets($fp, 4096)) {
		  $file_content .= $line;
		}
	   // do something with the content here
		$fh = fopen($cpstore_FileName, 'w') or die("can't create new cache file");
		fwrite($fh, $file_content);
		fclose($fh);
	}
}

$cpstore_xml_parser = xml_parser_create();
xml_set_element_handler($cpstore_xml_parser, "startElement", "endElement");



if (!($fp = fopen($cpstore_FileName, "r"))) {
    die("cannot open local cache file");
}

while ($data = fread($fp, 4096)) {
    if (!xml_parse($cpstore_xml_parser, $data, feof($fp))) {
        die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($cpstore_xml_parser)),
                    xml_get_current_line_number($cpstore_xml_parser)));
    }
}
xml_parser_free($cpstore_xml_parser);


			   $cpstore_content = '<style>
<!--
div.cpstore_css_category {
  ' . $cpstore_css_category . '
  }
div.cpstore_css_container {
  ' . $cpstore_css_container . '
  }
div.cpstore_css_spacer {
  clear: both;
  }
div.cpstore_css_float {
  ' . $cpstore_css_float . '
  }
div.cpstore_css_float img{
  ' . $cpstore_css_float_img . '
  }
div.cpstore_css_float p {
  ' . $cpstore_css_float_p . '
   }
div.cpstore_css_float_hover {
  ' . $cpstore_css_float_hover . '
  }
div.cpstore_css_float_hover img{
  ' . $cpstore_css_float_hover_img . '
  }
  
div.cpstore_css_float_hover p {
  ' . $cpstore_css_float_hover_p . '
   }
div.cpstore_css_price a {
  ' . $cpstore_css_price_a . '
   }

div.cpstore_css_price a:hover {
  ' . $cpstore_css_price_hover_a . '
   }
div.cpstore_css_viewall {
  ' . $cpstore_css_viewall . '
   }
div.cpstore_css_viewall a {
  ' . $cpstore_css_viewall . '
   }
div.cpstore_css_catmenu {
  ' . $cpstore_css_catmenu . '
   }
   
-->
</style>';


$cpstore_content .= '<div class="cpstore_css_container">';

// create the category menu if this is a single post or page
if (is_single() || is_page())  {
	foreach ($cpstore_category as $key => $cpstore_catname) {
	$cpstore_productlist = $thisprod["$cpstore_catname"];
		if (!empty($cpstore_productlist)){
			$cpstore_catlist .= "<span style=\"white-space:nowrap;\"><a href=\"#$key\">$cpstore_catname</a></span> | ";
		}
	}
	$cpstore_catlist = substr($cpstore_catlist,0,strlen($cpstore_catlist)-3);
	$cpstore_catlist = "<div class=\"cpstore_css_catmenu\"><a name=\"cpstore_menu\"></a>" . $cpstore_catlist . "</div>";
	
	$cpstore_content .= $cpstore_catlist;
	
}

$cpstore_content .= '<div class="cpstore_css_spacer"></div>';

// now run through each category and show the thumbs
foreach ($cpstore_category as $key => $cpstore_catname) {
$cpstore_productlist = $thisprod["$cpstore_catname"];
	if (!empty($cpstore_productlist)){
		$cpstore_content .= '<div class="cpstore_css_spacer"></div>';
		$cpstore_content .= "<div class=\"cpstore_css_category\"><a name=\"$key\"></a>$cpstore_catname</div>";
		foreach ($cpstore_productlist as $cpstore_id => $cpstore_attr) {

		$cpstore_cjpid = get_option('wishads_cpstore_cjpid');
		$cpstore_cjsid = get_option('wishads_cpstore_cjsid');
		$cpstore_cjxid = get_option('wishads_cpstore_cjxid');

		if ($cpstore_cjpid == '') {
			$cpstore_prefix = '';
			if ($cpstore_cjxid == '') {
				$cpstore_suffix = '';
			} else {
				$cpstore_suffix = "?pid=$cpstore_cjxid";
			}
		} else {
			$cpstore_prefix = "http://www.anrdoezrs.net/click-".$cpstore_cjpid."-10463747?XID=".$cpstore_cjxid."&SID=". $cpstore_cjsid ."&URL=";
		}

		$this_link = $cpstore_prefix . $cpstore_attr["link"] . $cpstore_suffix;


			$cpstore_content .= '
			<div class="cpstore_css_float" onmouseover="this.className=\'cpstore_css_float_hover\'" onmouseout="this.className=\'cpstore_css_float\'">
			<a href="' . $this_link . '"><img title="' . $cpstore_attr["description"] . '" src="' . $cpstore_attr["image"] . '" alt="' . $cpstore_attr["description"] . '" width="150" height="150" /></a>
			<div><a class="thickbox" href="' . str_replace("150x150","350x350",$cpstore_attr["image"]) . '">
			+zoom</a></div><p>' . $cpstore_attr["name"] . '</p><div class="cpstore_css_price"><a href="' . $this_link . '">Buy Now! - $' . $cpstore_attr["price"] . '</a></div></div>
				';
			$cpstore_loopcounter++;
			if (!is_single() && ($cpstore_loopcounter == $cpstore_preview)) { // exit both loops
				$cpstore_content .= '<div class="cpstore_css_spacer"></div>';
				$cpstore_content .= "<div class=\"cpstore_css_viewall\"><a href=\"" . get_permalink($post->ID) . "\">View all</a></div>";
				break 2;

			}
			if (is_single() && ($cpstore_loopcounter == $cpstore_return)) { // exit both loops
				break 2;
			}
			

		}
		// end of individual category loop
		// if this is a single post or page, show the "back to top" link
		if (is_single() || is_page())  {
				$cpstore_content .= '<div class="cpstore_css_spacer"></div>';
				$cpstore_content .= "<div class=\"cpstore_toplink\"><a href=\"#cpstore_menu\">back to menu</a></div>";
		}
	}
}


		$cpstore_content .= '<div class="cpstore_css_spacer"></div>';
		$cpstore_content .= '<div style="margin-bottom:2em;"></div></div>';
		return $cpstore_content;
	}
	
}
// ********
function cpstore_add_css() {

	wp_enqueue_script('jquery');
	wp_enqueue_script('thickbox');
	echo "<script type=\"text/javascript\" src=\"/wp-includes/js/jquery/jquery.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"/wp-includes/js/thickbox/thickbox.js\"></script>";
	echo "<link rel=\"stylesheet\" href=\"/wp-includes/js/thickbox/thickbox.css\" type=\"text/css\" media=\"screen\" />\n";
	
}
// ********
function cpStoreHandleAdminMenu() {
    add_meta_box('cpStoreMB', 'Wishads for CafePress Store Entry', 'cpStoreInsertForm', 'post', 'normal');
    add_meta_box('cpStoreMB', 'Wishads for CafePress Store Entry', 'cpStoreInsertForm', 'page', 'normal');
}
// ********
function cpstorewarning() {
	echo "<div id='wpCPStore_warning' class='updated fade-ff0000'><p><strong>"
		.__('Wishads for CafePress Store is almost ready.')."</strong> "
		.sprintf(__('You must <a href="options-general.php?page=wishads-for-cafepress-store/cafepress_grid.php">enter your CafePress API key and your Commission Junction PID</a> for it to work.'), "options-general.php?page=wishads-for-cafepress-store/cafepress_grid.php")
		."</p></div>";
}
// ********
function cpStoreInsertForm() {
?>
        <table class="form-table">
            <tr valign="top">
                <th align="right" scope="row"><label for="wpCPStore_url"><?php _e('Section Url:')?></label></th>
                <td>
                    <input type="text" size="40" style="width:95%;" name="wpCPStore_url" id="wpCPStore_url" />
                </td>
            </tr>
            <tr valign="top">
                <th align="right" scope="row"><label for="wpCPStore_preview"><?php _e('Preview how many?:')?></label></th>
                <td>
                    <input type="text" size="40" style="width:95%;" name="wpCPStore_preview" id="wpCPStore_preview" />
                </td>
            </tr>
            <tr valign="top">
                <th align="right" scope="row"><label for="wpCPStore_return"><?php _e('Show how many?:')?></label></th>
                <td>
                    <input type="text" size="40" style="width:95%;" name="wpCPStore_return" id="wpCPStore_return" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="button" onclick="return this_wpCPStoreAdmin.sendToEditor(this.form);" value="<?php _e('Create Wishad Shortcode &raquo;'); ?>" />
        </p>
<?php
}
// ********
function cpStoreAdminHead () {
    if ($GLOBALS['editing']) {
        wp_enqueue_script('wpCPStoreAdmin', WP_PLUGIN_URL .'/wishads-for-cafepress-store/js/cpstore.js', array('jquery'), '1.0.0');
    }
}
// ********

// admin menus
function wishads_cpstore_plugin_menu() {
  	add_options_page('Wishads for CafePress Store Settings', 'Wishads for CafePress Store', 8, __FILE__, 'wishads_cpstore_plugin_options');
}
// ********
function wishads_cpstore_plugin_options() {
	echo "<h2>Wishads for CafePress Store</h2>";

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( $_POST['get_cpstore_submit'] == 'Y' ) {

		// let the browser know it's been updated
		echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';

        // Read their posted value
        $cpstore_cpapikey = $_POST['cpstore_cpapikey'];
        $cpstore_cjpid = $_POST['cpstore_cjpid'];
        $cpstore_cjsid = $_POST['cpstore_cjsid'];
        $cpstore_cjxid = $_POST['cpstore_cjxid'];
        $cpstore_numtopreview = $_POST['cpstore_numtopreview'];
        $cpstore_numtoshow = $_POST['cpstore_numtoshow'];
			// css
        $cpstore_css_viewall = $_POST['cpstore_css_viewall'];
        $cpstore_css_category = $_POST['cpstore_css_category'];
        $cpstore_css_container = $_POST['cpstore_css_container'];
        $cpstore_css_float = $_POST['cpstore_css_float'];
        $cpstore_css_float_img = $_POST['cpstore_css_float_img'];
        $cpstore_css_float_p = $_POST['cpstore_css_float_p'];
        $cpstore_css_price_a = $_POST['cpstore_css_price_a'];
        $cpstore_css_float_hover = $_POST['cpstore_css_float_hover'];
        $cpstore_css_float_hover_img = $_POST['cpstore_css_float_hover_img'];
        $cpstore_css_float_hover_p = $_POST['cpstore_css_float_hover_p'];
        $cpstore_css_price_hover_a = $_POST['cpstore_css_price_hover_a'];
        $cpstore_css_catmenu = $_POST['cpstore_css_catmenu'];

        // Save the posted value in the database
        update_option( 'wishads_cpstore_cpapikey', $cpstore_cpapikey );
        update_option( 'wishads_cpstore_cjpid', $cpstore_cjpid );
        update_option( 'wishads_cpstore_cjsid', $cpstore_cjsid );
        update_option( 'wishads_cpstore_cjxid', $cpstore_cjxid );
        update_option( 'wishads_cpstore_numtoshow', $cpstore_numtoshow );
        update_option( 'wishads_cpstore_numtopreview', $cpstore_numtopreview );

        update_option( 'wishads_cpstore_css_viewall', $cpstore_css_viewall );
        update_option( 'wishads_cpstore_css_category', $cpstore_css_category );
        update_option( 'wishads_cpstore_css_container', $cpstore_css_container);
        update_option( 'wishads_cpstore_css_float', $cpstore_css_float);
        update_option( 'wishads_cpstore_css_float_img', $cpstore_css_float_img);
        update_option( 'wishads_cpstore_css_float_p', $cpstore_css_float_p);
        update_option( 'wishads_cpstore_css_price_a', $cpstore_css_price_a);
        update_option( 'wishads_cpstore_css_float_hover', $cpstore_css_float_hover);
        update_option( 'wishads_cpstore_css_float_hover_img', $cpstore_css_float_hover_img);
        update_option( 'wishads_cpstore_css_float_hover_p', $cpstore_css_float_hover_p);
        update_option( 'wishads_cpstore_css_price_hover_a', $cpstore_css_price_hover_a);
        update_option( 'wishads_cpstore_css_catmenu', $cpstore_css_catmenu);

	}

	$cpstore_cpapikey = get_option('wishads_cpstore_cpapikey');
	$cpstore_cjpid = get_option('wishads_cpstore_cjpid');
	$cpstore_cjsid = get_option('wishads_cpstore_cjsid');
	$cpstore_cjxid = get_option('wishads_cpstore_cjxid');
	$cpstore_numtoshow = get_option('wishads_cpstore_numtoshow');
	$cpstore_numtopreview = get_option('wishads_cpstore_numtopreview');

	$cpstore_css_viewall = get_option('wishads_cpstore_css_viewall');
	$cpstore_css_category = get_option('wishads_cpstore_css_category');
	$cpstore_css_container = get_option('wishads_cpstore_css_container');
	$cpstore_css_float = get_option('wishads_cpstore_css_float');
	$cpstore_css_float_img = get_option('wishads_cpstore_css_float_img');
	$cpstore_css_float_p = get_option('wishads_cpstore_css_float_p');
	$cpstore_css_price_a = get_option('wishads_cpstore_css_price_a');
	$cpstore_css_float_hover = get_option('wishads_cpstore_css_float_hover');
	$cpstore_css_float_hover_img = get_option('wishads_cpstore_css_float_hover_img');
	$cpstore_css_float_hover_p = get_option('wishads_cpstore_css_float_hover_p');
	$cpstore_css_price_hover_a = get_option('wishads_cpstore_css_price_hover_a');
	$cpstore_css_catmenu = get_option('wishads_cpstore_css_catmenu');

	?>
    
	<div class="wrap">
    <h3>For a complete explanation of the setup and use, see the <a href="<? echo WP_PLUGIN_URL; ?>/wishads-for-cafepress-store/wishads-for-cafepress-store_help.php" target="_blank">help file</a>.	</h3>

	<form name="myform" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="get_cpstore_submit" value="Y" />
    <table style="border 2px solid black;" border=0 cellspacing=5>
    <tr valign="top"><td align="left" colspan="2"><h3>Wishads for CafePress Store Configuration: </h3>
    <p>Complete the settings below to configure your wishad.</p></td></tr>
    <tr valign="top"><td width="400px" align="right" ><div style="color:red;">Required - Enter your CafePress API Key:</div></td>
    	<td><input type=text name="cpstore_cpapikey" value="<?php echo $cpstore_cpapikey ?>" /> </td></tr>
      <tr valign="top">
        <td></td><td>Until you acquire your own api key, you can use our demo key &quot;bebmn3qfxsvp4ah7z38rjrc5&quot; (without the quotes). This is a shared demo key and should not be used to run your plugin. Check the help file above for details about acquiring an api key.</td></tr>
    <tr valign="top"><td width="400px" align="right" >Enter your Commission Junction PID:</td>
    	<td><input type=text name="cpstore_cjpid" value="<?php echo $cpstore_cjpid ?>" /> </td></tr>
    <tr valign="top"><td width="400px" align="right" >Optional - Commission Junction SID</a>:</td>
    	<td><input type=text name="cpstore_cjsid" value="<?php echo $cpstore_cjsid ?>" /> </td></tr>
    <tr valign="top"><td width="400px" align="right" >Optional - <b>CafePress</b> Account Number</a>:</td>
    	<td><input type=text name="cpstore_cjxid" value="<?php echo $cpstore_cjxid ?>" /> </td></tr>
    <p>These settings can be changed per individual wishad post</p></td></tr>
    <tr valign="top">
        <td width="400px" align="right" >Default # of products to preview on the main page/archive pages:</td>
        <td><input name="cpstore_numtopreview" type=text value="<?php echo $cpstore_numtopreview ?>" size="4"><br />(Leave blank to show all products)</td></tr>
      <tr valign="top">
        <td width="400px" align="right" >Limit the number of products on single post pages to:</td>
        <td><input name="cpstore_numtoshow" type=text value="<?php echo $cpstore_numtoshow ?>" size="4"><br />(Leave blank to show all products)</td></tr>


      <tr valign="top">
        <td colspan="2" align="center" ><p style="color:red;font-weight:bold;">Edit the styles for the store grid. Copy and paste the sample styles the first time you set up the plugin, then make changes as necessary to match your site's style.</p></td></tr>
      <tr valign="top">
        <td width="400px" align="right" >Style for entire container:</td>
        <td>.cpstore_css_container<br />{<br /><textarea cols="40" rows="2" name="cpstore_css_container"><?php echo $cpstore_css_container ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>margin-left:0px;
</i></pre>
</td></tr>
      <tr valign="top">
        <td width="400px" align="right" >Style for category header:</td>
        <td>.cpstore_css_category<br />{<br /><textarea cols="40" rows="2" name="cpstore_css_category"><?php echo $cpstore_css_category ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>font-size:125%;
font-weight:bold;
</i></pre>
</td></tr>
      <tr valign="top">
        <td width="400px" align="right" >Style for category menu:</td>
        <td>.cpstore_css_catmenu<br />{<br /><textarea cols="40" rows="2" name="cpstore_css_catmenu"><?php echo $cpstore_css_catmenu ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>text-align:center;
</i></pre>
</td></tr>
      <tr valign="top">
        <td colspan="2" align="center"><h3>Normal Style (when the mouse is not over the cell):</h3></td></tr>
    
      <tr valign="top">
        <td width="400px" align="right" >Style for each cell:</td>
        <td>.cpstore_css_float<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_float"><?php echo $cpstore_css_float?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>float: left;
width: 158px;
height: 250px;
padding: 2px;
background:#F5F5F5;
border: #999999 1px solid;
text-align: center;
margin-right: 4px;
margin-bottom: 6px;</i></pre>
</td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for the thumbnail image:</td>
        <td>.cpstore_css_float img<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_float_img"><?php echo $cpstore_css_float_img ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>padding: 2px;
background:#999999;
margin-top:2px;
border: 0px;
margin-bottom: 0;
</i></pre>
</td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for the text:</td>
        <td>.cpstore_css_float p<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_float_p"><?php echo $cpstore_css_float_p ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>margin: 0;
text-align: center;
font-weight:bold;
line-height:normal;
</i></pre>
</td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for the buy now/price link:</td>
        <td>.cpstore_css_price a<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_price_a"><?php echo $cpstore_css_price_a ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>font-size:100%;
font-weight:bold;
text-decoration:none;
color:#000;
font-weight:bold;
border:2px solid;
padding:1px 1px 3px 1px;
border-color: #eee #999 #666 #e3e3e3;
background:#fff;
</i></pre>
</td></tr>

      <tr valign="top">
        <td colspan="2" align="center"><h3>Style when the mouse is over the cell:</h3></td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for each cell:</td>
        <td>.cpstore_css_float_hover<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_float_hover"><?php echo $cpstore_css_float_hover ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>float: left;
width: 158px;
height: 250px;
padding: 2px;
background:#E8E8E8;
border: #9F9F9F 1px solid;
text-align: center;
margin-right: 4px;
margin-bottom: 6px;</i></pre>
</td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for the thumbnail image:</td>
        <td>.cpstore_css_float_hover img<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_float_hover_img"><?php echo $cpstore_css_float_hover_img ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>padding: 2px;
background:#999999;
margin-top:2px;
border: 0px;
margin-bottom: 0;
</i></pre>
</td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for the text:</td>
        <td>.cpstore_css_float_hover p<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_float_hover_p"><?php echo $cpstore_css_float_hover_p ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>margin: 0;
text-align: center;
font-weight:bold;
line-height:normal;
</i></pre>
</td></tr>

      <tr valign="top">
        <td width="400px" align="right" >Style for the price link:</td>
        <td>.cpstore_css_price a:hover<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_price_hover_a"><?php echo $cpstore_css_price_hover_a ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>border-color: #666 #e3e3e3 #eee #999; 
</i></pre>
</td></tr>
      <tr valign="top">
        <td width="400px" align="right" >Style for the &quot;View All&quot; link:</td>
        <td>.cpstore_css_viewall a<br />{<br /><textarea cols="40" rows="7" name="cpstore_css_viewall"><?php echo $cpstore_css_viewall ?></textarea><br />}</td></tr>
      <tr valign="top">
        <td></td><td>Sample: 
<pre><i>text-align:center;
font-size:125%;
</i></pre>
</td></tr>
    </table>
    <input type="submit" value="Update" />
    </form></p>
	</div>
  <?
}
// ********
function cpstore_cleancache($directory)
{
	$seconds_old = 84600; // 
	if( !$dirhandle = @opendir($directory) )
			return;

	while( false !== ($filename = readdir($dirhandle)) ) {
			if( $filename != "." && $filename != ".." ) {
					$filename = $directory. "/". $filename;

					if( @filemtime($filename) < (time()-$seconds_old) )
							@unlink($filename);
			}
	}

}
// ********
function startElement($parser, $name, $attrs) 
{
    global $depth,$thisprod;
	if ($depth[$parser] == 1) {
		$temp_cat = $attrs['CATEGORYCAPTION'];
		$temp_id = $attrs['ID'];
		$temp_link = "http://www.cafepress.com/" . $attrs['STOREID'] . "." . $temp_id;
		$temp_description = $attrs['DESCRIPTION'];
		$temp_name = $attrs['NAME'];
		$temp_price = $attrs['SELLPRICE'];
		$temp_image = str_replace("240x240","150x150",$attrs['DEFAULTPRODUCTURI']);
		
		$thisprod[$temp_cat][$temp_id]["name"] = $temp_name;
		$thisprod[$temp_cat][$temp_id]["link"] = $temp_link;
		$thisprod[$temp_cat][$temp_id]["description"] = $temp_description;
		$thisprod[$temp_cat][$temp_id]["price"] = $temp_price;
		$thisprod[$temp_cat][$temp_id]["image"] = $temp_image;

	}    
	$depth[$parser]++;
}
// ********
function endElement($parser, $name) 
{
    global $depth;
    $depth[$parser]--;
}
// ********
function cpstore_myErrorHandler($errno, $errstr, $errfile, $errline)
{
    switch ($errno) {
    case E_USER_ERROR:
        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        echo "  Fatal error on line $errline in file $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Aborting...<br />\n";
        exit(1);
        break;

    case E_USER_WARNING:
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

    case E_USER_NOTICE:
        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
        break;

    default:

       //echo "Unknown error type: [$errno] $errstr<br />\n";
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}
// ********
function cpstore_get_web_page( $url )
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_USERAGENT      => "spider", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}
// ********


?>