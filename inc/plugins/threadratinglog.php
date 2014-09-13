<?php
/**
 * Thread Rating Log
 * Copyright 2010 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("admin_tools_menu_logs", "threadratinglog_admin_menu");
$plugins->add_hook("admin_tools_action_handler", "threadratinglog_admin_action_handler");
$plugins->add_hook("admin_tools_permissions", "threadratinglog_admin_permissions");

// The information that shows up on the plugin manager
function threadratinglog_info()
{
	global $lang;
	$lang->load("tools_ratinglog");

	return array(
		"name"				=> $lang->threadratinglog_info_name,
		"description"		=> $lang->threadratinglog_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function threadratinglog_activate()
{
	change_admin_permission('tools', 'ratinglog');
}

// This function runs when the plugin is deactivated.
function threadratinglog_deactivate()
{
	change_admin_permission('tools', 'ratinglog', -1);
}

// Admin CP log page
function threadratinglog_admin_menu($sub_menu)
{
	global $lang;
	$lang->load("tools_ratinglog");

	$sub_menu['90'] = array('id' => 'ratinglog', 'title' => $lang->thread_rating_log, 'link' => 'index.php?module=tools-ratinglog');

	return $sub_menu;
}

function threadratinglog_admin_action_handler($actions)
{
	$actions['ratinglog'] = array('active' => 'ratinglog', 'file' => 'ratinglog.php');

	return $actions;
}

function threadratinglog_admin_permissions($admin_permissions)
{
	global $db, $mybb, $lang;
	$lang->load("tools_ratinglog");

	$admin_permissions['ratinglog'] = $lang->can_manage_rating_logs;

	return $admin_permissions;
}

?>