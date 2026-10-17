<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\CuttingAudit;
use App\Models\CuttingChecklistTemplate;
use App\Models\CuttingCheckpoint;
use App\Models\CuttingCheckpointResult;
use App\Models\CuttingStage;
use App\Models\CuttingTable;
use App\Models\Style;
use App\Models\User;
use App\Services\CuttingNumberService;
use App\Services\CuttingWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CuttingQmsDummySeeder extends Seeder
{
    protected CuttingWorkflowService $workflow;

    protected User $admin;

    protected User $manager;

    protected User $qc;

    protected User $incharge;

    protected User $qam;

    protected User $management;

    protected User $merchandiser;

    protected $buyers;

    protected $styles;

    protected $tables;

    protected array $summary = [];

    public function run(): void
    {
        $this->workflow = app(CuttingWorkflowService::class);

        $this->call(RoleSeeder::class);
        $this->call(CuttingQmsSeeder::class);
        $this->call(AdminUserSeeder::class);

        if (Buyer::count() === 0) {
            $this->call(DemoDataSeeder::class);
        }

        $this->ensureUsers();
        $this->assignMerchandiserBuyers();
        $this->flagCheckpoints();
        $this->resetCuttingData();

        $this->buyers = Buyer::orderBy('id')->get();
        $this->styles = Style::orderBy('id')->get();
        $this->tables = CuttingTable::orderBy('id')->get();

        $this->scenarioDraft();
        $this->scenarioLayAwaitingIncharge();
        $this->scenarioLayApprovedCutPanelDraft();
        $this->scenarioCutPanelAwaitingIncharge();
        $this->scenarioCutPanelApprovedBundlingDraft();
        $this->scenarioBundlingAwaitingQam();
        $this->scenarioReadyToRelease();
        $this->scenarioClosedReleased();
        $this->scenarioLayNotApprovedWithRevision();
        $this->scenarioSpecialPendingLay();
        $this->scenarioSpecialApprovedCutPanel();
        $this->scenarioSpecialRejected();
        $this->scenarioBlockingAndMandatoryRemarks();
        $this->scenarioOutOfTolerance();
        $this->scenarioMerchandiserUnassignedBuyer();

        $this->spreadTimestamps();
        $this->report();
    }

    protected function ensureUsers(): void
    {
        $this->admin = User::where('email', 'admin@fabricsourcing.in')->firstOrFail();
        $this->manager = User::where('email', 'manager@fabricsourcing.in')->firstOrFail();

        $definitions = [
            'qc' => ['Cutting QC Officer', 'qc@fabricsourcing.in', 'Qc@12345', 'cutting_qc'],
            'incharge' => ['Cutting Incharge', 'incharge@fabricsourcing.in', 'Incharge@12345', 'cutting_incharge'],
            'qam' => ['Quality Assurance Manager', 'qam@fabricsourcing.in', 'Qam@12345', 'qam'],
            'management' => ['Management (View Only)', 'management@fabricsourcing.in', 'Management@12345', 'management'],
            'merchandiser' => ['Merchandiser', 'merch@fabricsourcing.in', 'Merch@12345', 'merchandiser'],
        ];

        foreach ($definitions as $property => [$name, $email, $password, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$role]);
            $this->{$property} = $user;
        }
    }

    protected function assignMerchandiserBuyers(): void
    {
        $this->merchandiser->assignedBuyers()->sync(
            Buyer::orderBy('id')->limit(1)->pluck('id')->all()
        );
    }

    protected function flagCheckpoints(): void
    {
        CuttingCheckpoint::where('name', 'Tension')
            ->update(['mandatory_remarks_on_not_ok' => true]);

        CuttingCheckpoint::where('name', 'Stripe/check matching')
            ->update(['blocks_approval' => true]);
    }

    protected function resetCuttingData(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'cutting_checkpoint_photos',
            'cutting_checkpoint_results',
            'cutting_size_patterns',
            'cutting_bundles',
            'cutting_signoffs',
            'cutting_special_approvals',
            'cutting_stages',
            'cutting_audits',
        ] as $table) {
            DB::table($table)->delete();
        }

        Schema::enableForeignKeyConstraints();
    }

    protected function scenarioDraft(): void
    {
        $audit = $this->createAudit($this->header(0, 0, 'PO-9001', 'LOT-DRAFT-01', 'A', now()->toDateString()));

        $payload = $this->stagePayload('lay', ['Leaning'], [
            'Leaning' => 'Slight leaning near the right selvedge; will re-check before submit.',
        ], 3);
        $payload['comments'] = 'Draft in progress - first checkpoints reviewed.';

        $this->workflow->saveStage($audit, 'lay', $payload, $this->qc, false);

        $this->record('Draft (unsaved lay stage)', $audit);
    }

    protected function scenarioLayAwaitingIncharge(): void
    {
        $audit = $this->createAudit($this->header(0, 1, 'PO-9002', 'LOT-LAY-02', 'B', now()->subDay()->toDateString()));

        $this->workflow->saveStage($audit, 'lay', array_merge(
            $this->stagePayload('lay'),
            ['result' => 'approved', 'comments' => 'Lay inspection passed; awaiting incharge sign-off.']
        ), $this->qc, true);

        $this->record('Lay submitted - awaiting Cutting Incharge', $audit);
    }

    protected function scenarioLayApprovedCutPanelDraft(): void
    {
        $audit = $this->createAudit($this->header(1, 0, 'PO-9003', 'LOT-CUT-03', 'A', now()->subDays(2)->toDateString()));

        $this->approveStage($audit, 'lay');

        $draft = $this->stagePayload('cut_panel', [], [], 2);
        $draft['comments'] = 'Cut panel inspection started; notches partially verified.';
        $this->workflow->saveStage($audit, 'cut_panel', $draft, $this->qc, false);

        $this->record('Lay approved - Cut panel draft', $audit);
    }

    protected function scenarioCutPanelAwaitingIncharge(): void
    {
        $audit = $this->createAudit($this->header(1, 1, 'PO-9004', 'LOT-CUT-04', 'C', now()->subDays(3)->toDateString()));

        $this->approveStage($audit, 'lay');

        $this->workflow->saveStage($audit, 'cut_panel', array_merge(
            $this->stagePayload('cut_panel'),
            [
                'result' => 'approved',
                'comments' => 'Panels checked against marker; awaiting incharge sign-off.',
                'size_patterns' => $this->sizePatterns(true),
            ]
        ), $this->qc, true);

        $this->record('Cut panel submitted - awaiting Cutting Incharge', $audit);
    }

    protected function scenarioCutPanelApprovedBundlingDraft(): void
    {
        $audit = $this->createAudit($this->header(2, 2, 'PO-9005', 'LOT-BUN-05', 'A', now()->subDays(4)->toDateString()));

        $this->approveStage($audit, 'lay');
        $this->approveStage($audit, 'cut_panel', ['size_patterns' => $this->sizePatterns(true)]);

        $draft = $this->stagePayload('bundling', [], [], 4);
        $draft['comments'] = 'Bundling started; bundle cards being prepared.';
        $draft['bundles'] = [
            ['bundle_no' => 'B-5001', 'size' => 'S', 'panel_numbers' => '1-20', 'pieces' => 20, 'remarks' => null],
            ['bundle_no' => 'B-5002', 'size' => 'M', 'panel_numbers' => '21-40', 'pieces' => 20, 'remarks' => null],
        ];
        $this->workflow->saveStage($audit, 'bundling', $draft, $this->qc, false);

        $this->record('Cut panel approved - Bundling draft', $audit);
    }

    protected function scenarioBundlingAwaitingQam(): void
    {
        $audit = $this->createAudit($this->header(2, 3, 'PO-9006', 'LOT-BUN-06', 'B', now()->subDays(5)->toDateString()));

        $this->approveStage($audit, 'lay');
        $this->approveStage($audit, 'cut_panel', ['size_patterns' => $this->sizePatterns(true)]);

        $this->workflow->saveStage($audit, 'bundling', array_merge(
            $this->stagePayload('bundling'),
            [
                'result' => 'approved',
                'comments' => 'Bundles verified; awaiting QAM sign-off.',
                'bundles' => $this->bundles(),
            ]
        ), $this->qc, true);

        $this->workflow->signOff($audit, 'bundling', $this->incharge);

        $this->record('Bundling submitted - awaiting QAM', $audit);
    }

    protected function scenarioReadyToRelease(): void
    {
        $audit = $this->createAudit($this->header(0, 4, 'PO-9007', 'LOT-REL-07', 'A', now()->subDays(6)->toDateString()));

        $this->approveStage($audit, 'lay');
        $this->approveStage($audit, 'cut_panel', ['size_patterns' => $this->sizePatterns(true)]);
        $this->approveStage($audit, 'bundling', ['bundles' => $this->bundles()]);

        $this->record('All stages approved - ready to release bundles', $audit);
    }

    protected function scenarioClosedReleased(): void
    {
        $audit = $this->createAudit($this->header(1, 4, 'PO-9008', 'LOT-CLS-08', 'C', now()->subDays(8)->toDateString()));

        $this->approveStage($audit, 'lay');
        $this->approveStage($audit, 'cut_panel', ['size_patterns' => $this->sizePatterns(true)]);
        $this->approveStage($audit, 'bundling', ['bundles' => $this->bundles()]);

        $this->workflow->releaseBundles($audit, $this->incharge);

        $this->record('Bundles released - audit closed', $audit);
    }

    protected function scenarioLayNotApprovedWithRevision(): void
    {
        $audit = $this->createAudit($this->header(2, 1, 'PO-9009', 'LOT-REV-09', 'B', now()->subDays(9)->toDateString()));

        $this->workflow->saveStage($audit, 'lay', array_merge(
            $this->stagePayload('lay', ['Parts marking', 'Lay ends'], [
                'Parts marking' => 'Parts marking offset by 4mm on the left side.',
                'Lay ends' => 'Lay ends uneven; fabric not flush at both ends.',
            ]),
            ['result' => 'not_approved', 'comments' => 'Lay rejected. Rework and re-lay required.']
        ), $this->qc, true);

        $revision = $this->workflow->requestRevision(
            $audit,
            'lay',
            $this->qc,
            'Rework and re-lay required before reinspection.'
        );

        $this->record('Lay NOT approved - revision created', $audit, 'Parent of revision '.$revision->audit_no);
        $this->record('Revision 2 reinspection (draft)', $revision);
    }

    protected function scenarioSpecialPendingLay(): void
    {
        $audit = $this->createAudit($this->header(0, 2, 'PO-9010', 'LOT-SPC-10', 'A', now()->subDays(10)->toDateString()));

        $this->workflow->saveStage($audit, 'lay', array_merge(
            $this->stagePayload('lay', ['Stripe/check matching'], [
                'Stripe/check matching' => 'Stripe matching off by half a stripe at the fold.',
            ]),
            [
                'result' => 'special_approval',
                'special_reason' => 'Minor stripe mismatch within buyer-approved tolerance; request cut on special approval.',
                'comments' => 'Special approval requested from QAM.',
            ]
        ), $this->qc, true);

        $this->record('Lay special approval PENDING (awaiting QAM)', $audit);
    }

    protected function scenarioSpecialApprovedCutPanel(): void
    {
        $audit = $this->createAudit($this->header(1, 3, 'PO-9011', 'LOT-SPC-11', 'C', now()->subDays(12)->toDateString()));

        $this->approveStage($audit, 'lay');

        $this->workflow->saveStage($audit, 'cut_panel', array_merge(
            $this->stagePayload('cut_panel', ['Panel replacement'], [
                'Panel replacement' => 'One panel replaced after a cutting defect.',
            ]),
            [
                'result' => 'special_approval',
                'special_reason' => 'One panel replacement; request special approval to proceed.',
                'size_patterns' => $this->sizePatterns(false),
            ]
        ), $this->qc, true);

        $this->workflow->signOff($audit, 'cut_panel', $this->incharge);

        $approval = $audit->specialApprovals()->where('stage', 'cut_panel')->latest()->firstOrFail();
        $this->workflow->decideSpecialApproval($approval, $this->qam, true, 'Approved - replacement panel acceptable.');

        $this->record('Cut panel special approval APPROVED (advanced to bundling)', $audit);
    }

    protected function scenarioSpecialRejected(): void
    {
        $audit = $this->createAudit($this->header(2, 0, 'PO-9012', 'LOT-SPC-12', 'B', now()->subDays(13)->toDateString()));

        $this->approveStage($audit, 'lay');

        $this->workflow->saveStage($audit, 'cut_panel', array_merge(
            $this->stagePayload('cut_panel', ['Missing notches'], [
                'Missing notches' => 'Notches missing on two panels.',
            ]),
            [
                'result' => 'special_approval',
                'special_reason' => 'Request to bypass missing notch check.',
                'size_patterns' => $this->sizePatterns(false),
            ]
        ), $this->qc, true);

        $approval = $audit->specialApprovals()->where('stage', 'cut_panel')->latest()->firstOrFail();
        $this->workflow->decideSpecialApproval($approval, $this->qam, false, 'Rejected - notches are mandatory.');

        $this->record('Cut panel special approval REJECTED (stage not approved)', $audit);
    }

    protected function scenarioBlockingAndMandatoryRemarks(): void
    {
        $audit = $this->createAudit($this->header(1, 2, 'PO-9013', 'LOT-BLK-13', 'A', now()->subDays(15)->toDateString()));

        $payload = $this->stagePayload('lay', ['Tension', 'Stripe/check matching'], [
            'Tension' => 'Tension inconsistent across the lay; fabric visibly stretched.',
            'Stripe/check matching' => 'Stripe matching off by a full stripe at the fold.',
        ]);
        $payload['result'] = 'not_approved';
        $payload['comments'] = 'Blocking checkpoint failed. Lay cannot be approved.';

        $this->workflow->saveStage($audit, 'lay', $payload, $this->qc, true);

        $stage = $audit->stages()->where('stage', 'lay')->first();
        $result = $stage->results()->where('checkpoint_name', 'Tension')->first();
        if ($result) {
            $this->attachPhoto($result, 'cutting/checkpoints/dummy-tension.png');
        }

        $this->record('Blocking checkpoint + mandatory remarks (not approved)', $audit);
    }

    protected function scenarioOutOfTolerance(): void
    {
        $audit = $this->createAudit($this->header(0, 3, 'PO-9014', 'LOT-TOL-14', 'C', now()->subDays(18)->toDateString(), [
            'required_lay_length' => 10.00,
            'actual_lay_length' => 11.20,
            'required_lay_width' => 160,
            'actual_lay_width' => 150,
            'required_plies' => 50,
            'actual_plies' => 46,
        ]));

        $this->workflow->saveStage($audit, 'lay', array_merge(
            $this->stagePayload('lay'),
            ['result' => 'approved', 'comments' => 'Tolerance flags visible; under review by incharge.']
        ), $this->qc, true);

        $this->record('Out of tolerance + plies mismatch (awaiting incharge)', $audit);
    }

    protected function scenarioMerchandiserUnassignedBuyer(): void
    {
        $index = $this->buyers->count() > 2 ? 2 : 0;
        $audit = $this->createAudit($this->header($index, 4, 'PO-9015', 'LOT-MER-15', 'A', now()->subDays(20)->toDateString()));

        $this->approveStage($audit, 'lay');

        $this->record('Buyer NOT assigned to merchandiser (visibility test)', $audit);
    }

    protected function header(int $buyer, int $table, string $po, string $lot, string $shift, string $date, array $extra = []): array
    {
        $style = $this->styles->get($buyer) ?? $this->styles->first();

        return array_merge([
            'audit_date' => $date,
            'shift' => $shift,
            'buyer_id' => $this->buyers->get($buyer)?->id ?? $this->buyers->first()->id,
            'cutting_table_id' => $this->tables->get($table)?->id ?? $this->tables->first()->id,
            'style_number' => $style?->style_number ?? 'STY-1001',
            'style_name' => 'Fleece Hoodie',
            'po_number' => $po,
            'lot_number' => $lot,
            'fabric' => 'Cotton Fleece',
            'colour' => 'Navy',
            'dia' => '180',
            'lay_number' => 'LAY-'.$po,
            'cut_number' => 'CUT-'.$po,
            'number_of_sizes' => 3,
            'lay_method' => 'manual',
            'marking_type' => 'CAD Marker',
            'required_lay_length' => 12.50,
            'required_lay_width' => 180,
            'actual_lay_length' => 12.45,
            'actual_lay_width' => 180,
            'required_plies' => 60,
            'actual_plies' => 60,
            'actual_lay_height' => 6.20,
            'total_parts' => 180,
        ], $extra);
    }

    protected function createAudit(array $attributes, ?User $creator = null): CuttingAudit
    {
        $audit = CuttingAudit::create(array_merge([
            'audit_no' => app(CuttingNumberService::class)->nextAuditNumber(),
            'status' => 'in_progress',
            'current_stage' => 'lay',
            'revision' => 1,
            'tolerance_pct' => 5,
            'created_by' => ($creator ?? $this->qc)->id,
        ], $attributes));

        $audit->root_id = $audit->id;
        $audit->save();

        return $audit;
    }

    protected function stagePayload(string $stage, array $notOk = [], array $remarks = [], ?int $limit = null): array
    {
        $template = CuttingChecklistTemplate::currentFor($stage);

        $rows = $template->activeCheckpoints->map(function (CuttingCheckpoint $checkpoint) use ($notOk, $remarks) {
            $isNotOk = in_array($checkpoint->name, $notOk, true);

            return [
                'checkpoint_id' => $checkpoint->id,
                'checkpoint_name' => $checkpoint->name,
                'result' => $isNotOk ? 'not_ok' : 'ok',
                'remarks' => $isNotOk
                    ? ($remarks[$checkpoint->name] ?? 'Deviation noted; informed the line supervisor.')
                    : null,
            ];
        })->all();

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        return ['checkpoints' => $rows];
    }

    protected function sizePatterns(bool $pass): array
    {
        return [
            ['size_name' => 'S', 'top_result' => true, 'middle_result' => true, 'bottom_result' => true, 'remarks' => null],
            ['size_name' => 'M', 'top_result' => true, 'middle_result' => $pass, 'bottom_result' => true, 'remarks' => $pass ? null : 'Middle panel off by 2mm.'],
            ['size_name' => 'L', 'top_result' => $pass, 'middle_result' => true, 'bottom_result' => true, 'remarks' => $pass ? null : 'Top panel slightly short.'],
        ];
    }

    protected function bundles(): array
    {
        return [
            ['bundle_no' => 'B-6001', 'size' => 'S', 'panel_numbers' => '1-25', 'pieces' => 25, 'remarks' => null],
            ['bundle_no' => 'B-6002', 'size' => 'M', 'panel_numbers' => '26-50', 'pieces' => 25, 'remarks' => null],
            ['bundle_no' => 'B-6003', 'size' => 'L', 'panel_numbers' => '51-75', 'pieces' => 25, 'remarks' => null],
        ];
    }

    protected function approveStage(CuttingAudit $audit, string $stage, array $extra = []): CuttingStage
    {
        $payload = array_merge($this->stagePayload($stage), $extra, ['result' => 'approved']);

        $this->workflow->saveStage($audit, $stage, $payload, $this->qc, true);
        $this->workflow->signOff($audit, $stage, $this->incharge);

        if (in_array('qam', $this->workflow->requiredSignoffRoles($stage), true)) {
            $this->workflow->signOff($audit, $stage, $this->qam);
        }

        return $audit->stages()->where('stage', $stage)->first();
    }

    protected function attachPhoto(CuttingCheckpointResult $result, string $path): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        Storage::disk('public')->put($path, $png);

        $result->photos()->firstOrCreate(['path' => $path]);
    }

    protected function spreadTimestamps(): void
    {
        $audits = CuttingAudit::with(['stages.signoffs'])->get();

        foreach ($audits as $audit) {
            $base = Carbon::parse($audit->audit_date->toDateString().' 08:00:00');

            DB::table('cutting_audits')->where('id', $audit->id)->update([
                'created_at' => $base->copy()->subHour(),
                'updated_at' => $base->copy()->addHours(6),
            ]);

            foreach ($audit->stages as $stage) {
                $submitted = $base->copy()->addHours(1);
                $columns = [
                    'created_at' => $base->copy()->subMinutes(30),
                    'updated_at' => $base->copy()->addHours(5),
                    'inspection_date' => $base->copy()->addMinutes(20),
                ];

                if ($stage->submitted_at) {
                    $columns['submitted_at'] = $submitted;
                    $columns['locked_at'] = $submitted;
                }

                DB::table('cutting_stages')->where('id', $stage->id)->update($columns);

                foreach ($stage->signoffs as $index => $signoff) {
                    DB::table('cutting_signoffs')->where('id', $signoff->id)->update([
                        'signed_at' => $submitted->copy()->addMinutes(30 * ($index + 1)),
                    ]);
                }
            }
        }
    }

    protected function record(string $scenario, CuttingAudit $audit, string $note = ''): void
    {
        $this->summary[] = [
            $audit->audit_no,
            $scenario,
            $audit->po_number,
            $audit->fresh()->status,
            $audit->fresh()->current_stage,
            $note,
        ];
    }

    protected function report(): void
    {
        $this->command->newLine();
        $this->command->info('Cutting QMS dummy data created. Login password for all cutting users: <Role>@12345');
        $this->command->table(
            ['Audit No', 'Scenario', 'PO Number', 'Status', 'Stage', 'Note'],
            $this->summary
        );
    }
}
