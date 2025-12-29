<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    /**
     * 發件人信箱（建議與 SMTPUser 相同或使用相同域名的信箱）
     */
    public string $fromEmail  = 'mail@srl.tw';
    
    /**
     * 發件人名稱（會顯示在收件人的郵件中）
     */
    public string $fromName   = '系統管理員';
    public string $recipients = '';

    /**
     * The "user agent"
     * 郵件 User-Agent 字串，用於識別發送郵件的應用程式
     * 可自訂為您的應用程式名稱，不影響功能
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     * 郵件發送協議：mail (PHP mail), sendmail, smtp
     * 使用 SMTP 時設定為 'smtp'
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     * sendmail 執行路徑，僅在使用 protocol = 'sendmail' 時需要
     * 使用 SMTP 時此設定無效，可保持預設值
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = 'mail.srl.tw';

    /**
     * SMTP Username
     */
    public string $SMTPUser = 'mail@srl.tw';

    /**
     * SMTP Password
     */
    public string $SMTPPass = 'RCWCL19d6d4ze5hiQwZb';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 465;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to 'ssl', port 587 should use 'tls'.
     */
    public string $SMTPCrypto = 'ssl';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     * 郵件類型：'text' 純文字，'html' HTML 格式
     * 聯絡表單回信使用 HTML 格式，此設定會影響預設郵件類型
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;
}
