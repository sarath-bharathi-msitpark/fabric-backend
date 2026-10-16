<?php

namespace App\Notifications;

use App\Models\CuttingAudit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CuttingStageReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CuttingAudit $audit,
        public string $stage,
        public string $requiredRole,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Cutting audit {$this->audit->audit_no} ready for your sign-off")
            ->line("Audit {$this->audit->audit_no} is awaiting your sign-off at the {$this->stage} stage.")
            ->action('Open audit', url("/admin/cutting/audits/{$this->audit->id}"))
            ->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cutting_stage_ready',
            'audit_id' => $this->audit->id,
            'audit_no' => $this->audit->audit_no,
            'stage' => $this->stage,
            'required_role' => $this->requiredRole,
            'message' => "Audit {$this->audit->audit_no} is awaiting your sign-off at the {$this->stage} stage.",
            'url' => "/admin/cutting/audits/{$this->audit->id}",
        ];
    }
}
