<?php
/*********************************************************************
    settings.php

    Handles all admin settings.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('staff.inc.php');
$errors=array();
$settingOptions=array(
    'system' =>
        array(__('System Settings'), 'settings.system'),
    'tickets' =>
        array(__('Ticket Settings and Options'), 'settings.ticket'),
    'tasks' =>
        array(__('Task Settings and Options'), 'settings.tasks'),
    'agents' =>
        array(__('Agent Settings and Options'), 'settings.agents'),
    'users' =>
        array(__('User Settings and Options'), 'settings.users'),
    'pages' =>
        array(__('Site Pages'), 'settings.pages'),
    'kb' =>
        array(__('Knowledgebase Settings'), 'settings.kb'),
    'crm' =>
        array(__('CRM Settings'), 'settings.crm'),
    'poll' =>
        array(__('Poll Settings'), 'settings.poll'),
    'chat' =>
        array(__('Chat Settings'), 'settings.chat'),
    'survey' =>
        array(__('Survey Settings'), 'settings.survey'),
);
//Handle a POST.
$target=(isset($_REQUEST['t']) && $settingOptions[$_REQUEST['t']])?$_REQUEST['t']:'system';
$page = false;
if (isset($settingOptions[$target]))
    $page = $settingOptions[$target];

if($page && $_POST && !$errors) {
    if($cfg && $cfg->updateSettings($_POST,$errors)) {
        $msg=sprintf(__('Successfully updated %s.'), Format::htmlchars($page[0]));
    } elseif(!$errors['err']) {
        $errors['err'] = sprintf('%s %s',
            __('Unable to update settings.'),
            __('Correct any errors below and try again.'));
    }
}

$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfigInfo());
$ost->addExtraHeader('<meta name="tip-namespace" content="'.$page[1].'" />',
    "$('#content').data('tipNamespace', '".$page[1]."');");

$nav->setTabActive('settings', ('settings.php?t='.$target));
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.'settings-nav.inc.php');

if ($target == 'crm') {
    echo '<h2>CRM Settings</h2>';
    echo '<form action="settings.php" method="post">';
    echo '<input type="hidden" name="t" value="crm">';
    echo '<label for="crm_enabled">Enable CRM:</label>';
    echo '<input type="checkbox" name="crm_enabled" id="crm_enabled" '.(CRM_ENABLED ? 'checked' : '').'>';
    echo '<input type="submit" value="Save">';
    echo '</form>';
} elseif ($target == 'poll') {
    echo '<h2>Poll Settings</h2>';
    echo '<form action="settings.php" method="post">';
    echo '<input type="hidden" name="t" value="poll">';
    echo '<label for="poll_enabled">Enable Polls:</label>';
    echo '<input type="checkbox" name="poll_enabled" id="poll_enabled" '.(POLL_ENABLED ? 'checked' : '').'>';
    echo '<input type="submit" value="Save">';
    echo '</form>';
} elseif ($target == 'chat') {
    echo '<h2>Chat Settings</h2>';
    echo '<form action="settings.php" method="post">';
    echo '<input type="hidden" name="t" value="chat">';
    echo '<label for="chat_enabled">Enable Chat:</label>';
    echo '<input type="checkbox" name="chat_enabled" id="chat_enabled" '.(CHAT_ENABLED ? 'checked' : '').'>';
    echo '<input type="submit" value="Save">';
    echo '</form>';
} elseif ($target == 'survey') {
    echo '<h2>Survey Settings</h2>';
    echo '<form action="settings.php" method="post">';
    echo '<input type="hidden" name="t" value="survey">';
    echo '<label for="survey_enabled">Enable Surveys:</label>';
    echo '<input type="checkbox" name="survey_enabled" id="survey_enabled" '.(SURVEY_ENABLED ? 'checked' : '').'>';
    echo '<input type="submit" value="Save">';
    echo '</form>';
} else {
    include_once(STAFFINC_DIR."settings-$target.inc.php");
}

include_once(STAFFINC_DIR.'footer.inc.php');
?>
