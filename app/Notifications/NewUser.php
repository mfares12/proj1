<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\User;

class NewUser extends BaseNotification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $password;
    private $emailSetting;

    public function __construct(User $user, $password)
    {
        $this->password = $password;
        $this->company = $user->company;

        // When there is company of user.
        if ($this->company) {
            $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'user-registrationadded-by-admin')->first();
        }

    }

    /**
     * Get the notification's delivery channels.
     *t('mail::layout')
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['database'];

        if (is_null($this->company)) {
            array_push($via, 'mail');

            return $via;
        }

        if ($this->emailSetting->send_email == 'yes' && ($notifiable->email_notifications == '' || $notifiable->email_notifications) && $notifiable->email != '') {
            array_push($via, 'mail');
        }


        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            $this->slackUserNameCheck($notifiable) ? array_push($via, 'slack') : null;
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $build = parent::build($notifiable);

        $url = route('login');
        $url = getDomainSpecificUrl($url, $this->company);

        // WORKSUITESAAS
        $this->password = $this->password ?: __('superadmin.previousPassword');

        $content = __('email.newUser.text') . '<br><br>' . __('app.email') . ': <b>' . $notifiable->email . '</b><br>' . __('app.password') . ': <b>' . $this->password . '</b>';

        return $build
            ->subject(__('email.newUser.subject') . ' ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company ? $this->company->header_color : null,
                'actionText' => __('email.newUser.action'),
                'notifiableName' => $notifiable->name
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    //phpcs:ignore
    public function toArray($notifiable)
    {
        return $notifiable->toArray();
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {

        try {

            $url = route('login');
            $url = getDomainSpecificUrl($url, $this->company);

            $content = '*' . __('email.newUser.subject') . ' ' . config('app.name') . '!*' . "\n" . __('email.newUser.text');
            $url = "\n" . '<' . $url . '|' . __('email.newUser.action') . '>';

            return $this->slackBuild($notifiable)->content($content . $url);

        } catch (\Exception $e) {
            return $this->slackRedirectMessage('email.newUser.subject', $notifiable);
        }

    }

}
