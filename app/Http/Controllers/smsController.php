<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;

class smsController extends Controller
{
    public function sendSms()
    {
        // Your SMS sending logic goes here
        // Use the Vonage SDK or any other SMS service provider

        // Example using Vonage
        $basic  = new \Vonage\Client\Credentials\Basic('6092baed', '46yyQZXpu3GJGB9P');
        $client = new \Vonage\Client($basic);

// Set the CA bundle path for Guzzle with a relative path
$guzzleClient = new \GuzzleHttp\Client([
    'verify' => storage_path('cacert.pem'),
]);
            $client->setHttpClient($guzzleClient);

        $message = $client->sms()->send(
            new \Vonage\SMS\Message\SMS("639364003963", "trial", 'trial')
        );

        // Return a response
        return response()->json(['message' => 'SMS sent successfully']);
    }



    
    public function sendSms2(){

        $sid = getenv("TWILIO_SID");
        $token = getenv("TWILIO_TOKEN");
        $sendernumber=getenv("TWILIO_PHONE");
        $twilio = new Client($sid, $token);

        $message = $twilio->messages->create(
            "+63 993 582 9400", // to
            //"+63 364 000 3963",
            "+63 936 400 3963",
            [
                "body" =>
                    "sept",
                "from" => $sendernumber,
            ]
        );
        return response()->json(['message' => 'SMS sent successfully']);
    }





}
