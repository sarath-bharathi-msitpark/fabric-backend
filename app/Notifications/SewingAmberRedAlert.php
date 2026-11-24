<?php

namespace App\Notifications;

use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SewingAmberRedAlert extends Notification
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
        $band = ucfirst($this->result->result);

        return [
            'type' => 'sewing_amber_red_alert',
            'sheet_id' => $this->sheet->id,
            'sheet_no' => $this->sheet->sheet_no,
            'operation' => $operation,
            'round_no' => $this->result->round_no,
            'result' => $this->result->result,
            'defect_count' => $this->result->defect_count,
            'message' => "{$band} result on {$this->sheet->sheet_no} — {$operation}, round {$this->result->round_no} ({$this->result->defect_count} defects). Supervisor action needed.",
        ];
    }
}
