<?php
/*
Plugin Name: HiFi
Plugin URI: http://farinspace.com/2010/03/wordpress-hifi-plugin/
Description: HIFI is a <em>head injection</em> and <em>foot injection</em> plugin. It allows you to inject <code>&lt;script&gt;</code>, <code>&lt;style&gt;</code>, <code>&lt;meta&gt;</code> and any other code you want into your posts and pages. The code injected is <em>page-specific</em>, this means that only the pages you want will be affected.
Author: Dimas Begunoff
Version: 1.0.1
Author URI: http://farinspace.com/
*/

add_action('plugins_loaded', 'hifi_loaded');

function hifi_loaded()
{
	global $hifi_fields;

	$hifi_fields = array('hifi_head','hifi_foot');
	
	define('HIFI_PLUGIN_FOLDER',str_replace('\\','/',dirname(__FILE__)));
	define('HIFI_PLUGIN_PATH','/' . substr(HIFI_PLUGIN_FOLDER,stripos(HIFI_PLUGIN_FOLDER,'wp-content')));
	define('HIFI_CSS',HIFI_PLUGIN_PATH . '/hifi.css');

	define('HIFI_META_BOX_NAME','Head & Foot');

	add_action('admin_init','hifi_init');
	add_action('wp_head','hifi_head_inject');
	add_action('wp_footer','hifi_foot_inject');
}

function hifi_head_inject()
{
	global $post;

	if (isset($post->ID))
	{
		$v = get_post_meta($post->ID,'hifi_head',TRUE);

		if ($v) echo "\n" . $v . "\n";
	}
}

function hifi_foot_inject()
{
	global $post;

	if (isset($post->ID))
	{
		$v = get_post_meta($post->ID,'hifi_foot',TRUE);
	
		if ($v) echo "\n" . $v . "\n";
	}
}

function hifi_init()
{
	wp_enqueue_style('hifi_admin_css', HIFI_CSS);

	add_meta_box('hifi_options_meta', __(HIFI_META_BOX_NAME, 'hifi'), 'hifi_options_meta', 'post', 'normal', 'high');
	add_meta_box('hifi_options_meta', __(HIFI_META_BOX_NAME, 'hifi'), 'hifi_options_meta', 'page', 'normal', 'high');

	add_action('save_post','hifi_save_meta');
}

function hifi_options_meta()
{
	global $post, $hifi_fields;

	foreach ($hifi_fields as $field_name)
	{
		${$field_name} = get_post_meta($post->ID,$field_name,TRUE);
	}

	include(HIFI_PLUGIN_FOLDER . '/meta.php');

	echo '<input type="hidden" name="hifi_options_noncename" id="hifi_options_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />' . "\n";
}

function hifi_save_meta($post_id) 
{

	global $hifi_fields;

	// make sure all new data came from the proper HIFI entry fields
	if (!wp_verify_nonce($_POST['hifi_options_noncename'],plugin_basename(__FILE__)))
	{
		return $post_id;
	}

	if ($_POST['post_type'] == 'page') 
	{
		if (!current_user_can('edit_page', $post_id)) return $post_id;
	}
	else 
	{
		if (!current_user_can('edit_post', $post_id)) return $post_id;
	}

	// save data
	foreach ($hifi_fields as $field_name) 
	{
		$current_data = get_post_meta($post_id, $field_name, TRUE);	
		$new_data = $_POST[$field_name];

		if ($current_data) 
		{
			if ($new_data == '') delete_post_meta($post_id,$field_name);
			elseif ($new_data != $current_data) update_post_meta($post_id,$field_name,$new_data);
		}
		elseif ($new_data != '')
		{
			add_post_meta($post_id,$field_name,$new_data,TRUE);
		}
	}
}

?>