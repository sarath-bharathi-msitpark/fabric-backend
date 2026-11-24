<?php

namespace App\Notifications;

use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SewingReauditEscalated extends Notification
{
    use Queueable;

    public function __construct(
        public SewingInspectionSheet $sheet,
        public SewingInspectionResult $result,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $operation = $this->result->operation?->operation_name ?? 'Operation';

        return [
            'type' => 'sewing_reaudit_escalated',
            'sheet_id' => $this->sheet->id,
            'sheet_no' => $this->sheet->sheet_no,
            'operation' => $operation,
            'round_no' => $this->result->round_no,
            'defect_count' => $this->result->re_audit_count,
            'message' => "Re-audit still Red on {$this->sheet->sheet_no} — {$operation}, round {$this->result->round_no} ({$this->result->re_audit_count} defects). Escalated to Production Manager.",
        ];
    }
}
