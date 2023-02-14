<?php

require_once "vendor/autoload.php";

use garethp\ews\API;
use garethp\ews\API\Type;
use garethp\ews\API\Enumeration;
use garethp\ews\API\Message;
use garethp\ews\API\Request;
use garethp\ews\MailAPI;
use GuzzleHttp\Psr7\ServerRequest;

function sendEmail(ServerRequest $request) {
    $data = $request->getParsedBody();
    $emailAddress = explode(",", $data['emailAddress']);
    $subject = $data['subject'];
    $body = $data['body'];

// Set connection information.
$host = getenv("HOST");
$username = getenv("EMAIL"); // Use this if you using Cloud Run Environment Variable
$password = getenv("TOKEN"); // Use this if you using Cloud Run Environment Variable

// $username = ''; // Use this if you want to enter username/email manaually
// $password = ''; // Use this if you want to enter password manually

$api = MailAPI::withUsernameAndPassword($host, $username, $password);

// Set the sender.
$recipients = array();
foreach ($emailAddress as $email) {
    $recipient = new Type\EmailAddressType();
    $recipient->EmailAddress = $email;
    $recipients[] = $recipient;
}

// Create the message.
$body = new Type\BodyType();
$message = new Type\MessageType();
$message->setBody("<html><body>" . nl2br($body) . "</body></html>");
$message->setSubject($subject);
$message->setToRecipients($recipients);

// Create our message without sending it
$mailId = $return = $api->sendMail($message, array('MessageDisposition' => 'SaveOnly'));

// Create our Attachments
// $api->getClient()->CreateAttachment(array (
//     'ParentItemId' => $mailId->toArray(),
//     'Attachments' => array (
//         'FileAttachment' => array (
//             'Name' => $_FILES['attachment']['name'],
//             'Content' => file_get_contents($_FILES['attachment']['tmp_name'])
//         )
//     ),
// ));

// We need to fetch the ItemId again. This is because the ItemId contains a "ChangeKey", which is an Id for that specific
// version of the item. Since we've added attachments, the item has changed, so we need to get the new change key before
// we can send the message
$mailId = $api->getItem($mailId)->getItemId();

// Send the message
$response = $api->getClient()->SendItem(array (
    'SaveItemToFolder' => true,
    'ItemIds' => array (
        'ItemId' => $mailId->toArray()
    )
));

if ($response) {
    echo "<script>alert('Email sent successfully.');</script>";
} else {
    echo "<script>alert('Email Failed to send.');</script>";
}
return new JsonResponse(array('result' => 'success'));
}