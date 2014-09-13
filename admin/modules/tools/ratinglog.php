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

$page->add_breadcrumb_item($lang->rating_log, "index.php?module=tools-ratinglog");

$sub_tabs['rating_logs'] = array(
	'title' => $lang->rating_log,
	'link' => "index.php?module=tools-ratinglog",
	'description' => $lang->rating_log_desc
);

if(!$mybb->input['action'])
{
	$page->output_header($lang->rating_log);

	$page->output_nav_tabs($sub_tabs, 'rating_logs');

	$perpage = $mybb->get_input('perpage', 1);
	if(!$perpage)
	{
		if(!$mybb->settings['threadsperpage'] || (int)$mybb->settings['threadsperpage'] < 1)
		{
			$mybb->settings['threadsperpage'] = 20;
		}

		$perpage = $mybb->settings['threadsperpage'];
	}

	$where = 'WHERE 1=1';

	// Searching for entries by a particular user
	if($mybb->input['uid'])
	{
		$where .= " AND l.uid='".$mybb->get_input('uid', 1)."'";
	}

	// Searching for entries in a specific thread
	if($mybb->input['tid'] > 0)
	{
		$where .= " AND l.tid='".$mybb->get_input('tid', 1)."'";
	}

	// Order?
	switch($mybb->input['sortby'])
	{
		case "username":
			$sortby = "u.username";
			break;
		case "thread":
			$sortby = "t.subject";
			break;
		case "rating":
			$sortby = "l.rating";
			break;
		default:
			$sortby = "l.rid";
	}
	$order = $mybb->input['order'];
	if($order != "asc")
	{
		$order = "desc";
	}

	$query = $db->query("
		SELECT COUNT(l.rating) AS count
		FROM ".TABLE_PREFIX."threadratings l
		{$where}
	");
	$rescount = $db->fetch_field($query, "count");

	// Figure out if we need to display multiple pages.
	if($mybb->input['page'] != "last")
	{
		$pagecnt = $mybb->get_input('page', 1);
	}

	$postcount = (int)$rescount;
	$pages = $postcount / $perpage;
	$pages = ceil($pages);

	if($mybb->input['page'] == "last")
	{
		$pagecnt = $pages;
	}

	if($pagecnt > $pages)
	{
		$pagecnt = 1;
	}

	if($pagecnt)
	{
		$start = ($pagecnt-1) * $perpage;
	}
	else
	{
		$start = 0;
		$pagecnt = 1;
	}

	$table = new Table;
	$table->construct_header($lang->username, array('width' => '15%'));
	$table->construct_header($lang->thread_head, array("class" => "align_center", 'width' => '40%'));
	$table->construct_header($lang->rating, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->ipaddress, array("class" => "align_center", 'width' => '15%'));

	$query = $db->query("
		SELECT l.*, u.username, u.usergroup, u.displaygroup, t.subject AS subject
		FROM ".TABLE_PREFIX."threadratings l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=l.tid)
		{$where}
		ORDER BY {$sortby} {$order}
		LIMIT {$start}, {$perpage}
	");
	while($logitem = $db->fetch_array($query))
	{
		$information = '';
		$trow = alt_trow();
		$username = format_name($logitem['username'], $logitem['usergroup'], $logitem['displaygroup']);
		if($logitem['uid'] == 0)
		{ 
			$logitem['profilelink'] = $lang->guest;
		}
		else
		$logitem['profilelink'] = build_profile_link($username, $logitem['uid']);
		if($logitem['subject'])
		{
			$information = "<a href=\"../".get_thread_link($logitem['tid'])."\" target=\"_blank\">".htmlspecialchars_uni($logitem['subject'])."</a><br />";
		}

		$table->construct_cell($logitem['profilelink']);
		$table->construct_cell($information);
		$table->construct_cell($logitem['rating'], array("class" => "align_center"));
		$table->construct_cell(my_inet_ntop($db->unescape_binary($logitem['ipaddress'])), array("class" => "align_center"));
		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_ratings, array("colspan" => "4"));
		$table->construct_row();
	}

	$table->output($lang->rating_log);

	// Do we need to construct the pagination?
	if($rescount > $perpage)
	{
		echo draw_admin_pagination($pagecnt, $perpage, $rescount, "index.php?module=tools-ratinglog&amp;perpage={$perpage}&amp;uid={$mybb->input['uid']}&amp;tid={$mybb->input['tid']}&amp;sortby={$mybb->input['sortby']}&amp;order={$order}")."<br />";
	}

	// Fetch filter options
	$sortbysel[$mybb->input['sortby']] = "selected=\"selected\"";
	$ordersel[$mybb->input['order']] = "selected=\"selected\"";

	$user_options[''] = $lang->all_users;
	$user_options['0'] = '----------';

	$query = $db->query("
		SELECT DISTINCT l.uid, u.username
		FROM ".TABLE_PREFIX."threadratings l
		LEFT JOIN ".TABLE_PREFIX."users u ON (l.uid=u.uid)
		ORDER BY u.username ASC
	");
	while($user = $db->fetch_array($query))
	{
		$selected = '';
		if($mybb->input['uid'] == $user['uid'])
		{
			$selected = "selected=\"selected\"";
		}
		$user_options[$user['uid']] = $user['username'];
	}

	$thread_options[''] = $lang->all_threads;
	$thread_options['0'] = '----------';

	$query2 = $db->query("
		SELECT DISTINCT l.tid, t.subject
		FROM ".TABLE_PREFIX."threadratings l
		LEFT JOIN ".TABLE_PREFIX."threads t ON (l.tid=t.tid)
		ORDER BY t.subject ASC
	");
	while($thread = $db->fetch_array($query2))
	{
		$thread_options[$thread['tid']] = $thread['subject'];
	}

	$sort_by = array(
		'added' => $lang->order_added,
		'username' => $lang->username,
		'rating' => $lang->rating,
		'thread' => $lang->thread_head
	);

	$order_array = array(
		'asc' => $lang->asc,
		'desc' => $lang->desc
	);

	$form = new Form("index.php?module=tools-ratinglog", "post");
	$form_container = new FormContainer($lang->filter_rating_log);
	$form_container->output_row($lang->user, "", $form->generate_select_box('uid', $user_options, $mybb->input['uid'], array('id' => 'uid')), 'uid');
	$form_container->output_row($lang->thread, "", $form->generate_select_box('tid', $thread_options, $mybb->input['tid'], array('id' => 'tid')), 'tid');
	$form_container->output_row($lang->sort_by, "", $form->generate_select_box('sortby', $sort_by, $mybb->input['sortby'], array('id' => 'sortby'))." {$lang->in} ".$form->generate_select_box('order', $order_array, $order, array('id' => 'order'))." {$lang->order}", 'order');	
	$form_container->output_row($lang->results_per_page, "", $form->generate_text_box('perpage', $perpage, array('id' => 'perpage')), 'perpage');	

	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->filter_rating_log);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}
?>