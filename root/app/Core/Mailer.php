<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols
/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Mailer.php
 * Description: PHPMailer wrapper for email notifications
 */

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Core\ErrorHandler;

class Mailer
{
    /**
     * Send an email via configured SMTP server.
     *
     * @param string $to      Recipient address
     * @param string $subject Email subject
     * @param string $body    Email body
     * @param bool   $html    Whether the body is already HTML
     */
    public static function send(string $to, string $subject, string $body, bool $html = false): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            if ($html) {
                $mail->Body    = $body;
                $mail->AltBody = strip_tags($body);
            } else {
                $mail->Body    = nl2br($body);
                $mail->AltBody = $body;
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            ErrorHandler::getInstance()->log('Mailer Error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send an email using a template file located in app/Templates.
     *
     * @param string $to       Recipient address
     * @param string $subject  Email subject
     * @param string $template Template filename without extension
     * @param array  $data     Variables to extract into the template
     */
    public static function sendTemplate(string $to, string $subject, string $template, array $data = []): bool
    {
        $templatePath = __DIR__ . '/../Templates/' . $template . '.php';
        $headerPath   = __DIR__ . '/../Templates/email_header.php';
        $footerPath   = __DIR__ . '/../Templates/email_footer.php';

        if (!file_exists($templatePath)) {
            ErrorHandler::getInstance()->log('Template not found: ' . $template, 'error');
            return false;
        }

        // Fallback to basic header/footer if files are missing
        $header = file_exists($headerPath) ? $headerPath : null;
        $footer = file_exists($footerPath) ? $footerPath : null;

        extract($data, EXTR_SKIP);
        ob_start();
        if ($header) {
            include $header;
        }
        include $templatePath;
        if ($footer) {
            include $footer;
        }
        $body = ob_get_clean();

        return self::send($to, $subject, $body, true);
    }
}
