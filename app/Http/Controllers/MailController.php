<?php

namespace App\Http\Controllers;
//namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TestMail;
use App\Mail\WelcomeMail;


class MailController extends Controller
{
        /**
     * Send an invitation email.
     *
    * @param Request $request
    * @return \Illuminate\Http\JsonResponse
    */
   public function sendInvitation(Request $request)
   {
       // Validate the request data
       $validated = $request->validate([
           'email' => 'required|email',
           'name' => 'required|string',
       ]);

       // Prepare email details
       $details = [
           'name' => $validated['name'],
       ];

       try {
           // Send the email
           Mail::to($validated['email'])->send(new InvitationMail($details));
           
           // Return a success response
           return response()->json(['message' => 'Email sent successfully.'], 200);
       } catch (\Exception $e) {
           // Log the error for debugging
           Log::error('Failed to send invitation email', ['error' => $e->getMessage()]);
           
           // Return an error response
           return response()->json(['message' => 'Failed to send email.'], 500);
       }
   }

   public function sendWelcomeMail(Request $request)
   {
       $request->validate([
           'name' => 'required|string',
           'email' => 'required|email',
       ]);

       $name = $request->input('name');
       $email = $request->input('email');

       // Send the email
       Mail::to($email)->send(new WelcomeMail($name));

       return response()->json(['message' => 'Welcome email sent successfully']);
   }
}
