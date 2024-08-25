<?php
if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

// Plugin information
function discord_thread_notify_info()
{
    return [
        'name' => 'Discord Thread Notification',
        'description' => 'Sends a Discord embed via webhook when a new thread is created.',
        'website' => '',
        'author' => 'Your Name',
        'authorsite' => '',
        'version' => '1.0',
        'compatibility' => '18*',
    ];
}

// Plugin installation
function discord_thread_notify_install()
{
    // Nothing to install in the database
}

function discord_thread_notify_is_installed()
{
    // Plugin is always installed if the file exists
    return true;
}

function discord_thread_notify_uninstall()
{
    // Nothing to uninstall
}

// Hooks
$plugins->add_hook('newthread_do_newthread_end', 'discord_thread_notify_send_webhook');

function discord_thread_notify_send_webhook()
{
    global $mybb, $db, $thread, $forum;

    // Get the webhook URL from settings
    $webhook_url = $mybb->settings['discord_thread_notify_webhook_url'];

    // Build the embed
    $embed = [
        'embeds' => [
            [
                'title' => $thread['subject'],
                'description' => 'A new thread has been created!',
                'url' => $mybb->settings['bburl'] . '/' . get_thread_link($thread['tid']),
                'color' => 16711680, // You can change the color if you want
                'fields' => [
                    [
                        'name' => 'Posted By',
                        'value' => '[' . htmlspecialchars($mybb->user['username']) . '](' . $mybb->settings['bburl'] . '/' . get_profile_link($mybb->user['uid']) . ')',
                        'inline' => true,
                    ],
                    [
                        'name' => 'Category',
                        'value' => '[' . htmlspecialchars($forum['name']) . '](' . $mybb->settings['bburl'] . '/' . get_forum_link($forum['fid']) . ')',
                        'inline' => true,
                    ]
                ]
            ]
        ]
    ];

    // Send the webhook
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($embed));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);
}

// Settings
function discord_thread_notify_activate()
{
    global $db;

    $setting_group = [
        'name' => 'discord_thread_notify',
        'title' => 'Discord Thread Notification Settings',
        'description' => 'Settings for the Discord Thread Notification plugin.',
        'disporder' => 1,
        'isdefault' => 0,
    ];

    $db->insert_query('settinggroups', $setting_group);
    $gid = $db->insert_id();

    $setting = [
        'name' => 'discord_thread_notify_webhook_url',
        'title' => 'Discord Webhook URL',
        'description' => 'The Discord webhook URL to send thread notifications to.',
        'optionscode' => 'text',
        'value' => '',
        'disporder' => 1,
        'gid' => $gid,
    ];

    $db->insert_query('settings', $setting);
    rebuild_settings();
}

function discord_thread_notify_deactivate()
{
    global $db;

    // Delete settings
    $db->delete_query('settings', "name = 'discord_thread_notify_webhook_url'");
    $db->delete_query('settinggroups', "name = 'discord_thread_notify'");

    rebuild_settings();
}
?>
