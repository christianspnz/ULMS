<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "../../vendor/autoload.php";

function sendAccountEmail($email, $firstname, $password, $preview = false)
{
    $mail = new PHPMailer(true);

    // Uncomment these only when debugging SMTP
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;

    $mail->Username = "ulmsadministrator@gmail.com";
    $mail->Password = "blbpajzvwexfcjsl"; // Replace with your App Password

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("ulmsadministrator@gmail.com", "UAAGI Learning Hub");
    $mail->addAddress($email, $firstname);

    $mail->isHTML(true);

    $mail->addEmbeddedImage(
        "../../assets/Logo.png",
        "ulmslogo"
    );

    $mail->Subject = "Your UAAGI Learning Hub Account is Ready";

    $body = "
        <div style='background-color:#f4f6fb;padding:40px 20px;font-family:Arial, sans-serif;'>

            <div style='max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(35,76,161,0.12);'>

                <!-- Header band -->
                <div style='background:#234CA1;padding:32px 20px;text-align:center;'>
                    <img src='cid:ulmslogo'
                         alt='UAAGI Learning Hub'
                         style='width:170px;height:auto;'>
                </div>

                <!-- Body -->
                <div style='padding:36px 32px;'>

                    <h1 style='color:#234CA1;font-size:22px;margin:0 0 4px 0;'>
                        Account Approved!
                    </h1>

                    <p style='color:#666666;font-size:14px;margin:0 0 24px 0;'>
                        Welcome to UAAGI Learning Hub
                    </p>

                    <p style='font-size:14px;color:#333333;line-height:1.6;margin:0 0 20px 0;'>
                        Hi <strong>{$firstname}</strong>,
                    </p>

                    <p style='font-size:14px;color:#333333;line-height:1.6;margin:0 0 24px 0;'>
                        Your account has been reviewed and approved. Use the credentials below to sign in for the first time.
                    </p>

                    <!-- Credentials card -->
                    <div style='background:#f8fafc;border:1px solid #e5e9f2;border-radius:10px;padding:20px 24px;margin-bottom:24px;'>

                        <div style='margin-bottom:14px;'>
                            <p style='margin:0;font-size:11px;letter-spacing:0.5px;color:#8a94a6;text-transform:uppercase;font-weight:bold;'>
                                Email
                            </p>
                            <p style='margin:2px 0 0 0;font-size:15px;color:#234CA1;font-weight:bold;'>
                                {$email}
                            </p>
                        </div>

                        <div>
                            <p style='margin:0;font-size:11px;letter-spacing:0.5px;color:#8a94a6;text-transform:uppercase;font-weight:bold;'>
                                Temporary Password
                            </p>
                            <p style='margin:2px 0 0 0;font-size:15px;color:#234CA1;font-weight:bold;letter-spacing:0.5px;'>
                                {$password}
                            </p>
                        </div>

                    </div>

                    <!-- Security note -->
                    <div style='background:#fff8e6;border-left:3px solid #f0b429;border-radius:6px;padding:12px 16px;margin-bottom:28px;'>
                        <p style='margin:0;font-size:13px;color:#7a5c00;line-height:1.5;'>
                            For your security, please change this temporary password immediately after logging in.
                        </p>
                    </div>

                    <!-- CTA -->
                    <div style='text-align:center;margin-bottom:8px;'>
                        <a href='http://localhost:8080/ULMS/login.php'
                           style='background:#D02027;
                                  color:#ffffff;
                                  text-decoration:none;
                                  padding:14px 36px;
                                  border-radius:8px;
                                  display:inline-block;
                                  font-weight:bold;
                                  font-size:14px;'>
                            Log In to Your Account
                        </a>
                    </div>

                </div>

                <!-- Footer -->
                <div style='background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #eef1f6;'>
                    <p style='margin:0;font-size:12px;color:#9aa3b5;line-height:1.5;'>
                        This is an automated message from UAAGI Learning Hub.<br>
                        Please do not reply to this email.
                    </p>
                </div>

            </div>

        </div>
    ";

    $mail->Body = $body;

    // ============================
    // PREVIEW MODE
    // ============================
    if ($preview) {
        echo $body;
        exit;
    }

    // ============================
    // SEND EMAIL
    // ============================
    try {

        $mail->send();

        return true;

    } catch (Exception $e) {

        die("Email could not be sent.<br><br>" . $mail->ErrorInfo);

    }
}