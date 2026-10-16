<?php

namespace App\Notifications;

use App\Models\CuttingAudit;
use App\Models\CuttingSpecialApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CuttingSpecialApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(
        public CuttingAudit $audit,
        public CuttingSpecialApproval $approval,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Special approval requested for cutting audit {$this->audit->audit_no}")
            ->line("A special approval has been requested for audit {$this->audit->audit_no} at the {$this->approval->stage} stage.")
            ->line("Reason: {$this->approval->reason}")
            ->action('Review request', url("/admin/cutting/audits/{$this->audit->id}"))
            ->line('Please review and approve or reject.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cutting_special_approval',
            'audit_id' => $this->audit->id,
            'audit_no' => $this->audit->audit_no,
            'approval_id' => $this->approval->id,
            'stage' => $this->approval->stage,
            'message' => "Special approval requested for audit {$this->audit->audit_no} at the {$this->approval->stage} stage.",
            'url' => "/admin/cutting/audits/{$this->audit->id}",
        ];
    }
}
