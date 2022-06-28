<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = 'service@unitrip.asia';
        $subject = '【UniTrip系統】忘記密碼確認信';
        $name = '樂多科技';
        $encrypted_email = Crypt::encrypt($this->data['email']);
        return $this->view('emails.reset_password_zh')
                    ->from($address, $name)
//                    ->cc($address, $name)
//                    ->bcc($address, $name)
//                    ->replyTo($address, $name)
                    ->subject($subject)
                    ->with([
                        'email' => $this->data['email'],
                        'contact_name' => $this->data['contact_name'],
                        'reset_url' => "https://dev.unitrip.asia/api/mail/reset/".$encrypted_email."/".$this->data['signature'],

                    ]);
    }
}
