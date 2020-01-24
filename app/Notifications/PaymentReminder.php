<?php

namespace App\Notifications;

use App\EmailNotificationSetting;
use App\Invoice;
use App\SlackSetting;
use App\Task;
use App\Traits\SmtpSettings;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class PaymentReminder extends Notification implements ShouldQueue
{
    use Queueable, SmtpSettings;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $invoice;
    private $user;
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->user = User::findOrFail($invoice->project->client_id);
        $this->emailSetting = email_notification_setting();
        $this->setMailConfigs();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['mail'];

        if ($this->emailSetting[7]->send_slack == 'yes') {
            array_push($via, 'slack');
        }

        if ($this->emailSetting[7]->send_push == 'yes') {
            array_push($via, OneSignalChannel::class);
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url('/login');
        $paymentUrl = route('front.invoice', [md5($this->invoice->id)]);

        $content = __('email.paymentReminder.subject') . ' ' . __('app.invoice') . ' ' . ucfirst($this->invoice->invoice_number) . '<p>
            <b style="color: green">' . __('app.dueDate') . ': ' . $this->invoice->due_date->format('d M, Y') . '</b>
        </p>';

        return (new MailMessage)
            ->subject(__('email.paymentReminder.subject') . ' - ' . config('app.name') . '!')
            ->greeting(__('email.hello') . ' ' . ucwords($this->user->name) . '!')
            ->markdown('mail.payment.reminder', ['url' => $url, 'paymentUrl' => $paymentUrl, 'content' => $content]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'id' => $this->invoice->id,
            'created_at' => $this->invoice->created_at->format('Y-m-d H:i:s'),
            'heading' => $this->invoice->invoice_number
        ];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        $slack = SlackSetting::first();
        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->image(asset('storage/slack-logo/' . $slack->slack_logo))
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->content('*' . __('email.paymentReminder.subject') . '*' . "\n" . __('app.invoice') . ' - ' . ucfirst($this->invoice->invoice_number));
        }
        return (new SlackMessage())
            ->from(config('app.name'))
            ->image(asset('storage/slack-logo/' . $slack->slack_logo))
            ->content('This is a redirected notification. Add slack username for *' . ucwords($notifiable->name) . '*');
    }

    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->subject(__('email.paymentReminder.subject'))
            ->body($this->invoice->heading);
    }
}
