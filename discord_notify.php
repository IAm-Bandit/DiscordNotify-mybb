<?php
if(!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

function discord_notify_info() {
    return array(
        "name"          => "Discord Notify",
        "description"   => "Sends a message to a Discord channel when a new post is created.",
        "website"       => "https://bandit.uk.to",
        "author"        => "IAm-Bandit",
        "authorsite"    => "https://bandit.uk.to",
        "version"       => "1.1",
        "guid"          => "",
        "compatibility" => "18*"
    );
}

function discord_notify_install() {
    global $db;

    $settings_group = array(
        'name' => 'discord_notify',
        'title' => 'Discord Notify Settings',
        'description' => 'Settings for the Discord Notify plugin.',
        'disporder' => 1,
        'isdefault' => 0,
    );
    
    $gid = $db->insert_query("settinggroups", $settings_group);
    
    $settings = array(
        'discord_notify_bot_token' => array(
            'title' => 'Discord Bot Token',
            'description' => 'Enter your Discord bot token.',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 1,
            'gid' => $gid
        ),
        'discord_notify_channel_id' => array(
            'title' => 'Discord Channel ID',
            'description' => 'Enter the Discord channel ID where messages will be sent.',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 2,
            'gid' => $gid
        )
    );
    
    foreach($settings as $name => $setting) {
        $setting['name'] = $name;
        $db->insert_query('settings', $setting);
    }
    
    rebuild_settings();
}

function discord_notify_is_installed() {
    global $mybb;
    return isset($mybb->settings['discord_notify_bot_token']);
}

function discord_notify_uninstall() {
    global $db;
    $db->delete_query('settings', "name IN ('discord_notify_bot_token', 'discord_notify_channel_id')");
    $db->delete_query('settinggroups', "name = 'discord_notify'");
    rebuild_settings();
}

function discord_notify_activate() {
    // Add any template changes or additional hooks if needed
}

function discord_notify_deactivate() {
    // Revert any template changes or additional hooks if needed
}

$plugins->add_hook("datahandler_post_insert_post", "discord_notify_new_post");

function discord_notify_new_post($post) {
    global $mybb;

    $channel_id = $mybb->settings['discord_notify_channel_id'];
    $bot_token = $mybb->settings['discord_notify_bot_token'];
    $post_content = $post->data['message'];
    $post_author = $post->post_insert_data['username'];

    $embed = array(
        "title" => "New Post Created",
        "description" => $post_content,
        "fields" => array(
            array(
                "name" => "Author",
                "value" => $post_author,
                "inline" => true
            )
        )
    );

    $data = json_encode(array("embeds" => array($embed)));

    $url = "https://discord.com/api/channels/{$channel_id}/messages";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bot {$bot_token}"
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
}
?>
