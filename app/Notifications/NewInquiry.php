<?php
namespace App\Notifications;
use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class NewInquiry extends Notification implements ShouldQueue {
 use Queueable;
 public function __construct(public Inquiry $inquiry){}
 public function via(object $notifiable): array{return ['mail'];}
 public function toMail(object $notifiable): MailMessage {return (new MailMessage)->subject('New Adore Interior consultation inquiry')->line('A new consultation inquiry has arrived.')->line($this->inquiry->name.' · '.$this->inquiry->project_type)->action('Review inquiry',url('/admin/inquiries'));}
}
