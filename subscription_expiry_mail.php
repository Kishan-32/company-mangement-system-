<?php
include 'db.php';

date_default_timezone_set('Asia/Kolkata');

/* Run only at 11:32 AM */
if (date("H:i") != "10:56") {
    exit("This script runs only at 11:32 AM");
}

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* Get subscriptions expiring within next 7 days */
$sql = "
    SELECT 
        s.id,
        s.plan_type,
        s.end_date,
        c.client_name,
        c.email,
        DATEDIFF(s.end_date, CURDATE()) AS days_left
    FROM subscriptions s
    INNER JOIN client c 
        ON c.id = s.client_id
    WHERE DATEDIFF(s.end_date, CURDATE()) BETWEEN 0 AND 7
    AND s.status = 'active'
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("No subscriptions expiring in next 7 days.");
}

while ($row = mysqli_fetch_assoc($result)) {

    $mail = new PHPMailer(true);

    try {

        /* SMTP Configuration */
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kishanaajagiya03@gmail.com'; // CHANGE
        $mail->Password   = 'ylckwvmtfjqiwivl';  // CHANGE
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        /* Email Settings */
        $mail->setFrom('kishanaajagiya03@gmail.com', 'YOUR COMPANY NAME');
        $mail->addAddress($row['email'], $row['client_name']);
        $mail->addEmbeddedImage('assets/logo.png', 'companylogo');

        $mail->isHTML(true);
        $mail->Subject = "Subscription Expiry Reminder";

        /* Dynamic Warning Color */
        $warningColor = ($row['days_left'] <= 3) ? '#d9534f' : '#f0ad4e';

        $mail->Body = '
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:20px 0;font-family:Arial,Helvetica,sans-serif;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                        
                        <!-- Header -->
                        <tr>
                            <td style="background:#ffffff;padding:25px;text-align:center;border-bottom:4px solid #1f3c88;">
                                <img src="cid:companylogo" 
                                     alt="Company Logo" 
                                     style="width:200px;height:auto;display:block;margin:auto;">
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding:30px;color:#333;">
                                
                                <h2 style="margin-top:0;color:#1f3c88;">
                                    Subscription Expiry Reminder
                                </h2>

                                <p style="font-size:15px;">
                                    Hello <strong>'.$row['client_name'].'</strong>,
                                </p>

                                <p style="font-size:15px;line-height:1.6;">
                                    Your <strong>'.$row['plan_type'].'</strong> subscription 
                                    will expire on 
                                    <strong>'.$row['end_date'].'</strong>.
                                </p>

                                <table width="100%" cellpadding="12" cellspacing="0" 
                                    style="margin:20px 0;background:#f8f9fa;border-radius:6px;">
                                    <tr>
                                        <td style="font-size:16px;color:'.$warningColor.';">
                                            Days Remaining:
                                            <strong>'.$row['days_left'].' days</strong>
                                        </td>
                                    </tr>
                                </table>

                                <p style="font-size:14px;">
                                    Please renew your subscription before expiry 
                                    to avoid service interruption.
                                </p>

                                <div style="text-align:center;margin:30px 0;">
                                    <a href="tel:7600032165"
                                    style="background:#1f3c88;
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:14px 30px;
                                    border-radius:6px;
                                    font-size:15px;
                                    font-weight:bold;
                                    display:inline-block;">
                                    📞 Renew Now
                                    </a>
                                </div>

                                <p style="font-size:13px;color:#777;">
                                    Thank you,<br>
                                    <strong>Your Company Name</strong>
                                </p>

                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="background:#f1f1f1;text-align:center;
                                       padding:15px;font-size:12px;color:#777;">
                                © '.date("Y").' Your Company Name. All Rights Reserved.
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
        ';

        $mail->send();
        echo "Reminder sent to: " . $row['email'] . "<br>";

    } catch (Exception $e) {
        echo "Mail Error: " . $mail->ErrorInfo . "<br>";
    }
}

echo "<br>All subscription reminders processed successfully.";
?>