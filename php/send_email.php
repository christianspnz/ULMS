<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "../vendor/autoload.php";

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

    $mail->setFrom("ulmsadministrator@gmail.com", "U-LMS");
    $mail->addAddress($email, $firstname);

    $mail->isHTML(true);

    $mail->addEmbeddedImage(
        "../assets/Logo.png",
        "ulmslogo"
    );

    $mail->Subject = "Welcome to U-LMS";

    $body = "
        <div style='background-color:#234CA1;max-width:600px;margin:30px auto;padding:20px;border-radius:10px;'>

        <div style='font-family:Eurostile Extd, sans-serif;background:#ffffff;padding:30px;border-radius:8px;'>

            <div style='text-align:center;'>

                <img src='cid:ulmslogo'
                     alt='U-LMS Logo'
                     style='width:150px;height:auto;margin-bottom:20px;'>

                <h1 style='color:#234CA1;font-size:30px;margin:0;'>
                    Welcome to U-LMS
                </h1>

            </div>

            <hr style='margin:25px 0;border:none;border-top:1px solid #ddd;'>

            <p style='font-size:14px;color:#234CA1;'>
                Hello <strong>{$firstname}</strong>,
            </p>

            <p style='font-size:14px;color:#234CA1;'>
                Your account has been successfully created.
            </p>

            <table width='100%' cellpadding='10'
                   style='border-collapse:collapse;margin:25px 0;'>

                <tr>
                    <td style='background:#f5f5f5;font-weight:bold;width:180px;'>
                        Email
                    </td>

                    <td style='background:#fafafa;'>
                        {$email}
                    </td>
                </tr>

                <tr>
                    <td style='background:#f5f5f5;font-weight:bold;'>
                        Temporary Password
                    </td>

                    <td style='background:#fafafa;'>
                        {$password}
                    </td>
                </tr>

            </table>

            <p style='font-size:14px;color:#234CA1;'>
                Please change your password after your first login.
            </p>

            <div style='text-align:center;margin-top:35px;'>

                <a href='http://localhost:8080/ULMS/login.php'
                   style='background:#D02027;
                          color:#ffffff;
                          text-decoration:none;
                          padding:14px 30px;
                          border-radius:6px;
                          display:inline-block;
                          font-weight:bold;'>

                    Login to U-LMS

                </a>

            </div>

            <p style='margin-top:40px;
                      text-align:center;
                      color:#777;
                      font-size:13px;'>

                This is an automated email from U-LMS.<br>
                Please do not reply to this message.

            </p>

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