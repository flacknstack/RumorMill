<?php

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    $plugins->add_hook("global_start", "gossip_alerts");
}

$plugins->add_hook('global_start', 'gossip_global');
$plugins->add_hook('global_intermediate', 'gossip_index');
$plugins->add_hook('modcp_nav', 'gossip_nav');
$plugins->add_hook('member_profile_start', 'gossip_member_profile');



function gossip_info()
{
    return array(
        "name" => "Gerüchteküche",
        "description" => "Mit diesen Plugin können Charaktere Gerüchte in die Welt setzen.",
        "website" => "",
        "author" => "Ales",
        "authorsite" => "https://github.com/Ales12",
        "version" => "1.0",
        "guid" => "",
        "codename" => "",
        "compatibility" => "*"
    );
}

function gossip_install()
{
    global $db, $cache;
    //Datenbank erstellen
    if ($db->engine == 'mysql' || $db->engine == 'mysqli') {
        $db->query("CREATE TABLE `" . TABLE_PREFIX . "gossip` (
          `gossip_id` int(10) NOT NULL auto_increment,
          `gossip_text` varchar(500) CHARACTER SET utf8 NOT NULL,
          `gossip_victims` varchar(500) CHARACTER SET utf8 NOT NULL,
          `gossip_group` varchar(500) CHARACTER SET utf8 NOT NULL, 
            `gossip_from` varchar(500) CHARACTER SET utf8 NOT NULL,
        `gossip_date` date NOT NULL,
          `gossip_ok` int(10) NOT NULL  default '0',
          PRIMARY KEY (`gossip_id`)
        ) ENGINE=MyISAM" . $db->build_create_table_collation());

    }

    $db->add_column("usergroups", "canaddgossip", "tinyint NOT NULL default '1'");
    $cache->update_usergroups();

    // Einstellungen

    $setting_group = array(
        'name' => 'gossip',
        'title' => 'Gerüchteküche Einstellungen',
        'description' => 'Hier kannst du alle Einstellungen für die Gerüchteküche machen.',
        'disporder' => 2,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);


    $setting_array = array(
        // A text setting
        'gossip_group' => array(
            'title' => 'Gruppenauswahl möglich?',
            'description' => 'Soll es bei den Gerüchten möglich sein, die Gruppen auszuwählen, die explizit von dem Gerücht wissen?',
            'optionscode' => 'yesno',
            'value' => 0,
            'disporder' => 1
        ),
        // A select box
        'gossip_groupname' => array(
            'title' => 'Gruppenauswahl',
            'description' => 'Trage hier ein, welche Gruppen betroffen sein können:',
            'optionscode' => 'text',
            'value' => 'Hogwarts, Erwachsene allgemein, Todesser, Aversio',
            'disporder' => 2
        ),
        'gossip_from' => array(
            'title' => 'Gerüchtestreuer',
            'description' => 'Wer soll das Gerücht streuen? Trage hier einen extra Namen ein, wenn die gestreuten Charaktere anonym bleiben sollen:',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 3
        ),
    );

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    // Templates

    $insert_array = array(
        'title' => 'gossip',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->gossip_main}</title>
{$headerinclude}
</head>
<body>
{$header}

<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->gossip_main_welcome}</strong></td>
</tr>
<tr>
<td class="trow1">
	<div class="gossip_flex">
		{$gossip_bit}
	</div>
	
	{$opengossip_alert}
{$gossip_formular}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_bit',
        'template' => $db->escape_string('<div class="gossip_box">
	<div class="gossip_about">
	Ein Gerücht über {$gossip_allvictims}
	</div>
	<div class="gossip_rumour">
		{$rumour}
	</div>
	<div class="gossip_info">
		Gerücht gestreut von {$rumourmonger} am {$rumour_date} {$gossip_options}
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_edit',
        'template' => $db->escape_string('<form method="post" id="edit_gossip" action="misc.php?action=gossip">
<input type="hidden" value="{$gossip_id}" name="gossip_id">
	<div class="form_box">
		<div class="form_title">{$lang->gossip_text}</div>
		<textarea name="gossip" id="gossip" rows="3" cols="50">{$gossip[\'gossip_text\']}</textarea>
	</div>
					<div class="form_box">
		<div class="form_title">{$lang->gossip_date}</div>
			<input type="date" class="textbox" name="date" id="date" size="40" maxlength="1155" value="{$gossip[\'gossip_date\']}"  />	
	</div>
			<div class="form_box">
		<div class="form_title">{$lang->gossip_victim}</div>
			<input type="text" class="textbox" name="victim" id="edit_victim" size="40" maxlength="1155" value="{$gossip[\'gossip_victims\']}"  />	
	</div>
		{$form_group}
	<div class="form_go"><input type="submit" name="edit_gossip" value="{$lang->gossip_go}" id="submit" class="button"></div>
</form>


<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#edit_victim").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_formular',
        'template' => $db->escape_string('<form method="post" id="add_gossip" action="misc.php?action=gossip">
	<div class="form_flex">
	<div class="form_box">
		<div class="form_title">{$lang->gossip_text}</div>
		<textarea placeholder="trage hier dein Gerücht ein" name="gossip" id="gossip" rows="3" cols="50" required></textarea>
	</div>
					<div class="form_box">
		<div class="form_title">{$lang->gossip_date}</div>
			<input type="date" class="textbox" name="date" id="date" size="40" maxlength="1155" required />	
	</div>
			<div class="form_box">
		<div class="form_title">{$lang->gossip_victim}</div>
			<input type="text" class="textbox" name="victim" id="victim" size="40" maxlength="1155" value="{$victim}" required />	
	</div>
		{$form_group}

	<div class="form_go"><input type="submit" name="add_gossip" value="{$lang->gossip_go}" id="submit" class="button"></div>	</div>
</form>

<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#victim").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_index',
        'template' => $db->escape_string('<div class="gossip_box">
	<div class="gossip_title">
		{$lang->gossip_index}
	</div>
	<div class="gossip_about">
	{$gossip_allvictims}
	</div>
	<div class="gossip_rumour">
		{$rumour}
	</div>
	<div class="gossip_link">
		{$lang->gossip_link}
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_modcp',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->gossip_modcp}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->gossip_modcp}</strong></td>
</tr>
	<tr><td class="tcat"><strong>{$lang->gossip_modcp_new}</strong></td></tr>
<tr>
<td class="trow1">
	<div class="gossip_flex">
		{$gossip_bit}
	</div>
	</td>
</tr>
	
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_modcp_nav',
        'template' => $db->escape_string('<tr><td class="trow1 smalltext"><a href="modcp.php?action=gossip" class="modcp_nav_item modcp_nav_modqueue">{$lang->gossip_modcp_nav}</a></td></tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_modcp_new',
        'template' => $db->escape_string('<div class="new_gossip">
	<div class="thead">
		<div class="gossip_about">{$lang->gossip_modcp_gossipabout}</div>
		<div class="gossip_victims">{$gossip_allvictims}</div>
	</div>
	<div class="gossip_from tcat"><strong>Gerücht gestreut von:</strong> {$rumourmonger} am {$rumour_date}</div>
	<div class="gossip_gossipbox trow1">
		{$rumour}
	</div>
	<div class="gossip_modcp_flex trow2">
		<div class="gossip_option">
			<a href="modcp.php?action=gossip&accept={$gossip_id}">{$lang->gossip_modcp_accept}</a>
		</div>
				<div class="gossip_option">
			<a href="modcp.php?action=gossip&refuse={$gossip_id}">{$lang->gossip_modcp_refuse}</a>
		</div>
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_profile',
        'template' => $db->escape_string('	<div class="gossip_about">
	{$gossip_allvictims}
	</div>
	<div class="gossip_rumour">
		{$rumour}
	</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'gossip_options',
        'template' => $db->escape_string('// <a href="misc.php?action=gossip&deletegossip={$gossip_id}">{$lang->gossip_delete}</a> <a onclick="$(\'#edit_{$gossip_id}\').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== \'undefined\' ? modal_zindex : 9999) }); return false;" style="cursor: pointer;">{$lang->gossip_edit}</a>
                                            <div class=\'modal\' id="edit_{$gossip_id}" style=\'display: none;\'>{$gossip_edit}</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //CSS einfügen
    $css = array(
        'name' => 'gossip.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '.form_flex{
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
}

 .form_box{
	margin: 5px;
}

.form_title{
	background: #0066a2 url(images/thead.png) top left repeat-x;
	color: #ffffff;
	border-bottom: 1px solid #263c30;
	padding: 5px 10px;
	text-align: center;
	margin: 4px auto;
	font-weight: bold;
}

.form_go{
	width: 100%;
	text-align: center;
}

/*Gossip*/

.gossip_flex{
			display: flex;
	flex-wrap: wrap;
	align-items: center;
}

.gossip_box{
	width: 33%;
	margin: 5px;
}

.gossip_rumour{
	height: 100px;
	overflow: auto;
	box-sizing: border-box;
	padding: 3px;
}

.gossip_info{
	font-size: 10px;
	text-align: center;
}

/*Index*/

.gossip_title{
	background: #0066a2 url(../../../images/thead.png) top left repeat-x;
color: #ffffff;
border-bottom: 1px solid #263c30;
padding: 8px;	
}

.gossip_link{
	color: #333;
	text-align: center;
	font-weight: bold;
}

.gossip_link a{
	font-weight: bold;
		color: #333;
}

/*modcp*/

.new_gossip{
	margin: 10px;
	width: 98%;
}

.gossip_about{
	text-align: center;
	font-size: 12px;
}

.gossip_victims{
	font-size: 14px;
	text-align: center;
}


.gossip_gossipbox{
	padding: 5px;
	box-sizing: border-box;
}

.gossip_from{
		text-align: center;
	font-size: 10x;
}
.gossip_modcp_flex{
			display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: center;
}

.gossip_option{
	margin: 5px 10px;
	padding: 2px;
	text-align: center;
} ',
        'cachefile' => $db->escape_string(str_replace('/', '', 'gossip.css')),
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    // Don't forget this!
    rebuild_settings();
}

function gossip_is_installed()
{
    global $db;
    if ($db->table_exists("gossip")) {
        return true;
    }
    return false;
}

function gossip_uninstall()
{
    //Datenbanktabellen wieder löschen und Cache erneuern
    global $db, $cache;
    if ($db->table_exists("gossip")) {
        $db->drop_table("gossip");
    }

    if ($db->field_exists("canaddgossip", "usergroups")) {
        $db->drop_column("usergroups", "canaddgossip");
    }


    // Einstellungen löschen
    $db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='gossip'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='gossip_group'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='gossip_groupname'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='gossip_from'");


    $db->delete_query("templates", "title LIKE '%gossip%'");

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'gossip.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    rebuild_settings();
}

function gossip_activate()
{
    global $db, $cache;
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('gossip_alert'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('gossip_ok'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('gossip_refuse'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);


        $alertTypeManager->add($alertType);

    }

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("modcp_nav_users", "#" . preg_quote('{$nav_ipsearch}') . "#i", '{$nav_ipsearch}{$gossip_nav}');
    find_replace_templatesets("header", "#" . preg_quote('{$pm_notice}') . "#i", '{$newgossip_alert}{$pm_notice}');
    find_replace_templatesets("index", "#" . preg_quote('{$boardstats}') . "#i", '{$boardstats}{$gossip_index}');
}

function gossip_deactivate()
{
    global $db, $cache;
    //Alertseinstellungen
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertTypeManager->deleteByCode('gossip_alert');
        $alertTypeManager->deleteByCode('gossip_ok');
        $alertTypeManager->deleteByCode('gossip_refuse');
    }

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("modcp_nav_users", "#" . preg_quote('{$gossip_nav}') . "#i", '', 0);
    find_replace_templatesets("header", "#" . preg_quote('{$newgossip_alert}') . "#i", '', 0);
    find_replace_templatesets("index", "#" . preg_quote('{$gossip_index}') . "#i", '', 0);
}

// ADMIN-CP PEEKER
$plugins->add_hook('admin_config_settings_change', 'gossip_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'gossip_settings_peek');
function gossip_settings_change()
{
    global $db, $mybb, $gossip_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='gossip'", array("limit" => 2));
    $group = $db->fetch_array($result);
    $gossip_settings_peeker = ($mybb->input['gid'] == $group['gid']) && ($mybb->request_method != 'post');
}
function gossip_settings_peek(&$peekers)
{
    global $mybb, $gossip_settings_peeker;

    if ($gossip_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_gossip_group"), $("#row_setting_gossip_groupname"),/1/,true)';
    }
}


// Backend Hooks
$plugins->add_hook("admin_formcontainer_end", "gossip_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "gossip_usergroup_permission_commit");

// Usergruppen-Berechtigungen
function gossip_usergroup_permission()
{
    global $mybb, $lang, $form, $form_container, $run_module;

    if ($run_module == 'user' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc) {
        $gossip_options = array(
            $form->generate_check_box('canaddgossip', 1, "kann ein Gerücht in die Welt setzen?", array("checked" => $mybb->input['canaddgossip'])),
        );
        $form_container->output_row("Einstellung für Gerüchteküche", "", "<div class=\"group_settings_bit\">" . implode("</div><div class=\"group_settings_bit\">", $gossip_options) . "</div>");
    }
}

function gossip_usergroup_permission_commit()
{
    global $db, $mybb, $updated_group;
    $updated_group['canaddgossip'] = $mybb->get_input('canaddgossip', MyBB::INPUT_INT);
}


$plugins->add_hook('misc_start', 'gossip_misc');

/*
 * Dann wollen wir mal Gerüchte streuen!
 * Das Team kann die Gerüchte vorher kontrollieren und erst nach deren OK wird es ausgegeben.
 * Je nach Angabe wird entweder der Name des Gerüchtestreuers angegeben oder ein Alternativname, welcher im ACP angegeben werden kann.
 */
function gossip_misc()
{
    global $mybb, $templates, $db, $lang, $header, $headerinclude, $parser, $footer, $form_group, $opengossip_alert, $rumour, $rumourmonger, $rumour_date, $gossip_allvictims, $gossip_options;
    $lang->load('gossip');
    require_once MYBB_ROOT . "inc/class_parser.php";
    ;
    $parser = new postParser;
    // Do something, for example I'll create a page using the hello_world_template
    $options = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );


    if ($mybb->get_input('action') == 'gossip') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb($lang->gossip_main, "misc.php?action=gossip");

        // Wenn die Usergruppe die Erlaubnis hat, Gerüchte zu streuen, dann bitte diesen Part ausführen.
        if ($mybb->usergroup['canaddgossip'] == 1) {
            $gossip_from = $mybb->user['uid'];
            // soll es gruppen geben, welche speciell angesprochen werden sollen, dann bitte hier abgehen :D
            if ($mybb->settings['gossip_group'] == 1) {
                $groups = explode(", ", $mybb->settings['gossip_groupname']);

                foreach ($groups as $group) {
                    $gossip_group .= "<option value='{$group}'>{$group}</option>";
                }

                $form_group = "<div class='form_box'>
		<div class='form_title'>{$lang->gossip_group}</div>
			<select name='group'>
			{$gossip_group}
</select>
	</div>";
            }
            // unser wundervolles Formular
            eval ("\$gossip_formular = \"" . $templates->get("gossip_formular") . "\";");

            // Und ab mit dem Gerücht in die Datenbank. Gerüchte müssen immer vorher vom Team abgesegnet werden, weswegen es dauern kann.
            if (isset($mybb->input['add_gossip'])) {


                $add_gossip = array(
                    "gossip_text" => $db->escape_string($mybb->input['gossip']),
                    "gossip_victims" => $db->escape_string($mybb->input['victim']),
                    "gossip_date" => $mybb->input['date'],
                    "gossip_group" => $db->escape_string($mybb->input['group']),
                    "gossip_from" => $gossip_from,
                );

                $db->insert_query("gossip", $add_gossip);
                redirect("misc.php?action=gossip");
            }
        }

        $gossip_open = $db->fetch_field($db->simple_select(
            "gossip",
            "COUNT(*) as open_gossip",
            "gossip_ok='0' and gossip_from = '{$gossip_from}'",
            array("limit" => 1)
        ), "open_gossip");


        $count_opengossip = $gossip_open;

        if ($count_opengossip > 0) {
            $opengossip_alert = "<div class='red_alert'>{$lang->gossip_open}</div>";
        }

        // Dann geben wir den Spaß doch mal aus :D

        $all_gossip = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "gossip g
        LEFT JOIN " . TABLE_PREFIX . "users u
        on (g.gossip_from = u.uid)
        where gossip_ok = 1
        order by gossip_date ASC
        ");

        while ($gossip = $db->fetch_array($all_gossip)) {
            $gossip_options = "";
            $gossip_id = "";

            $gossip_id = $gossip['gossip_id'];
            eval ("\$gossip_edit = \"" . $templates->get("gossip_edit") . "\";");

            if ($mybb->user['uid'] != 0) {
                if ($mybb->usergroup['canmodcp'] == 1) {
                    if ($mybb->settings['gossip_group'] == 1) {
                        $groups = explode(", ", $mybb->settings['gossip_groupname']);

                        foreach ($groups as $group) {
                            $gossip_group .= "<option value='{$group}'>{$group}</option>";
                        }

                        $form_group = "<div class='form_box'>
                    <div class='form_title'>{$lang->gossip_group}</div>
                        <select name='group'>
                        <option value='{$gossip['gossip_group']}'>{$gossip['gossip_group']}</option>
                        {$gossip_group}
            </select>
                </div>";
                    }
                    eval ("\$gossip_options = \"" . $templates->get("gossip_options") . "\";");
                }
            }



            $gossip_victims = explode(",", $gossip['gossip_victims']);
            $all_victims = array();
            $count_victim = 0;
            if (empty($mybb->settings['gossip_from'])) {
                $username = format_name($gossip['username'], $gossip['usergroup'], $gossip['displaygroup']);
                $rumourmonger = build_profile_link($username, $gossip['uid']);
            } else {
                $rumourmonger = $mybb->settings['gossip_from'];
            }


            foreach ($gossip_victims as $gossip_victim) {
                $count_victim++;
                $gossip_victim = $db->escape_string($gossip_victim);
                $chara_query = $db->simple_select("users", "*", "username ='$gossip_victim'");
                $victim = $db->fetch_array($chara_query);

                if ($mybb->user['uid'] == $victim['uid']) {
                    if ($mybb->settings['gossip_group'] == 1) {
                        $groups = explode(", ", $mybb->settings['gossip_groupname']);

                        foreach ($groups as $group) {
                            $gossip_group .= "<option value='{$group}'>{$group}</option>";
                        }

                        $form_group = "<div class='form_box'>
                    <div class='form_title'>{$lang->gossip_group}</div>
                        <select name='group'>
                        <option value='{$gossip['gossip_group']}'>{$gossip['gossip_group']}</option>
                        {$gossip_group}
            </select>
                </div>";
                    }
                    eval ("\$gossip_options = \"" . $templates->get("gossip_options") . "\";");
                }

                $username = format_name($victim['username'], $victim['usergroup'], $victim['displaygroup']);
                $victimname = build_profile_link($username, $victim['uid']);
                array_push($all_victims, $victimname);
            }

            if ($count_victim > 1) {
                $gossip_allvictims = implode(" und ", $all_victims);
            } else {
                $gossip_allvictims = implode(", ", $all_victims);
            }

            $rumour = $parser->parse_message($gossip['gossip_text'], $options);
            $form_date = strtotime($gossip['gossip_date']);
            $rumour_date = date("d.m.Y", $form_date);



            eval ("\$gossip_bit .= \"" . $templates->get("gossip_bit") . "\";");
        }

        // Gossip Bearbeiten


        // Gossip löschen
        $delete = $mybb->input['deletegossip'];
        if ($delete) {
            $db->delete_query("gossip", "gossip_id = '{$delete}'");
            redirect("misc.php?action=gossip");
        }

        if (isset($mybb->input['edit_gossip'])) {

            $gossip_id = $mybb->input['gossip_id'];

            $edit_gossip = array(
                "gossip_text" => $db->escape_string($mybb->input['gossip']),
                "gossip_victims" => $db->escape_string($mybb->input['victim']),
                "gossip_date" => $mybb->input['date'],
                "gossip_group" => $db->escape_string($mybb->input['group']),
                "gossip_ok" => 0
            );

            $db->update_query("gossip", $edit_gossip, "gossip_id = '{$gossip_id}'");
            redirect("misc.php?action=gossip");
        }

        // Using the misc_help template for the page wrapper
        eval ("\$page = \"" . $templates->get("gossip") . "\";");
        output_page($page);
    }
}


// admins müssen ja wissen, das ein neues Gerücht im Lande ist


function gossip_global()
{
    global $db, $templates, $mybb, $lang, $newgossip_alert;
    $lang->load('gossip');
    $gossip_new = $db->fetch_field($db->simple_select(
        "gossip",
        "COUNT(*) as new_gossip",
        "gossip_ok='0'",
        array("limit" => 1)
    ), "new_gossip");

    if ($mybb->usergroup['canmodcp'] == 1) {
        if ($gossip_new > 0) {
            $newgossip_alert = "<div class='red_alert'><a href='modcp.php?action=gossip'>{$lang->gossip_modcp_newalert}</a></div>";
        }
    }

}


// Wir möchten das ganze ja noch Random auf dem Index haben

function gossip_index()
{
    global $db, $templates, $lang, $mybb, $parser, $gossip_index, $rumour;
    $lang->load('gossip');
    require_once MYBB_ROOT . "inc/class_parser.php";
    ;
    $parser = new postParser;
    $options = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );


    $count = $db->fetch_field($db->query("SELECT COUNT(*) as count_gossip
        FROM " . TABLE_PREFIX . "gossip
        WHERE gossip_ok = '1'
    "), "count_gossip");

    $gossip_count = $count;
    $gossip_allvictims = "";
    if ($gossip_count > 0) {

        $query = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "gossip
        WHERE gossip_ok = '1'
        ORDER BY RAND()
        LIMIT 1
    ");


        $gossip = $db->fetch_array($query);
        $gossip_victims = explode(",", $gossip['gossip_victims']);
        $all_victims = array();
        $count_victim = 0;


        foreach ($gossip_victims as $gossip_victim) {
            $count_victim++;
            $gossip_victim = $db->escape_string($gossip_victim);
            $chara_query = $db->simple_select("users", "*", "username ='$gossip_victim'");
            $victim = $db->fetch_array($chara_query);
            $username = format_name($victim['username'], $victim['usergroup'], $victim['displaygroup']);
            $victimname = build_profile_link($username, $victim['uid']);
            array_push($all_victims, $victimname);
        }



        if ($count_victim > 1) {
            $gossip_allvictim = implode(" und ", $all_victims);
        } else {
            $gossip_allvictim = implode(", ", $all_victims);
        }

        $gossip_allvictims = $lang->gossip_gossipabout . $gossip_allvictim;


        $rumour = $parser->parse_message($gossip['gossip_text'], $options);
    } else {
        $rumour = $lang->gossip_nogossip;
    }

    eval ("\$gossip_index .= \"" . $templates->get("gossip_index") . "\";");


}

// profile

function gossip_member_profile()
{
    global $db, $mybb, $templates, $memprofile, $parser, $lang, $gossip_profile;
    require_once MYBB_ROOT . "inc/class_parser.php";
    $lang->load('gossip');
    $parser = new postParser;
    $options = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    $charaprofile = "";
    $charaprofile = $db->escape_string($memprofile['username']);
    $query = $db->query("SELECT *
    FROM " . TABLE_PREFIX . "gossip
    WHERE gossip_ok = '1'
    and gossip_victims like '%" . $charaprofile . "%'
    ORDER BY RAND()
    LIMIT 1
");

    while ($gossip = $db->fetch_array($query)) {
        $gossip_victims = explode(",", $gossip['gossip_victims']);
        $all_victims = array();
        $count_victim = 0;


        foreach ($gossip_victims as $gossip_victim) {
            $count_victim++;
            $gossip_victim = $db->escape_string($gossip_victim);
            $chara_query = $db->simple_select("users", "*", "username ='$gossip_victim'");
            $victim = $db->fetch_array($chara_query);
            $username = format_name($victim['username'], $victim['usergroup'], $victim['displaygroup']);
            $victimname = build_profile_link($username, $victim['uid']);
            array_push($all_victims, $victimname);
        }



        if ($count_victim > 1) {
            $gossip_allvictim = implode(" und ", $all_victims);
        } else {
            $gossip_allvictim = implode(", ", $all_victims);
        }

        $gossip_allvictims = $lang->gossip_gossipabout . $gossip_allvictim;
        $rumour = $parser->parse_message($gossip['gossip_text'], $options);
        eval ("\$gossip_profile .= \"" . $templates->get("gossip_profile") . "\";");
    }
}


// Mod cp Navigation

function gossip_nav()
{
    global $templates, $lang, $gossip_nav;
    $lang->load('gossip');
    eval ("\$gossip_nav = \"" . $templates->get("gossip_modcp_nav") . "\";");
}


$plugins->add_hook('modcp_start', 'gossip_modcp');

// In the body of your plugin
function gossip_modcp()
{
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $page, $db, $parser, $modcp_nav, $gossip_allvictims, $rumour, $rumour_date, $rumourmonger, $gossip_id;
    $lang->load('gossip');
    require_once MYBB_ROOT . "inc/class_parser.php";
    ;
    $parser = new postParser;
    $options = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    /*
     * Alle noch nicht angenommenen Gerüchte
     */
    if ($mybb->get_input('action') == 'gossip') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb($lang->gossip_modcp_nav, "misc.php?action=gossip");
        $count_gossip = $db->fetch_field($db->simple_select("gossip", "COUNT(*) AS gossip_count", "gossip_ok = '0'"), "gossip_count");

        if ($count_gossip > 0) {

            $query = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "gossip g
        LEFT JOIN " . TABLE_PREFIX . "users u
        on (g.gossip_from = u.uid)
        WHERE gossip_ok = '0'
        ORDER BY gossip_date ASC
    ");


            while ($gossip = $db->fetch_array($query)) {

                $gossip_victims = explode(",", $gossip['gossip_victims']);
                $all_victims = array();
                $count_victim = 0;
                $gossip_id = $gossip['gossip_id'];
                $username = format_name($gossip['username'], $gossip['usergroup'], $gossip['displaygroup']);
                $rumourmonger = build_profile_link($username, $gossip['uid']);

                foreach ($gossip_victims as $gossip_victim) {
                    $count_victim++;
                    $gossip_victim = $db->escape_string($gossip_victim);
                    $chara_query = $db->simple_select("users", "*", "username ='$gossip_victim'");
                    $victim = $db->fetch_array($chara_query);
                    $username = format_name($victim['username'], $victim['usergroup'], $victim['displaygroup']);
                    $victimname = build_profile_link($username, $victim['uid']);
                    array_push($all_victims, $victimname);
                }

                $gossip_allvictims = "";
                if ($count_victim > 1) {
                    $gossip_allvictims = implode(" und ", $all_victims);
                } else {
                    $gossip_allvictims = implode(", ", $all_victims);
                }

                $rumour = $parser->parse_message($gossip['gossip_text'], $options);
                $form_date = strtotime($gossip['gossip_date']);
                $rumour_date = date("d.m.Y", $form_date);
                eval ("\$gossip_bit .= \"" . $templates->get("gossip_modcp_new") . "\";");
            }
        } else {
            $gossip_bit = "<div class='new_gossip'>{$lang->gossip_modcp_nogossip}</div>";
        }

        /*
         * Gerücht annehmen
         */

        $gossip_accepted = $mybb->input['accept'];
        if ($gossip_accepted) {

            // Einmal die alerts auslösen
            // Es werden die Benachrichtigt, über die es nun ein Gerücht gibt
            $gossip_victims = $db->fetch_array($db->simple_select("gossip", "gossip_victims", "gossip_id = '{$gossip_accepted}'"));
            $gossip_victims = explode(",", $gossip_victims['gossip_victims']);
            foreach ($gossip_victims as $victim) {
                $victiminfo = get_user_by_username($victim);
                $victim_id = $victiminfo['uid'];

                if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('gossip_alert');
                    if ($alertType != NULL && $alertType->getEnabled()) {
                        $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $victim_id, $alertType);
                        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                    }
                }
            }

            $gossip_from_query = $db->fetch_array($db->simple_select("gossip", "gossip_from", "gossip_id = '{$gossip_accepted}'"));
            $gossip_uid = $gossip_from_query['gossip_from'];

            if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('gossip_ok');
                if ($alertType != NULL && $alertType->getEnabled()) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $gossip_uid, $alertType);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

            $update_gossip = array(
                "gossip_ok" => 1,
            );

            $db->update_query("gossip", $update_gossip, "gossip_id = '{$gossip_accepted}'");
            redirect("modcp.php?action=gossip");
        }

        $gossip_refus = $mybb->input['refuse'];

        if ($gossip_refus) {
            $gossip_from_query = $db->fetch_array($db->simple_select("gossip", "gossip_from", "gossip_id = '{$gossip_refus}'"));
            $gossip_uid = $gossip_from_query['gossip_from'];

            if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('gossip_refuse');
                if ($alertType != NULL && $alertType->getEnabled()) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $gossip_uid, $alertType);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

            $db->delete_query("gossip", "gossip_id = '{$gossip_refus}'");
            redirect("modcp.php?action=gossip");
        }


        eval ("\$page = \"" . $templates->get("gossip_modcp") . "\";");
        output_page($page);

    }
}

function gossip_alerts()
{
    global $mybb, $lang;
    $lang->load('gossip');
    /**
     * Alert formatter for my custom alert type.
     * Alert für die Benachrichtung, das ein Gerücht über einen versendet worden ist.
     */
    class MybbStuff_MyAlerts_Formatter_GossipFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            return $this->lang->sprintf(
                $this->lang->gossip_alert,
                $outputAlert['dateline']
            );
        }

        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }
        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=gossip';
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_GossipFormatter($mybb, $lang, 'gossip_alert')
        );
    }

    /**
     * Alert formatter for my custom alert type.
     * Alert, das ein Gerücht angenommen wurde.
     */
    class MybbStuff_MyAlerts_Formatter_GossipacceptedFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            return $this->lang->sprintf(
                $this->lang->gossip_ok,
                $outputAlert['from_user'],
                $outputAlert['dateline']
            );
        }

        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }
        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=gossip';
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_GossipacceptedFormatter($mybb, $lang, 'gossip_ok')
        );
    }

    /**
     * Alert formatter for my custom alert type.
     * Alert, das ein Gerücht abgelehnt wurde.
     */
    class MybbStuff_MyAlerts_Formatter_GossiprefuseFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            return $this->lang->sprintf(
                $this->lang->gossip_refuse,
                $outputAlert['from_user'],
                $outputAlert['dateline']
            );
        }

        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }
        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=gossip';
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_GossiprefuseFormatter($mybb, $lang, 'gossip_refuse')
        );
    }
}
