<?php
/*
Plugin Name: MW WP Form LINE Group Notification
Description: Sends MW WP Form submissions to a specific LINE group
Version: 1.0
Author: FreelanceFederation
*/

// 管理画面に設定ページを追加
add_action('admin_menu', 'line_notification_menu');
function line_notification_menu() {
    add_options_page('LINE Notification Settings', 'LINE Notification', 'manage_options', 'line-notification-settings', 'line_notification_settings_page');
}

// 設定ページの内容
function line_notification_settings_page() {
    ?>
    <div class="wrap">
        <h1>LINE Notification Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('line-notification-settings-group');
            do_settings_sections('line-notification-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                <th scope="row">Channel Access Token</th>
                <td><input type="text" name="line_channel_access_token" value="<?php echo esc_attr(get_option('line_channel_access_token')); ?>" /></td>
                </tr>
                <tr valign="top">
                <th scope="row">Group ID</th>
                <td><input type="text" name="line_group_id" value="<?php echo esc_attr(get_option('line_group_id')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 設定を登録
add_action('admin_init', 'register_line_notification_settings');
function register_line_notification_settings() {
    register_setting('line-notification-settings-group', 'line_channel_access_token');
    register_setting('line-notification-settings-group', 'line_group_id');
}

// MW WP Formの送信後にLINEグループに通知を送る
add_action('mwform_after_send_mw-wp-form-38', 'send_to_line_group', 10, 3);
function send_to_line_group($form_key, $data, $Data) {
    // 設定から値を取得
    $channel_access_token = get_option('line_channel_access_token');
    $group_id = get_option('line_group_id');

    $message = "新しいフォーム送信がありました:\n\n";
    foreach ($data as $key => $value) {
        $message .= $key . ": " . $value . "\n";
    }

    $response = wp_remote_post('https://api.line.me/v2/bot/message/push', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $channel_access_token,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'to' => $group_id,
            'messages' => array(
                array(
                    'type' => 'text',
                    'text' => $message
                )
            )
        ))
    ));

    if (is_wp_error($response)) {
        error_log('LINE Messaging API送信エラー: ' . $response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if (isset($result['message'])) {
            error_log('LINE Messaging APIエラー: ' . $result['message']);
        }
    }
}