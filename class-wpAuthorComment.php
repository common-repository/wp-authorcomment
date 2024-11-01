<?php

// TO DO
// internationalize
// test for compatiblity

class wpAuthorComment {
	var $pluginName = "wp-AuthorComment";
	var $installRequirements = array(
			"WordPress Version"	=> "2.7",
		);
	var $localPluginPath = "wp-authorcomment/wp-authorcomment.php"; // relative from plugin folder
	var $pluginPath = ""; // we will assign WP_PLUGIN_DIR base in __construct
	var $remoteFileURL = 'http://a.kingdesk.com/wp-authorcomment.php';
	var $option_group = "wpac_options"; //used to register options for option page
	var $settings;
	var $adminResourceLinks = array(
			/*
			"anchor text"			=> string URL,		// REQUIRED
			*/
			"Plugin Home"	 		=> "http://kingdesk.com/projects/wp-authorcomment/",
		);
	var $adminFormSections = array( // sections will be displayed in the order included
			/*
			"id" 					=> string heading,		// REQUIRED
			*/
			"options" 		=> "Options",
		);
	var $adminFormSectionFieldsets = array( // fieldsets will be displayed in the order included
			/*
			"id" => array(
				"heading" 	=> string Fieldset Name,	// REQUIRED
				"sectionID" 	=> string Parent Section ID,	// REQUIRED
			),
			*/
		);
	var $adminFormControls = array(
			/*
			"id" => array(
				"section" 		=> string Section ID, 		// REQUIRED
				"fieldset" 		=> string Fieldset ID,		// OPTIONAL
				"labelBefore" 		=> string Label Content,	// OPTIONAL
				"labelAfter"	=> string Label Content,	// OPTIONAL, only for controls of type "select", where the control is in the middle of a label
				"helpText" 		=> string Help Text,		// OPTIONAL
				"control" 		=> string Control,			// REQUIRED
				"inputType" 	=> string Control Type,		// OPTIONAL
				"optionValues"	=> array(value=>text, ... )	// OPTIONAL, only for controls of type "select"
				"default" 		=> string Default Value,	// REQUIRED (although it may be an empty string)
			),
			*/
			"wpacAuthorStyle" => array(
				"section" 		=> "options",
				"labelBefore" 	=> "CSS style for author comments:",
				"helpText" 		=> "Only include <code>property: value;</code> declarations.",
				"control" 		=> "textarea",
				"default" 		=> "background-color: LightYellow;",
			),
		);
	
	//PHP 4 constructor
	function wpAuthorComment() {
		$this->__construct();
	}
	
	//PHP 5 constructor
	function __construct(){
		global $wp_version;
		if(is_admin()) {
			if (version_compare($wp_version, $this->installRequirements['WordPress Version'], '<' ) ) {
				add_action('admin_notices', array(&$this, 'add_action_admin_notices_wpVersionIncompatible'));
			}
		}
		
		$this->pluginPath = WP_PLUGIN_DIR."/".$this->localPluginPath;
		
		$wpacRestoreDefaults = FALSE;
		if(get_option('wpacRestoreDefaults') == TRUE) {
			$wpacRestoreDefaults = TRUE;
		}
		$this->register_plugin($wpacRestoreDefaults);

		foreach($this->adminFormControls as $key => $value) {
			$this->settings[$key] = get_option($key);
		}
	
		// set up the plugin options page
		register_activation_hook($this->pluginPath, array(&$this, 'register_plugin'));
		add_action('admin_menu', array(&$this, 'add_options_page'));
		add_action('admin_init', array(&$this, 'register_the_settings'));

		global $wp_version;
		if ( version_compare($wp_version, '2.7', '>=' ) ) {
			add_filter( "plugin_action_links_".$this->localPluginPath, array(&$this, 'add_filter_plugin_action_links'));
		}

		if($this->adminFormControls["wpacAuthorStyle"] != "") add_action('wp_head', array(&$this, 'add_wp_head'));

		return;
	}
	
	function author_comment() {
		global $comment, $post, $authorClass;
		$authordata = get_userdata($post->post_author);
		$postAuthor = $authordata->display_name;
		$commentAuthor = $comment->comment_author;
		if ($postAuthor == $commentAuthor) echo " ".$this->settings["wpacAuthorClass"];
	}
	

	function register_plugin($update = FALSE) {
		// grab configuration variables
		foreach($this->adminFormControls as $key => $value) {
			if($update || !is_string(get_option($key))) {
				update_option($key, $value["default"]);
			}
		}
		update_option("wpacRestoreDefaults", 0);

		return;
	}

	function register_the_settings() {
		foreach($this->adminFormControls as $controlID => $control){
			register_setting( $this->option_group, $controlID );
		}
		register_setting( $this->option_group, "wpacRestoreDefaults" );
	}

	function add_options_page() {
		add_options_page($this->pluginName, $this->pluginName, 9, strtolower($this->pluginName), array(&$this, 'get_admin_page_content'));
		return;
	}

	function add_filter_plugin_action_links($links) {
		if (function_exists('admin_url')) {	// since WP 2.6.0
			$adminurl = trailingslashit(admin_url());			
		} else {
			$adminurl = trailingslashit(get_settings('siteurl')).'wp-admin/';
		}
	
		// Add link "Settings" to the plugin in /wp-admin/plugins.php
		$settings_link = '<a href="'.$adminurl.'options-general.php?page='.strtolower($this->pluginName).'">' . __('Settings') . '</a>';
		array_push($links, $settings_link);
		return $links;
	}


	// admin page content
	function get_admin_page_content() {
?>

<style type="text/css">
	#poststuff .inside {
		margin: 2em;
	}
	.submitdiv .inside {
		margin:  0 !important;
		padding-top: 2em;
	}
	.publishing-settings {
		border-bottom-color:#DDDDDD;
		border-bottom-style:solid;
		border-bottom-width:1px;
		padding: 0 1em 1em;;
	}
	.publishing-actions {
		background:#EAF2FA none repeat scroll 0 0;
		border-top:medium none;
		clear:both;
		padding:6px 1em;
	}
	.publishing-action {
		float:right;
		text-align:right;
	}
	fieldset {
		margin:2em -1px 1em;
		padding: 2em 1em 1em;
		border: 1px solid #dfdfdf;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: #fbfbfb;
	}
	legend {
		font-size: 111%;
		font-weight: 700;
		font-style: italic;
	}
	span.helpText {
		color: gray;
		font-size: 90%;
		margin: .3125em 0 0 1.875em;
	}
	samp {
		border: 1px solid #dfdfdf;
		padding: .35em .25em .2em;
		background-color:#fbfbfb;
		color: #000;
		
	}
	span.helpText samp {
		font-size: 111%;
	}
	fieldset samp {
		background-color:#f9f9f9;
	}
	textarea{
		width: 100%;
		margin: -.75em 0 1em;
		background-color:#fff;
	}
	label {
		font-size: 111%;
		display: block;
		margin-bottom: 1em;
		line-height: 1.5em;
	}
	select, input {
		margin-top: -.1em;
	}
	.publishing-action input {
		margin-top: 0;
	}

	.control {
		margin: 0 1em;
	}
	fieldset .control {
		margin: 0;
	}
	.text-button {
		background: none;
		border: none;
		text-decoration: underline;
	}
	.text-button:hover {
		cursor: pointer;
	}
</style>

<div class='wrap'>
<div id='icon-options-general' class='icon32'><br /></div>
<h2><?php echo $this->pluginName; ?></h2>

<?php echo $this->get_admin_page_alert(); ?>

<div id='poststuff' class='metabox-holder'>

<div id="resource-links" class='postbox' >
<h3><span><?php _e("Resource Links"); ?></span></h3>
<div class='inside'>

<?php $i=0; ?>
<?php foreach($this->adminResourceLinks as $anchor => $url) { ?>
	<?php if($i++ > 0) echo " | ";?><a href="<?php echo $url; ?>"><?php echo __("$anchor") ?></a>
<?php } ?>

</div>
</div>

<form method="post" action="options.php">
<?php  settings_fields($this->option_group); ?>
	
<?php foreach($this->adminFormSections as $sectionID => $heading): ?>
<div id="<?php echo $sectionID; ?>" class='postbox submitdiv' >
<h3><span><?php _e($heading); ?></span></h3>
<div class='inside'>
<div class='submitbox'>
<div class='publishing-settings'>

<?php
	$fieldsetID = NULL;
	foreach($this->adminFormControls as $controlID => $adminFormControl) {
		if($adminFormControl["section"] == $sectionID ) {
			if($adminFormControl["fieldset"] != $fieldsetID) {
				if($fieldsetID) { // close previous fieldset (if it existed)
					echo "</fieldset>\r\n\r\n";
				}
				if($adminFormControl["fieldset"]) { // start any new fieldset (if it exists)
					echo "\r\n<fieldset id='".$adminFormControl["fieldset"]."'>\r\n";
					echo "<legend>".$this->adminFormSectionFieldsets[$adminFormControl["fieldset"]]["heading"]."</legend>\r\n";
				}
				$fieldsetID = $adminFormControl["fieldset"];
			}
		
		
			echo $this->get_admin_form_control(
					$controlID,
					$adminFormControl['control'],
					$adminFormControl['inputType'],
					$adminFormControl['labelBefore'],
					$adminFormControl['labelAfter'],
					$adminFormControl['helpText'],
					$adminFormControl['optionValues']
					);
		}
	}
	if($fieldsetID) { // we have an unclosed fieldset
		echo "</fieldset>\r\n\r\n";
	}
?>

</div><!-- .publishing-settings -->
<div class='publishing-actions'>
<?php echo $this->get_admin_form_control("saveChanges", "input", "submit"); ?>
<?php echo $this->get_admin_form_control("wpacRestoreDefaults", "input", "submit"); ?>
<div class='clear'></div>
</div><!-- .publishing-actions -->
</div><!-- .submitbox -->
</div><!-- .inside -->
</div><!-- .postbox.submitdiv -->

<?php endforeach; //adminFormSections ?>

</form>


<div id="usage-notes" class='postbox' >
<h3><span><?php _e("Usage Notes"); ?></span></h3>
<div class='inside'>
<?php _e("
	<p>If you are using a recently developed theme, no further action should be needed.  By default, the post author's comments should now have a light yellow background. If they do not, you will need to take the following steps:</p>
	
	<ol>
		<li>Log in to your WordPress admin panel as an administrator.</li>
		<li>Go to <code>Appearance > Editor</code></li>
		<li>Select the <code>Comments (comments.php)</code> file &mdash; <code>Popup Comments (comments-popup.php)</code> if you use popup comments.</li>
		<li>Locate the opening HTML tag that wraps individual comments. This will be inside of a PHP <code>foreach</code> statement.  The opening tag should be an HTML <code>div</code> or <code>li</code> element.</li>
		<li>If this element already has a class name assigned, add the following code within the class name's quote marks:
			<pre><code>&lt;?php if (method_exists($wpac, 'author_comment')) $wpac->author_comment();?&gt;</code></pre>
		</li>
		<li>If this element does not have any classes assigned, add the following code within the opening before the end of the opening tag:
			<pre><code>class='&lt;?php if (method_exists($wpac, 'author_comment')) $wpac->author_comment();?&gt;'</code></pre>
		</li>
		<li>Click the 'Update File' button.</li>
	</ol>
"); ?>

</div>
</div>



</div><!-- #poststuff.metabox-holder -->
</div><!-- .wrap -->
<div class='clear'></div>

<?php
		return;
	}
	
	function get_admin_page_alert() {
		if(function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
			curl_setopt($ch, CURLOPT_URL, $this->remoteFileURL);
			$content = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($httpCode == 404) {
				$content = "";
			}
			curl_close($ch);
			if ($content) {
				return "<div class='updated fade'>".$content."</div><!-- .updated.fade -->\r\n";
			}
		}
				
		return FALSE;
	}
	
	//	parameter	$id REQUIRED STRING
	//				$control REQUIRED STRING, must be: "input", "select", or "textarea"; not implemented: "button"
	//				$inputType OPTIONAL STRING, for $control = "input"; must be: "text", "password", "checkbox", "submit", "hidden"; not implemented: "radio", "image", "reset", "button", "file"
	//				$labelBefore OPTIONAL STRING, set this to the text that should appear before the control
	//				$labelAfter OPTIONAL STRING, set this to the text that should appear after the control; not for $control = "textarea"
	//				$helpText OPTIONAL STRING, requires an accompanying label
	//				$optionValues OPTIONAL ARRAY, in the form array($value => $display)
	function get_admin_form_control($id, $control="input", $inputType="text", $labelBefore=NULL, $labelAfter=NULL, $helpText=NULL, $optionValues=NULL) {
		$helpTextClass = "helpText";
		if($inputType != "submit") {
			$value = get_option($id);
		} elseif ($id == "wpacRestoreDefaults") {
			$value = "Restore Defaults";
		} else {
			$value = "Save Changes";
		}

		if($inputType == "checkbox") {
			$checked = "";
			if($value) $checked = 'checked="checked" ';
			
		}
		
		//make sure $value is in $optionValues if $optionValues is set
		if($optionValues && !isset($optionValues[$value])) {
			$value = NULL;
		}
		
	
		if($inputType=="submit"){
			$controlMarkup = "<div class='publishing-action'>";
		} else {
			$controlMarkup = "<div class='control'>";
		}
		
		if(($labelBefore || $labelAfter) && $inputType != "hidden" && $inputType != "submit"){
			$controlMarkup .= "<label for='$id'>";
			if($labelBefore) {
				$controlMarkup .= "$labelBefore ";
			}
			if($control == "textarea") {
				if($helpText) {
					$controlMarkup .= "<span class='$helpTextClass'>$helpText</span>";
				}
				$controlMarkup .= "</label>";
			}
		}
		
		$controlMarkup .= "<$control ";
		
		if($control == "input") {
			$controlMarkup .= "type='$inputType' ";
		}
		
		if($inputType=="submit" && $value == "Restore Defaults") {
			$controlMarkup .= "name='$id' class='text-button'"; //to avoid duplicate ids and some pretty stylin'
		} elseif($inputType=="submit") {
			$controlMarkup .= "name='$id' class='button-primary'"; //to avoid duplicate ids and some pretty stylin'
		} else {
			$controlMarkup .= "id='$id' name='$id' ";
		}

		if($value && $control != "select" && $control != "textarea" && $inputType != "checkbox") {
			$controlMarkup .= "value='$value' ";
		} elseif($inputType == "checkbox") {
			$controlMarkup .= "value='1' $checked";
		}
		
		if($control != "select" && $control != "textarea") {
			$controlMarkup .= " />";
		} elseif($control == "textarea") {
			$controlMarkup .= " >";
			if($value) {
				$controlMarkup .= $value;
			}
			$controlMarkup .= "</$control>";
		} elseif($control == "select") {
			$controlMarkup .= " >";
			foreach($optionValues as $optionValue => $display){
				$selected = "";
				if($value == $optionValue) $selected = "selected='selected'";
				$controlMarkup .= "<option value='$optionValue' $selected>$display</option>";
			}
			$controlMarkup .= "</$control>";
		}
		
		if(($labelBefore || $labelAfter) && $control != "textarea") {
			if($labelAfter) {
				$controlMarkup .= " $labelAfter";
			}
			if($helpText) {
				$controlMarkup .= "<span class='$helpTextClass'>$helpText</span>";
			}
			$controlMarkup .= "</label>";
		}
		
		$controlMarkup .= "</div>\r\n";

		return $controlMarkup;
	}

	function add_action_admin_notices_wpVersionIncompatible() { 
		global $wp_version;
		echo '<div class="error"><p>'.__('The activated plugin ').'<strong>'.$this->pluginName.'</strong>'.__(' requires WordPress version ').$this->installRequirements['WordPress Version'].__(' or later.  You are running WordPress version ').$wp_version.__('. Please deactivate this plugin, or upgrade your installation of WordPress.').'</p></div>'; 
	}
	
	function add_wp_head() {
		global $authorStyle, $authorClass;
		echo  '<style type="text/css" media="screen"> .bypostauthor { '.$this->settings["wpacAuthorStyle"].' } </style>'."\r\n";
	}
}
