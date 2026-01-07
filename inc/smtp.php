<?php
// SMTP 配置 - 完全依賴後台選項設定
add_action('phpmailer_init', 'kratos_mail_smtp');
function kratos_mail_smtp($phpmailer) {
    if (kratos_option('mail_smtps') != 1) {
        return;
    }
    $mail_name       = kratos_option('mail_name');
    $mail_host       = kratos_option('mail_host');
    $mail_port       = kratos_option('mail_port');
    $mail_username   = kratos_option('mail_username');
    $mail_passwd     = kratos_option('mail_passwd');
    $mail_smtpsecure = kratos_option('mail_smtpsecure');

    if (empty($mail_host) || empty($mail_port) || empty($mail_username) || empty($mail_passwd)) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = trim($mail_host);
    $phpmailer->Port       = (int)$mail_port;
    $phpmailer->SMTPAuth   = kratos_option('mail_smtpauth') == 1;
    $phpmailer->Username   = trim($mail_username);
    $phpmailer->Password   = $mail_passwd;

    if (!empty($mail_smtpsecure)) {
        $phpmailer->SMTPSecure = strtolower(trim($mail_smtpsecure));
    }
    $phpmailer->SMTPAutoTLS = true;

    $phpmailer->Sender   = trim($mail_username);
    $phpmailer->From     = trim($mail_username);
    $phpmailer->FromName = $mail_name ?: get_bloginfo('name');

    if (!empty($mail_name)) {
        $phpmailer->addReplyTo(trim($mail_username), $mail_name);
    }

    $phpmailer->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );
    
    //Debug Option
    //$phpmailer->SMTPDebug = 0;
    //$phpmailer->Debugoutput = 'error_log';
}

// Comment approved mail - 統一新風格
add_action('comment_unapproved_to_approved', 'kratos_comment_approved');
function kratos_comment_approved($comment) {
    if (is_email($comment->comment_author_email)) {
        $blogname = htmlspecialchars_decode(get_option('blogname'), ENT_QUOTES);
        $to = trim($comment->comment_author_email);
        $subject = __('[通知] 您的留言已经通过审核', 'moedog');

        $message = '
        <div style="background:#ececec;width:100%;padding:50px 0;text-align:center">
            <div style="max-width:750px;width:100%;background:#fff;margin:0 auto;text-align:left;font-size:14px;line-height:1.5;box-shadow:0 2px 10px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden">
                <div style="padding:25px 40px;background:#518bcb;border-bottom:1px solid #467ec3">
                    <h1 style="color:#fff;font-size:25px;line-height:30px;margin:0"><a href="'.get_option('home').'" style="text-decoration:none;color:#FFF">'.$blogname.'</a></h1>
                </div>
                <div style="padding:35px 40px 50px">
                    <h2 style="font-size:18px;margin:5px 0">Hi '.trim($comment->comment_author).':</h2>
                    <p style="color:#313131;line-height:20px;font-size:15px;margin:20px 0">'.__('您有一条留言通过了管理员的审核并显示在文章页面，摘要信息请见下表。','moedog').'</p>
                    
                    <table align="center" cellspacing="0" cellpadding="0" style="width:100%;max-width:660px;margin:20px auto;border:1px solid #ccc;font-size:14px">
                        <thead>
                            <tr style="background:#eee">
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="40%">'.__('文章','moedog').'</th>
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="40%">'.__('内容','moedog').'</th>
                                <th style="padding:12px;text-align:center;border-bottom:1px solid #dfdfdf" width="20%">'.__('操作','moedog').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">《'.get_the_title($comment->comment_post_ID).'》</td>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.convert_smilies(trim($comment->comment_content)).'</td>
                                <td style="padding:12px;text-align:center"><a href="'.get_comment_link($comment->comment_ID).'" style="color:#1E5494;text-decoration:none" target="_blank">'.__('查看留言','moedog').'</a></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="font-size:13px;color:#a0a0a0;margin-top:30px">'.__('该邮件由系统自动发出，如果不是您本人操作，请忽略此邮件。','moedog').'</div>
                    <div style="font-size:12px;color:#a0a0a0;margin-top:20px">
                        <p>'.$blogname.'</p>
                        <p><span style="border-bottom:1px dashed #ccc">'.date("Y-m-d", time()).'</span></p>
                    </div>
                </div>
            </div>
        </div>';

        $headers = "Content-Type: text/html; charset=" . get_option('blog_charset') . "\r\n";
        wp_mail($to, $subject, $message, $headers);
    }
}

// Comment reply mail - 統一新風格
add_action('comment_post', 'comment_mail_notify');
function comment_mail_notify($comment_id) {
    $comment = get_comment($comment_id);
    $parent_id = $comment->comment_parent ? $comment->comment_parent : '';
    $spam_confirmed = $comment->comment_approved;

    if (($parent_id != '') && ($spam_confirmed != 'spam')) {
        $blogname = htmlspecialchars_decode(get_option('blogname'), ENT_QUOTES);
        $to = trim(get_comment($parent_id)->comment_author_email);
        $subject = __('[通知] 您的留言有了新的回复', 'moedog');

        $message = '
        <div style="background:#ececec;width:100%;padding:50px 0;text-align:center">
            <div style="max-width:750px;width:100%;background:#fff;margin:0 auto;text-align:left;font-size:14px;line-height:1.5;box-shadow:0 2px 10px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden">
                <div style="padding:25px 40px;background:#518bcb;border-bottom:1px solid #467ec3">
                    <h1 style="color:#fff;font-size:25px;line-height:30px;margin:0"><a href="'.get_option('home').'" style="text-decoration:none;color:#FFF">'.$blogname.'</a></h1>
                </div>
                <div style="padding:35px 40px 50px">
                    <h2 style="font-size:18px;margin:5px 0">Hi '.trim(get_comment($parent_id)->comment_author).':</h2>
                    <p style="color:#313131;line-height:20px;font-size:15px;margin:20px 0">'.__('您有一条留言有了新的回复，摘要信息请见下表。','moedog').'</p>
                    
                    <table align="center" cellspacing="0" cellpadding="0" style="width:100%;max-width:660px;margin:20px auto;border:1px solid #ccc;font-size:14px">
                        <thead>
                            <tr style="background:#eee">
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="35%">'.__('原文','moedog').'</th>
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="35%">'.__('回复','moedog').'</th>
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="15%">'.__('作者','moedog').'</th>
                                <th style="padding:12px;text-align:center;border-bottom:1px solid #dfdfdf" width="15%">'.__('操作','moedog').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.convert_smilies(trim(get_comment($parent_id)->comment_content)).'</td>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.convert_smilies(trim($comment->comment_content)).'</td>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.trim($comment->comment_author).'</td>
                                <td style="padding:12px;text-align:center"><a href="'.get_comment_link($comment->comment_ID).'" style="color:#1E5494;text-decoration:none" target="_blank">'.__('查看回复','moedog').'</a></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="font-size:13px;color:#a0a0a0;margin-top:30px">'.__('该邮件由系统自动发出，如果不是您本人操作，请忽略此邮件。','moedog').'</div>
                    <div style="font-size:12px;color:#a0a0a0;margin-top:20px">
                        <p>'.$blogname.'</p>
                        <p><span style="border-bottom:1px dashed #ccc">'.date("Y-m-d", time()).'</span></p>
                    </div>
                </div>
            </div>
        </div>';

        $headers = "Content-Type: text/html; charset=" . get_option('blog_charset') . "\r\n";
        wp_mail($to, $subject, $message, $headers);
    }
}

// Reset password mail - 統一新風格
add_filter('retrieve_password_message', 'kratos_reset_password_message', 10, 4);
function kratos_reset_password_message($message, $key, $user_login, $user_data) {
    add_filter('wp_mail_content_type', function() { return 'text/html'; });

    if (!is_object($user_data) || !isset($user_data->ID)) {
        return $message;
    }

    $blogname = htmlspecialchars_decode(get_option('blogname'), ENT_QUOTES);
    $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_data->user_login), 'login');

    $message = '
        <div style="background:#ececec;width:100%;padding:50px 0;text-align:center">
            <div style="max-width:750px;width:100%;background:#fff;margin:0 auto;text-align:left;font-size:14px;line-height:1.5;box-shadow:0 2px 10px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden">
                <div style="padding:25px 40px;background:#518bcb;border-bottom:1px solid #467ec3">
                    <h1 style="color:#fff;font-size:25px;line-height:30px;margin:0"><a href="'.get_option('home').'" style="text-decoration:none;color:#FFF">'.$blogname.'</a></h1>
                </div>
                <div style="padding:35px 40px 50px">
                    <h2 style="font-size:18px;margin:5px 0">Hi '.$user_data->display_name.':</h2>
                    <p style="color:#313131;line-height:20px;font-size:15px;margin:20px 0">'.__("您正在請求重設密碼，摘要訊息如下表。","moedog").'</p>
                    
                    <table align="center" cellspacing="0" cellpadding="0" style="width:100%;max-width:660px;margin:20px auto;border:1px solid #ccc;font-size:14px">
                        <thead>
                            <tr style="background:#eee">
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="40%">'.__("帳號","moedog").'</th>
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf" width="40%">'.__("信箱","moedog").'</th>
                                <th style="padding:12px;text-align:center;border-bottom:1px solid #dfdfdf" width="20%">'.__("操作","moedog").'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.$user_data->user_login.'</td>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.$user_data->user_email.'</td>
                                <td style="padding:12px;text-align:center"><a href="'.$reset_url.'" style="color:#1E5494;text-decoration:none" target="_blank">'.__("立即重設","moedog").'</a></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="font-size:13px;color:#a0a0a0;margin-top:30px">'.__("該郵件由系統自動發出，如果不是您本人操作，請忽略此郵件。","moedog").'</div>
                    <div style="font-size:12px;color:#a0a0a0;margin-top:20px">
                        <p>'.$blogname.'</p>
                        <p><span style="border-bottom:1px dashed #ccc">'.date("Y-m-d", time()).'</span></p>
                    </div>
                </div>
            </div>
        </div>';

    return $message;
}

// Register mail - 已統一新風格
add_filter('send_email_change_email', '__return_false');
add_filter('send_password_change_email', '__return_false');

add_action('init', function() {
    remove_action('register_new_user', 'wp_send_new_user_notifications');
    remove_action('network_site_new_created_user', 'wp_send_new_user_notifications');
    remove_action('network_site_users_created_user', 'wp_send_new_user_notifications');
    remove_action('network_user_new_created_user', 'wp_send_new_user_notifications');
});

add_action('user_register', 'kratos_pwd_register_mail', 101);
function kratos_pwd_register_mail($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return;
    }
    $blogname = htmlspecialchars_decode(get_option('blogname'), ENT_QUOTES);

    if (kratos_option('mail_reg')) {
        $pwd = __("您設定的密碼", "moedog");
    } else {
        $pwd = wp_generate_password(10, false);
        $update_result = wp_update_user(array(
            'ID'        => $user_id,
            'user_pass' => $pwd
        ));
        if (is_wp_error($update_result)) {
            error_log('Kratos: 註冊自動產生密碼失敗 - ' . $update_result->get_error_message());
            return;
        }
    }

    $message = '
        <div style="background:#ececec;width:100%;padding:50px 0;text-align:center">
            <div style="max-width:750px;width:100%;background:#fff;margin:0 auto;text-align:left;font-size:14px;line-height:1.5;box-shadow:0 2px 10px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden">
                <div style="padding:25px 40px;background:#518bcb;border-bottom:1px solid #467ec3">
                    <h1 style="color:#fff;font-size:25px;line-height:30px;margin:0"><a href="'.get_option('home').'" style="text-decoration:none;color:#FFF">'.$blogname.'</a></h1>
                </div>
                <div style="padding:35px 40px 50px">
                    <h2 style="font-size:18px;margin:5px 0">Hi '.$user->nickname.':</h2>
                    <p style="color:#313131;line-height:20px;font-size:15px;margin:20px 0">'.__("恭喜您註冊成功，請使用下面的資訊登入並建議盡快修改密碼。","moedog").'</p>
                    
                    <table align="center" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;margin:20px auto;border:1px solid #ccc;font-size:14px">
                        <thead>
                            <tr style="background:#eee">
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf">'.__("帳號","moedog").'</th>
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf">'.__("信箱","moedog").'</th>
                                <th style="padding:12px;text-align:center;border-right:1px solid #dfdfdf;border-bottom:1px solid #dfdfdf">'.__("密碼","moedog").'</th>
                                <th style="padding:12px;text-align:center;border-bottom:1px solid #dfdfdf">'.__("操作","moedog").'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.$user->user_login.'</td>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.$user->user_email.'</td>
                                <td style="padding:12px;text-align:center;border-right:1px solid #dfdfdf">'.$pwd.'</td>
                                <td style="padding:12px;text-align:center"><a href="'.wp_login_url().'" style="color:#1E5494;text-decoration:none" target="_blank">'.__("立即登入","moedog").'</a></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="font-size:13px;color:#a0a0a0;margin-top:30px">'.__("該郵件由系統自動發出，如果不是您本人操作，請忽略此郵件。","moedog").'</div>
                    <div style="font-size:12px;color:#a0a0a0;margin-top:20px">
                        <p>'.$blogname.'</p>
                        <p><span style="border-bottom:1px dashed #ccc">'.date("Y-m-d", time()).'</span></p>
                    </div>
                </div>
            </div>
        </div>';

    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    wp_mail($user->user_email, '[' . $blogname . '] ' . __('歡迎註冊', 'moedog'), $message, $headers);
}
