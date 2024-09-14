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
           $validated = $request->validate([
               'email' => 'required|email',
               'name' => 'required|string',
           ]);
   
           $details = ['name' => $validated['name']];
   
           try {
               Mail::to($validated['email'])->send(new InvitationMail($details));
   
               return response()->json(['message' => 'Invitation email sent successfully'], 200);
           } catch (\Exception $e) {
               Log::error('Failed to send invitation email', ['error' => $e->getMessage()]);
   
               return response()->json(['message' => 'Failed to send email.'], 500);
           }
       }
   
       /**
        * Send invitation by email.
        */
       public function inviteStudentByEmail(Request $request)
       {
           $validated = $request->validate([
               'email' => 'required|email',
               'name' => 'required|string',
           ]);
   
           $details = [
               'name' => $validated['name'],
           ];
   
           try {
               Mail::to($validated['email'])->send(new InvitationMail($details));
   
               return response()->json(['message' => 'Invitation email sent successfully']);
           } catch (\Exception $e) {
               Log::error('Failed to send invitation email', ['error' => $e->getMessage()]);
   
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
