<?php
/**
 * RACS Projects - Lead Handler & Email Sender
 * Delivers full lead details directly to projects.racs@gmail.com
 */

// Target email recipient
$to = 'projects.racs@gmail.com';

// Prevent direct GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Helper function to sanitize user inputs
function sanitize_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

// Retrieve & sanitize input fields
$fullName = '';
if (isset($_POST['Full_Name'])) $fullName = sanitize_input($_POST['Full_Name']);
elseif (isset($_POST['name'])) $fullName = sanitize_input($_POST['name']);
elseif (isset($_POST['fullName'])) $fullName = sanitize_input($_POST['fullName']);
else {
    foreach ($_POST as $key => $val) {
        if (strpos(strtolower($key), 'name') !== false) {
            $fullName = sanitize_input($val);
            break;
        }
    }
}

$email = isset($_POST['Email']) ? sanitize_input($_POST['Email']) : (isset($_POST['email']) ? sanitize_input($_POST['email']) : '');
$phone = isset($_POST['Phone']) ? sanitize_input($_POST['Phone']) : (isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '');

$service = '';
if (isset($_POST['Required_Service'])) $service = sanitize_input($_POST['Required_Service']);
elseif (isset($_POST['Service_Required'])) $service = sanitize_input($_POST['Service_Required']);
elseif (isset($_POST['service'])) $service = sanitize_input($_POST['service']);
else {
    foreach ($_POST as $key => $val) {
        if (strpos(strtolower($key), 'service') !== false) {
            $service = sanitize_input($val);
            break;
        }
    }
}

$company  = isset($_POST['Company_Name']) ? sanitize_input($_POST['Company_Name']) : '';
$category = isset($_POST['Project_Category']) ? sanitize_input($_POST['Project_Category']) : '';
$location = isset($_POST['Project_Location']) ? sanitize_input($_POST['Project_Location']) : '';

$message = '';
if (isset($_POST['Message'])) $message = sanitize_input($_POST['Message']);
elseif (isset($_POST['Project_Details'])) $message = sanitize_input($_POST['Project_Details']);
elseif (isset($_POST['message'])) $message = sanitize_input($_POST['message']);

$subject = "New MEP Lead Enquiry - " . (!empty($fullName) ? $fullName : "RACS Projects Website");

// -------------------------------------------------------------
// Method 1: High-Deliverability cURL Relay to projects.racs@gmail.com
// -------------------------------------------------------------
if (function_exists('curl_init')) {
    $postPayload = $_POST;
    $postPayload['_subject'] = $subject;
    $postPayload['_captcha'] = 'false';
    $postPayload['_template'] = 'table';

    $ch = curl_init('https://formsubmit.co/ajax/' . $to);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postPayload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    @curl_exec($ch);
    @curl_close($ch);
}

// -------------------------------------------------------------
// Method 2: Native PHP mail() with Valid Server Domain Headers
// -------------------------------------------------------------
$fromEmail = 'contact@racsprojects.in';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: RACS Projects Leads <" . $fromEmail . ">\r\n";
if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $headers .= "Reply-To: " . $fullName . " <" . $email . ">\r\n";
}

$emailBody = build_email_body($fullName, $email, $phone, $service, $company, $category, $location, $message);
@mail($to, $subject, $emailBody, $headers);

// Helper function to build HTML email layout
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
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'redirect' => 'thankyou.html']);
    exit;
} else {
    header('Location: thankyou.html');
    exit;
}
