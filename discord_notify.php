<?php
if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

// Plugin information
function discord_notify_info()
{
    return [
        'name' => 'Discord Notify',
        'description' => 'Sends a Discord embed via webhook when a new thread is created.',
        'website' => '',
        'author' => 'Bandit',
        'authorsite' => 'https://asbprogramming.uk.to',
        'version' => '2.0',
        'compatibility' => '18*',
    ];
}

// Plugin installation
function discord_notify_install()
{
    // Nothing to install in the database for now
}

function discord_notify_is_installed()
{
    global $mybb;
    return isset($mybb->settings['discord_notify_webhook_url']);
}

function discord_notify_uninstall()
{
    global $db;
    // Remove the settings
    $db->delete_query('settings', "name IN ('discord_notify_webhook_url')");
    $db->delete_query('settinggroups', "name = 'discord_notify'");
    rebuild_settings();
}

// Hooks
$plugins->add_hook('newthread_do_newthread_end', 'discord_notify_send_webhook');

function discord_notify_send_webhook()
{
    global $mybb, $db, $thread, $forum, $post;

    // Get the webhook URL from settings
    $webhook_url = $mybb->settings['discord_notify_webhook_url'];
    if (empty($webhook_url)) {
        return; // No webhook URL set, do nothing
    }

    // Build the embed
    $embed = [
        'embeds' => [
            [
                'title' => htmlspecialchars($thread['subject']),
                'description' => htmlspecialchars($post['message']),
                'url' => $mybb->settings['bburl'] . '/' . get_thread_link($thread['tid']),
                'color' => 16711680, // Red color in decimal (you can change this)
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
                    ],
                    [
                        'name' => 'Thread Link',
                        'value' => '[View Thread](' . $mybb->settings['bburl'] . '/' . get_thread_link($thread['tid']) . ')',
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

    // Optional: Log the response or handle errors
}

// Settings
function discord_notify_activate()
{
    global $db;

    // Create the setting group
    $setting_group = [
        'name' => 'discord_notify',
        'title' => 'Discord Notify Settings',
        'description' => 'Settings for the Discord Notify plugin.',
        'disporder' => 1,
        'isdefault' => 0,
    ];

    $db->insert_query('settinggroups', $setting_group);
    $gid = $db->insert_id();

    // Create the settings
    $setting = [
        'name' => 'discord_notify_webhook_url',
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

function discord_notify_deactivate()
{
    global $db;

    // Delete settings
    $db->delete_query('settings', "name = 'discord_notify_webhook_url'");
    $db->delete_query('settinggroups', "name = 'discord_notify'");

    rebuild_settings();
}
?>
