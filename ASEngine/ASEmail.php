<?php
class ASEmail {
    
    function confirmationEmail($email, $key) {
        require_once '../vendor/phpmailer/PHPMailerAutoload.php';

        $mail = $this->_getMailer();

        $mail->addAddress($email);

        $link = REGISTER_CONFIRM . "?k=" . $key;

        $body = file_get_contents('../templates/confirmation-mail.php');

        $body = str_replace('{{website_name}}',WEBSITE_NAME, $body);
        $body = str_replace('{{link}}',$link, $body);

        $mail->Subject = WEBSITE_NAME . " - Registration Confirmation";
        $mail->Body    = $body;

        if( ! $mail->send() ) {
            echo 'Message could not be sent. ';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
            exit;
        }
    }

    function passwordResetEmail($email, $key) {
        require_once '../vendor/phpmailer/PHPMailerAutoload.php';

        $mail = $this->_getMailer();

        $mail->addAddress($email);

        $link = REGISTER_PASSWORD_RESET . "?k=" . $key;

        $body = file_get_contents('../templates/forgot-password-mail.php');

        $body = str_replace('{{website_name}}',WEBSITE_NAME, $body);
        $body = str_replace('{{link}}',$link, $body);

        $mail->Subject = WEBSITE_NAME . " - Password Reset";
        $mail->Body    = $body;

        if( ! $mail->send() ) {
            echo 'Message could not be sent. ';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
            exit;
        }
    }

    private function _getMailer() {
        $mail = new PHPMailer;

        if ( MAILER == 'smtp' )
        {
            $mail->isSMTP();

            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
        }

        $mail->isHTML(true);

        $mail->From     = 'noreply@' . WEBSITE_DOMAIN;
        $mail->FromName = WEBSITE_NAME;
        $mail->addReplyTo('noreply@' . WEBSITE_DOMAIN, WEBSITE_NAME);

        return $mail;
    }
}

?>
