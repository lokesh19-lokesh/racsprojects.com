<?php
/**
 * RACS Projects - Lead Handler & Email Sender
 * Delivers full lead details from contact.html & mep.html directly to projects.racs@gmail.com
 */

// Target email recipient
$to = 'projects.racs@gmail.com';

// Handle CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Allow fallback GET redirect if accessed directly in browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Helper function to sanitize user inputs
function sanitize_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

// Normalize POST keys to lowercase without spaces or underscores
$normalizedPost = [];
foreach ($_POST as $key => $val) {
    if (is_string($val)) {
        $cleanKey = strtolower(str_replace([' ', '_', '-'], '', $key));
        $normalizedPost[$cleanKey] = sanitize_input($val);
    }
}

// Extract fields
$fullName = isset($normalizedPost['fullname']) ? $normalizedPost['fullname'] : (isset($normalizedPost['name']) ? $normalizedPost['name'] : 'Website Lead');
$email    = isset($normalizedPost['email']) ? $normalizedPost['email'] : '';
$phone    = isset($normalizedPost['phone']) ? $normalizedPost['phone'] : '';

$service  = isset($normalizedPost['requiredservice']) ? $normalizedPost['requiredservice'] : (isset($normalizedPost['servicerequired']) ? $normalizedPost['servicerequired'] : (isset($normalizedPost['service']) ? $normalizedPost['service'] : ''));

$company  = isset($normalizedPost['companyname']) ? $normalizedPost['companyname'] : (isset($normalizedPost['company']) ? $normalizedPost['company'] : '');
$category = isset($normalizedPost['projectcategory']) ? $normalizedPost['projectcategory'] : (isset($normalizedPost['category']) ? $normalizedPost['category'] : '');
$location = isset($normalizedPost['projectlocation']) ? $normalizedPost['projectlocation'] : (isset($normalizedPost['location']) ? $normalizedPost['location'] : '');
$message  = isset($normalizedPost['message']) ? $normalizedPost['message'] : (isset($normalizedPost['projectdetails']) ? $normalizedPost['projectdetails'] : '');

$subject = "New MEP Lead Enquiry - " . (!empty($fullName) ? $fullName : "RACS Projects Website");
$emailBody = build_email_body($fullName, $email, $phone, $service, $company, $category, $location, $message);

// Method 1: PHPMailer via vendor/autoload.php
$mailSent = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->setFrom('contact@racsprojects.in', 'RACS Projects Leads');
            $mail->addAddress($to);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($email, $fullName);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $emailBody;
            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            $mailSent = false;
        }
    }
}

// Method 2: Native PHP mail() with Valid Server Domain Headers
if (!$mailSent) {
    $fromEmail = 'contact@racsprojects.in';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: RACS Projects Leads <" . $fromEmail . ">\r\n";
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: " . $fullName . " <" . $email . ">\r\n";
    }
    @mail($to, $subject, $emailBody, $headers);
}

// Method 3: cURL Relay to FormSubmit API as secondary backup
if (function_exists('curl_init')) {
    $postPayload = $_POST;
    $postPayload['_subject'] = $subject;
    $postPayload['_captcha'] = 'false';

    $ch = curl_init('https://formsubmit.co/ajax/' . $to);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postPayload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    @curl_exec($ch);
    @curl_close($ch);
}

// Helper function to construct clean HTML email layout
function build_email_body($fullName, $email, $phone, $service, $company, $category, $location, $message) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; background: #ffffff; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e1e8ed; }
        .header { background: #17232D; color: #ffffff; padding: 25px; text-align: center; }
        .header h2 { margin: 0; font-size: 22px; color: #1D8FCD; }
        .header p { margin: 5px 0 0; font-size: 14px; opacity: 0.8; }
        .content { padding: 30px; }
        .field-group { margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; }
        .field-label { font-weight: bold; color: #17232D; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .field-value { font-size: 15px; color: #4E4E4E; margin-top: 4px; }
        .footer { background: #f8fafc; padding: 15px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <h2>RACS Projects - New Lead Enquiry</h2>
          <p>Received from Website Contact Form</p>
        </div>
        <div class="content">
          <div class="field-group">
            <div class="field-label">Full Name</div>
            <div class="field-value">' . (!empty($fullName) ? $fullName : 'N/A') . '</div>
          </div>
          <div class="field-group">
            <div class="field-label">Phone Number</div>
            <div class="field-value"><a href="tel:' . $phone . '" style="color:#1D8FCD; text-decoration:none; font-weight:bold;">' . (!empty($phone) ? $phone : 'N/A') . '</a></div>
          </div>
          <div class="field-group">
            <div class="field-label">Email Address</div>
            <div class="field-value"><a href="mailto:' . $email . '" style="color:#1D8FCD; text-decoration:none;">' . (!empty($email) ? $email : 'N/A') . '</a></div>
          </div>
          ' . (!empty($company) ? '<div class="field-group"><div class="field-label">Company Name</div><div class="field-value">' . $company . '</div></div>' : '') . '
          ' . (!empty($service) ? '<div class="field-group"><div class="field-label">Required Service</div><div class="field-value" style="font-weight:bold; color:#1D8FCD;">' . $service . '</div></div>' : '') . '
          ' . (!empty($category) ? '<div class="field-group"><div class="field-label">Project Category</div><div class="field-value">' . $category . '</div></div>' : '') . '
          ' . (!empty($location) ? '<div class="field-group"><div class="field-label">Project Location</div><div class="field-value">' . $location . '</div></div>' : '') . '
          ' . (!empty($message) ? '<div class="field-group"><div class="field-label">Project Details / Message</div><div class="field-value" style="white-space:pre-wrap;">' . $message . '</div></div>' : '') . '
        </div>
        <div class="footer">
          This message was sent directly from racsprojects.com form submission engine.
        </div>
      </div>
    </body>
    </html>';
}

// Redirect to thank you page
header('Location: thankyou.html');
exit;
