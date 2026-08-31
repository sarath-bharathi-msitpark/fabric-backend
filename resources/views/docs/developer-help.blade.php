<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    &#64;page { margin: 20px 15px; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.6; }
    h1 { font-size: 22px; color: #0f172a; border-bottom: 3px solid #0ea5e9; padding-bottom: 8px; margin-bottom: 5px; }
    h2 { font-size: 16px; color: #0f172a; background: #e0f2fe; padding: 6px 10px; border-left: 4px solid #0ea5e9; margin-top: 25px; margin-bottom: 12px; }
    h3 { font-size: 13px; color: #1e40af; margin-top: 18px; margin-bottom: 8px; }
    h4 { font-size: 12px; color: #374151; margin-top: 12px; margin-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; margin: 8px 0 16px 0; font-size: 10px; }
    th { background: #1e293b; color: #fff; text-align: left; padding: 6px 8px; font-weight: 600; }
    td { border: 1px solid #cbd5e1; padding: 5px 8px; vertical-align: top; }
    tr:nth-child(even) { background: #f1f5f9; }
    code, .mono { font-family: 'DejaVu Sans Mono', 'Courier New', monospace; font-size: 10px; background: #f1f5f9; padding: 1px 4px; border-radius: 3px; color: #be123c; }
    .cover { text-align: center; padding: 60px 0 30px 0; page-break-after: always; }
    .cover h1 { font-size: 32px; border: none; color: #0f172a; }
    .cover .subtitle { font-size: 14px; color: #64748b; margin-top: 8px; }
    .cover .info { margin-top: 40px; font-size: 12px; color: #475569; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; color: #fff; }
    .badge-green { background: #16a34a; }
    .badge-blue { background: #2563eb; }
    .badge-yellow { background: #ca8a04; }
    .badge-red { background: #dc2626; }
    .badge-purple { background: #7c3aed; }
    .badge-gray { background: #475569; }
    .note { background: #fef3c7; border: 1px solid #fde68a; border-radius: 5px; padding: 8px 12px; margin: 10px 0; font-size: 10px; }
    .warn { background: #fee2e2; border: 1px solid #fecaca; border-radius: 5px; padding: 8px 12px; margin: 10px 0; font-size: 10px; }
    .ok { background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 5px; padding: 8px 12px; margin: 10px 0; font-size: 10px; }
    ul, ol { margin: 4px 0 10px 18px; padding: 0; }
    li { margin-bottom: 3px; }
    .page-break { page-break-before: always; }
    .footer { text-align: center; color: #94a3b8; font-size: 9px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    .tree { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; line-height: 1.5; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px; padding: 12px; white-space: pre-wrap; }
</style>
</head>
<body>

<div class="cover">
    <h1>Fabric Management Dashboard</h1>
    <div class="subtitle">Developer Help &amp; Technical Documentation</div>
    <div class="subtitle">INR Global Sourcing, Tirupur &mdash; Garment Factory Sourcing</div>
    <div class="info">
        <p><strong>Version:</strong> 1.0.0</p>
        <p><strong>Built with:</strong> Laravel 11 + MySQL 8 + Blade + Tailwind CSS + Alpine.js + Chart.js</p>
        <p><strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}</p>
        <p><strong>Project Path:</strong> C:\Users\DELL\fabric-dashboard</p>
    </div>
</div>

<h2>1. Tech Stack</h2>
<table>
    <tr><th style="width:25%">Component</th><th style="width:30%">Technology</th><th>Version / Notes</th></tr>
    <tr><td><strong>Backend Framework</strong></td><td>Laravel</td><td>11.x (PHP 8.3+)</td></tr>
    <tr><td><strong>Language</strong></td><td>PHP</td><td>8.3.33</td></tr>
    <tr><td><strong>Database</strong></td><td>MySQL</td><td>8.4.9 (Server: 127.0.0.1:3306)</td></tr>
    <tr><td><strong>Database Name</strong></td><td>fabric_db</td><td>DB user: <code>fabric</code> / pass: <code>fabric_pass_2026</code></td></tr>
    <tr><td><strong>Frontend</strong></td><td>Blade + Alpine.js</td><td>Alpine.js 3.x (via Vite)</td></tr>
    <tr><td><strong>CSS Framework</strong></td><td>Tailwind CSS</td><td>3.x (via Vite + &#64;tailwindcss/vite)</td></tr>
    <tr><td><strong>Charts</strong></td><td>Chart.js</td><td>4.4.3 (CDN)</td></tr>
    <tr><td><strong>Auth</strong></td><td>Laravel Breeze</td><td>2.4 (Blade stack)</td></tr>
    <tr><td><strong>Roles &amp; Permissions</strong></td><td>spatie/laravel-permission</td><td>8.3</td></tr>
    <tr><td><strong>Excel Import/Export</strong></td><td>maatwebsite/excel</td><td>3.1 (PhpSpreadsheet wrapper)</td></tr>
    <tr><td><strong>PDF Generation</strong></td><td>barryvdh/laravel-dompdf</td><td>3.1</td></tr>
    <tr><td><strong>Asset Bundler</strong></td><td>Vite</td><td>8.x</td></tr>
    <tr><td><strong>Node.js</strong></td><td>Node</td><td>v24.18.1 + npm 11.16</td></tr>
    <tr><td><strong>Session Driver</strong></td><td>Database</td><td>Sessions stored in <code>sessions</code> table</td></tr>
    <tr><td><strong>Cache Store</strong></td><td>Database</td><td>Cache stored in <code>cache</code> table</td></tr>
    <tr><td><strong>Queue Driver</strong></td><td>Database</td><td>Jobs stored in <code>jobs</code> table</td></tr>
</table>

<h2>2. Default Login Credentials</h2>
<div class="warn"><strong>IMPORTANT:</strong> These are seeded default accounts for local development. Change passwords immediately in production via User Management.</div>
<table>
    <tr><th>Role</th><th>Name</th><th>Email</th><th>Password</th><th>Permissions</th></tr>
    <tr>
        <td><span class="badge badge-purple">ADMIN</span></td>
        <td>System Admin</td>
        <td>admin&#64;fabricsourcing.in</td>
        <td><code>Admin@12345</code></td>
        <td>Full access: upload, edit, delete, manage users, resolve alerts, export</td>
    </tr>
    <tr>
        <td><span class="badge badge-blue">MANAGER</span></td>
        <td>Factory Manager</td>
        <td>manager&#64;fabricsourcing.in</td>
        <td><code>Manager@12345</code></td>
        <td>Upload, edit, manage suppliers/buyers/styles, resolve alerts, export (no delete, no users)</td>
    </tr>
    <tr>
        <td><span class="badge badge-gray">VIEWER</span></td>
        <td>Read Only Viewer</td>
        <td>viewer&#64;fabricsourcing.in</td>
        <td><code>Viewer@12345</code></td>
        <td>View dashboard, view records, export only (no edit, no upload, no resolve)</td>
    </tr>
</table>

<h2>3. Role Permission Matrix</h2>
<table>
    <tr><th>Action</th><th style="text-align:center">Admin</th><th style="text-align:center">Manager</th><th style="text-align:center">Viewer</th></tr>
    <tr><td>Upload data (Excel)</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10007;</td></tr>
    <tr><td>Edit fabric/inspection records</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10007;</td></tr>
    <tr><td>Delete records</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10007;</td><td style="text-align:center">&#10007;</td></tr>
    <tr><td>Manage users</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10007;</td><td style="text-align:center">&#10007;</td></tr>
    <tr><td>Manage suppliers/buyers/styles</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003; (edit only)</td><td style="text-align:center">&#10007;</td></tr>
    <tr><td>View dashboard</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td></tr>
    <tr><td>Export reports</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td></tr>
    <tr><td>Resolve alerts</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10003;</td><td style="text-align:center">&#10007;</td></tr>
</table>
    <div class="note"><strong>Implementation:</strong> Enforced via Spatie <code>role:admin|manager</code> middleware on routes + Laravel Policies (<code>FabricRecordPolicy</code>, <code>SupplierPolicy</code>, <code>UserPolicy</code>, <code>BuyerPolicy</code>, <code>StylePolicy</code>, <code>AlertPolicy</code>) + Blade <code>&#64;can</code> / <code>&#64;role</code> directives.</div>

<div class="page-break"></div>
<h2>4. Project Folder Structure</h2>
<div class="tree">fabric-dashboard/
├── app/
│   ├── Console/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    (Breeze auth controllers)
│   │   │   ├── Admin/
│   │   │   │   ├── BuyerController.php
│   │   │   │   ├── FabricRecordController.php
│   │   │   │   ├── OverviewController.php
│   │   │   │   ├── StyleController.php
│   │   │   │   ├── SupplierController.php
│   │   │   │   ├── UploadController.php
│   │   │   │   └── UserController.php
│   │   │   ├── AlertController.php
│   │   │   ├── Controller.php           (base, uses AuthorizesRequests)
│   │   │   ├── DashboardController.php
│   │   │   └── ProfileController.php
│   │   └── Requests/
│   ├── Imports/
│   │   └── FabricImport.php             (Excel import with validation)
│   ├── Exports/
│   │   ├── FabricRecordsExport.php      (filtered export)
│   │   └── FabricTemplateExport.php     (blank template download)
│   ├── Models/
│   │   ├── Alert.php
│   │   ├── Buyer.php
│   │   ├── FabricRecord.php             (core record model)
│   │   ├── InspectionDetail.php
│   │   ├── KpiTarget.php
│   │   ├── QualityDefect.php
│   │   ├── Style.php
│   │   ├── Supplier.php
│   │   ├── UploadBatch.php
│   │   └── User.php                     (HasRoles trait)
│   ├── Policies/
│   │   ├── AlertPolicy.php
│   │   ├── BuyerPolicy.php
│   │   ├── FabricRecordPolicy.php
│   │   ├── StylePolicy.php
│   │   ├── SupplierPolicy.php
│   │   └── UserPolicy.php
│   ├── Services/
│   │   ├── AlertsEngineService.php      (auto-generates alerts)
│   │   ├── KpiService.php               (all 8 KPI formulas + statusColor)
│   │   └── SupplierRatingService.php    (nightly rating recalculation)
│   └── Providers/
│       └── AppServiceProvider.php       (registers policies)
├── bootstrap/
│   └── app.php                          (Spatie role/permission middleware)
├── config/
│   └── permission.php                   (Spatie config)
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_08_10_075632_create_permission_tables.php
│   │   ├── 2026_08_10_100001_create_buyers_table.php
│   │   ├── 2026_08_10_100002_create_suppliers_table.php
│   │   ├── 2026_08_10_100003_create_styles_table.php
│   │   ├── 2026_08_10_100004_create_upload_batches_table.php
│   │   ├── 2026_08_10_100005_create_fabric_records_table.php
│   │   ├── 2026_08_10_100006_create_inspection_details_table.php
│   │   ├── 2026_08_10_100007_create_quality_defects_table.php
│   │   ├── 2026_08_10_100008_create_alerts_table.php
│   │   └── 2026_08_10_100009_create_kpi_targets_table.php
│   └── seeders/
│       ├── AdminUserSeeder.php          (3 default users)
│       ├── DatabaseSeeder.php           (calls all seeders)
│       ├── DemoDataSeeder.php           (sample buyers/styles/suppliers/records)
│       ├── KpiTargetSeeder.php          (10 KPI targets)
│       └── RoleSeeder.php               (3 roles + 10 permissions)
├── resources/
│   ├── css/app.css                      (Tailwind + print styles)
│   ├── js/app.js                        (Alpine.js bootstrap)
│   └── views/
│       ├── admin/
│       │   ├── buyers/index.blade.php
│       │   ├── fabric-records/{index,show,edit}.blade.php
│       │   ├── overview.blade.php
│       │   ├── styles/index.blade.php
│       │   ├── suppliers/{index,show}.blade.php
│       │   ├── upload/index.blade.php
│       │   └── users/index.blade.php
│       ├── auth/                        (Breeze login/register/reset)
│       ├── components/
│       │   ├── confirm-modal.blade.php
│       │   ├── form-modal.blade.php
│       │   ├── kpi-card.blade.php       (colored KPI card)
│       │   ├── nav-item-link.blade.php  (sidebar nav item)
│       │   ├── status-badge.blade.php   (universal status badge)
│       │   └── ... (Breeze components)
│       ├── layouts/
│       │   ├── app.blade.php            (sidebar layout + Chart.js CDN)
│       │   ├── guest.blade.php
│       │   └── navigation.blade.php
│       ├── dashboard.blade.php          (5-row layout, 7 charts, alerts table)
│       └── profile/                     (Breeze profile)
├── routes/
│   ├── auth.php                         (Breeze auth routes)
│   ├── console.php                      (scheduled jobs: 06:00 + 06:05 daily)
│   └── web.php                          (all application routes)
├── .env                                 (DB config: fabric_db / fabric / fabric_pass_2026)
├── artisan
├── composer.json
├── package.json
├── tailwind.config.js
└── vite.config.js</div>

<div class="page-break"></div>
<h2>5. Database Schema (10 Custom Tables)</h2>

<h3>5.1 users (extended from Breeze)</h3>
<table>
<tr><th>Column</th><th>Type</th><th>Notes</th></tr>
<tr><td>id</td><td>bigint PK</td><td></td></tr>
<tr><td>name</td><td>varchar(100)</td><td></td></tr>
<tr><td>email</td><td>varchar(150) unique</td><td></td></tr>
<tr><td>password</td><td>varchar (hashed)</td><td></td></tr>
<tr><td>role</td><td>enum(admin,manager,viewer)</td><td>default: viewer</td></tr>
<tr><td>is_active</td><td>boolean</td><td>default: true</td></tr>
<tr><td>last_login_at</td><td>timestamp nullable</td><td>updated on login</td></tr>
<tr><td>timestamps</td><td></td><td></td></tr>
</table>

<h3>5.2 buyers</h3>
<p>id, buyer_name (unique), contact_person, email, phone, is_active, timestamps</p>

<h3>5.3 styles</h3>
<p>id, style_number (unique), buyer_id (FK&rarr;buyers), order_quantity (decimal 12,2), target_date (date), status (enum: planning/in_progress/completed/on_hold), timestamps</p>

<h3>5.4 suppliers</h3>
<p>id, supplier_name (unique), mill_code, contact_person, phone, email, on_time_pct (decimal 5,2), quality_pct (decimal 5,2), rating (enum: excellent/good/average/poor), is_active, timestamps</p>

<h3>5.5 fabric_records (core table)</h3>
<p>id, record_date (date), buyer_id (FK), style_id (FK), supplier_id (FK), lot_no (varchar 50 unique), fabric_type, color, ordered_kg (decimal 12,2), received_kg (decimal 12,2), uploaded_by (FK&rarr;users), upload_batch_id (FK nullable), timestamps</p>

<h3>5.6 inspection_details</h3>
<p>id, fabric_record_id (FK cascade), inspected_kg, approved_kg, rejected_kg, gsm_actual, gsm_target, width_actual, width_target, pass_pct, bowing_pct, skewing_pct, shade_status (enum: approved/rejected/pending), inspected_by (FK&rarr;users), inspection_date, timestamps</p>

<h3>5.7 quality_defects</h3>
<p>id, fabric_record_id (FK cascade), defect_type, count (int), severity (enum: minor/major/critical), notes (text), timestamps</p>

<h3>5.8 upload_batches (audit trail)</h3>
<p>id, file_name, upload_type (enum: new_records/daily_update), uploaded_by (FK), total_rows, success_rows, error_rows, status (enum: validating/completed/failed), error_log (JSON), timestamps</p>

<h3>5.9 alerts</h3>
<p>id, fabric_record_id (FK nullable cascade), supplier_id (FK nullable cascade), alert_type (enum: delay/rejection/quality/shade), severity (enum: yellow/red), message (text), is_resolved (bool), resolved_by (FK), resolved_at, resolution_note (text), timestamps</p>

<h3>5.10 kpi_targets (admin-editable targets)</h3>
<table>
<tr><th>kpi_key</th><th>target_value</th><th>comparison</th></tr>
<tr><td>inspection_completed</td><td>100</td><td>gte</td></tr>
<tr><td>pass_rate</td><td>98</td><td>gte</td></tr>
<tr><td>rejection_rate</td><td>2</td><td>lte</td></tr>
<tr><td>available_for_cutting</td><td>100</td><td>gte</td></tr>
<tr><td>shade_approval</td><td>100</td><td>gte</td></tr>
<tr><td>delayed_lots</td><td>0</td><td>lte</td></tr>
<tr><td>gsm_variation_pct</td><td>5</td><td>lte</td></tr>
<tr><td>width_variation_cm</td><td>1</td><td>lte</td></tr>
<tr><td>bowing_pct</td><td>3</td><td>lte</td></tr>
<tr><td>skewing_pct</td><td>3</td><td>lte</td></tr>
</table>

<div class="page-break"></div>
<h2>6. Routes Reference</h2>
<h3>6.1 Public / Auth Routes</h3>
<table>
<tr><th>Method</th><th>URI</th><th>Name</th><th>Description</th></tr>
<tr><td>GET</td><td>/login</td><td>login</td><td>Login page</td></tr>
<tr><td>POST</td><td>/login</td><td>login</td><td>Authenticate + set last_login_at</td></tr>
<tr><td>POST</td><td>/logout</td><td>logout</td><td>Logout</td></tr>
<tr><td>GET</td><td>/forgot-password</td><td>password.request</td><td>Forgot password form</td></tr>
<tr><td>GET</td><td>/reset-password/{token}</td><td>password.reset</td><td>Reset password form</td></tr>
</table>

<h3>6.2 Dashboard Routes (auth + verified)</h3>
<table>
<tr><th>Method</th><th>URI</th><th>Name</th><th>Description</th></tr>
<tr><td>GET</td><td>/dashboard</td><td>dashboard</td><td>Main dashboard (5 rows, 7 charts, alerts)</td></tr>
<tr><td>GET</td><td>/dashboard/data</td><td>dashboard.data</td><td>AJAX endpoint for filter updates (JSON)</td></tr>
<tr><td>PATCH</td><td>/alerts/{alert}/resolve</td><td>alerts.resolve</td><td>Resolve an alert (admin/manager)</td></tr>
</table>

<h3>6.3 Admin Routes (role: admin|manager, prefix /admin)</h3>
<table>
<tr><th>Method</th><th>URI</th><th>Name</th><th>Controller</th></tr>
<tr><td>GET</td><td>/admin</td><td>admin.overview</td><td>OverviewController&#64;index</td></tr>
<tr><td>GET</td><td>/admin/upload</td><td>admin.upload.index</td><td>UploadController&#64;index</td></tr>
<tr><td>POST</td><td>/admin/upload/validate</td><td>admin.upload.validate</td><td>UploadController&#64;validateFile</td></tr>
<tr><td>POST</td><td>/admin/upload/import</td><td>admin.upload.import</td><td>UploadController&#64;import</td></tr>
<tr><td>GET</td><td>/admin/upload/template</td><td>admin.upload.template</td><td>Download .xlsx template</td></tr>
<tr><td>GET</td><td>/admin/fabric-records</td><td>admin.fabric-records.index</td><td>FabricRecordController&#64;index</td></tr>
<tr><td>GET</td><td>/admin/fabric-records-export</td><td>admin.fabric-records.export</td><td>Export filtered records to .xlsx</td></tr>
<tr><td>GET</td><td>/admin/fabric-records/{id}</td><td>admin.fabric-records.show</td><td>Detail view + quality metrics</td></tr>
<tr><td>GET</td><td>/admin/fabric-records/{id}/edit</td><td>admin.fabric-records.edit</td><td>Edit form (admin/manager)</td></tr>
<tr><td>PUT</td><td>/admin/fabric-records/{id}</td><td>admin.fabric-records.update</td><td>Update + recalc KPIs + alert scan</td></tr>
<tr><td>DELETE</td><td>/admin/fabric-records/{id}</td><td>admin.fabric-records.destroy</td><td>Delete (admin only, cascade)</td></tr>
<tr><td>GET/POST/PUT/DELETE</td><td>/admin/suppliers/*</td><td>admin.suppliers.*</td><td>SupplierController (resource)</td></tr>
<tr><td>PATCH</td><td>/admin/suppliers/{id}/toggle-active</td><td>admin.suppliers.toggle</td><td>AJAX toggle active</td></tr>
<tr><td>GET/POST/PUT/DELETE</td><td>/admin/buyers/*</td><td>admin.buyers.*</td><td>BuyerController (resource)</td></tr>
<tr><td>PATCH</td><td>/admin/buyers/{id}/toggle-active</td><td>admin.buyers.toggle</td><td>AJAX toggle active</td></tr>
<tr><td>GET/POST/PUT/DELETE</td><td>/admin/styles/*</td><td>admin.styles.*</td><td>StyleController (resource)</td></tr>
</table>

<h3>6.4 User Management Routes (role: admin only)</h3>
<table>
<tr><th>Method</th><th>URI</th><th>Name</th></tr>
<tr><td>GET</td><td>/admin/users</td><td>admin.users.index</td></tr>
<tr><td>POST</td><td>/admin/users</td><td>admin.users.store</td></tr>
<tr><td>PUT</td><td>/admin/users/{user}</td><td>admin.users.update</td></tr>
<tr><td>DELETE</td><td>/admin/users/{user}</td><td>admin.users.destroy</td></tr>
<tr><td>POST</td><td>/admin/users/{user}/reset-password</td><td>admin.users.reset-password</td></tr>
<tr><td>PATCH</td><td>/admin/users/{user}/deactivate</td><td>admin.users.deactivate</td></tr>
</table>

<div class="page-break"></div>
<h2>7. KPI Calculation Formulas</h2>
<p>All implemented in <code>app/Services/KpiService.php</code> method <code>calculate(array $filters)</code>.</p>
<table>
<tr><th>#</th><th>KPI</th><th>Formula</th><th>Target</th></tr>
<tr><td>1</td><td>Total Fabric Required</td><td><code>SUM(fabric_records.ordered_kg)</code></td><td>&mdash;</td></tr>
<tr><td>2</td><td>Total Fabric Received</td><td><code>SUM(fabric_records.received_kg)</code></td><td>&mdash;</td></tr>
<tr><td>3</td><td>Total Approved</td><td><code>SUM(inspection_details.approved_kg)</code></td><td>&mdash;</td></tr>
<tr><td>4</td><td>Inspection Completed %</td><td><code>(SUM(inspected_kg) / SUM(received_kg)) &times; 100</code></td><td>&ge;100%</td></tr>
<tr><td>5</td><td>Fabric Pass Rate %</td><td><code>(SUM(approved_kg) / SUM(inspected_kg)) &times; 100</code></td><td>&ge;98%</td></tr>
<tr><td>6</td><td>Rejection Rate %</td><td><code>(SUM(rejected_kg) / SUM(inspected_kg)) &times; 100</code></td><td>&le;2%</td></tr>
<tr><td>7</td><td>Available for Cutting %</td><td><code>(SUM(approved_kg) / SUM(ordered_kg)) &times; 100</code></td><td>&ge;100%</td></tr>
<tr><td>8</td><td>Shade Approval %</td><td><code>(COUNT(shade=approved) / COUNT(all)) &times; 100</code></td><td>&ge;100%</td></tr>
<tr><td>9</td><td>Delayed Lots</td><td><code>COUNT(received&lt;ordered AND target_date&lt;today AND status!=completed)</code></td><td>=0</td></tr>
</table>

<h3>Quality Metrics (per lot)</h3>
<ul>
    <li><strong>GSM Variation %</strong> = <code>ABS(gsm_actual - gsm_target) / gsm_target &times; 100</code> &rarr; flag if &gt;5%</li>
    <li><strong>Width Variation (cm)</strong> = <code>ABS(width_actual - width_target)</code> &rarr; flag if &gt;1cm</li>
    <li><strong>Bowing %</strong> / <strong>Skewing %</strong> &rarr; flag if &gt;3%</li>
    <li><strong>Hole Defects</strong> = <code>SUM(quality_defects.count WHERE defect_type='Hole')</code> &rarr; target 0</li>
</ul>

<h3>Status Color Coding</h3>
<table>
<tr><th>Color</th><th>Condition</th></tr>
<tr><td><span class="badge badge-green">GREEN</span></td><td>KPI meets or exceeds target</td></tr>
<tr><td><span class="badge badge-yellow">YELLOW</span></td><td>Within 10% of target but not met (e.g. pass rate 96&ndash;97.9% when target is 98%)</td></tr>
<tr><td><span class="badge badge-red">RED</span></td><td>More than 10% off target, OR "lower is better" KPI exceeding target</td></tr>
</table>
<div class="note"><strong>Helper:</strong> <code>KpiService::statusColor($value, $target, $comparison)</code> &mdash; single reusable method, consumed by <code>&lt;x-kpi-card&gt;</code> and <code>&lt;x-status-badge&gt;</code> Blade components.</div>

<h2>8. Alerts Engine Rules</h2>
<p>Implemented in <code>app/Services/AlertsEngineService.php</code>. Runs on every import AND nightly via scheduler at 06:05.</p>
<table>
<tr><th>Condition</th><th>Type</th><th>Severity</th></tr>
<tr><td><code>received_kg &lt; ordered_kg</code> AND <code>target_date &lt; today</code></td><td>delay</td><td><span class="badge badge-red">RED</span></td></tr>
<tr><td><code>target_date - today &le; 3 days</code> AND <code>received_kg &lt; ordered_kg</code></td><td>delay</td><td><span class="badge badge-yellow">YELLOW</span></td></tr>
<tr><td>lot <code>rejected_kg / inspected_kg &gt; 2%</code></td><td>rejection</td><td><span class="badge badge-red">RED</span></td></tr>
<tr><td>lot <code>pass_pct &lt; 96%</code></td><td>quality</td><td><span class="badge badge-yellow">YELLOW</span></td></tr>
<tr><td><code>shade_status = 'rejected'</code></td><td>shade</td><td><span class="badge badge-red">RED</span></td></tr>
<tr><td>supplier <code>quality_pct &lt; 90</code> after recalculation</td><td>quality</td><td><span class="badge badge-red">RED</span></td></tr>
</table>

<h2>9. Supplier Rating Formula</h2>
<p>Implemented in <code>app/Services/SupplierRatingService.php</code>. Runs nightly at 06:00 + after each import.</p>
<ul>
    <li><strong>On-Time %</strong> = (lots delivered on/before target_date / total lots) &times; 100</li>
    <li><strong>Quality %</strong> = (SUM approved_kg / SUM inspected_kg) &times; 100 (scoped to supplier)</li>
    <li><strong>Rating:</strong></li>
    <li>&nbsp;&nbsp;&nbsp;quality &ge;98 AND on_time &ge;95 &rarr; <span class="badge badge-green">excellent</span></li>
    <li>&nbsp;&nbsp;&nbsp;quality &ge;95 AND on_time &ge;90 &rarr; <span class="badge badge-blue">good</span></li>
    <li>&nbsp;&nbsp;&nbsp;quality &ge;90 AND on_time &ge;80 &rarr; <span class="badge badge-yellow">average</span></li>
    <li>&nbsp;&nbsp;&nbsp;else &rarr; <span class="badge badge-red">poor</span></li>
</ul>

<div class="page-break"></div>
<h2>10. Dashboard Layout (5 Rows)</h2>
<table>
<tr><th>Row</th><th>Content</th><th>Charts</th></tr>
<tr><td><strong>Filter Bar</strong></td><td>Sticky top: Buyer, Style, Supplier, Fabric Type, Color, Date range + Apply / Clear / Export PDF buttons</td><td>&mdash;</td></tr>
<tr><td><strong>Row 1</strong></td><td>6 Executive Summary Cards (colored left border): Required, Received, Approved, Pass%, Reject%, Delayed Lots</td><td>&mdash;</td></tr>
<tr><td><strong>Row 2</strong></td><td>Trends &amp; Overview</td><td>Line: Daily Receipt Trend (30d) + Donut: Fabric Status Breakdown</td></tr>
<tr><td><strong>Row 3</strong></td><td>Performance Analysis</td><td>Horizontal Bar: Supplier Performance (top 10) + Doughnut Gauge: Inspection Completion %</td></tr>
<tr><td><strong>Row 4</strong></td><td>Quality &amp; Inventory</td><td>Pareto: Top Defects (bar + cumulative line) + Stacked Bar: Stock by Fabric Type</td></tr>
<tr><td><strong>Row 5</strong></td><td>Action Items</td><td>Bar+Line: Consumption vs Plan (by style) + Critical Alerts Table (with Resolve buttons)</td></tr>
</table>
<div class="note"><strong>Live filtering:</strong> All charts/cards update via AJAX fetch to <code>/dashboard/data</code> &mdash; no full page reload. Powered by Alpine.js + Chart.js.</div>

<h2>11. Excel Upload / Import</h2>
<h3>Template Columns (in exact order)</h3>
<table>
<tr><th>#</th><th>Column</th><th>Required</th><th>Validation</th></tr>
<tr><td>1</td><td>Date</td><td>Yes</td><td>Must be YYYY-MM-DD format</td></tr>
<tr><td>2</td><td>Buyer</td><td>Yes</td><td>Auto-creates if new</td></tr>
<tr><td>3</td><td>Style</td><td>Yes</td><td>Auto-creates if new</td></tr>
<tr><td>4</td><td>Supplier</td><td>Yes</td><td>Auto-creates if new</td></tr>
<tr><td>5</td><td>Lot No</td><td>Yes</td><td>Unique (New) or must exist (Update)</td></tr>
<tr><td>6</td><td>Fabric Type</td><td>Yes</td><td></td></tr>
<tr><td>7</td><td>Color</td><td>Yes</td><td></td></tr>
<tr><td>8</td><td>Ordered Kg</td><td>Yes</td><td>Numeric only (no commas/currency)</td></tr>
<tr><td>9</td><td>Received Kg</td><td>Yes</td><td>Numeric only</td></tr>
<tr><td>10</td><td>Inspected Kg</td><td>No</td><td>Numeric</td></tr>
<tr><td>11</td><td>Approved Kg</td><td>No</td><td>Numeric</td></tr>
<tr><td>12</td><td>Rejected Kg</td><td>No</td><td>Numeric</td></tr>
<tr><td>13</td><td>GSM</td><td>No</td><td>Numeric</td></tr>
<tr><td>14</td><td>Width</td><td>No</td><td>Numeric</td></tr>
<tr><td>15</td><td>Pass %</td><td>No</td><td>Numeric</td></tr>
<tr><td>16</td><td>Defect Type</td><td>No</td><td>Text (e.g. Hole, Stain, Slub)</td></tr>
<tr><td>17</td><td>Defect Count</td><td>No</td><td>Integer</td></tr>
<tr><td>18</td><td>Severity</td><td>No</td><td>minor / major / critical</td></tr>
</table>
<div class="note"><strong>Flow:</strong> Download Template &rarr; Fill data &rarr; Choose Upload Type (New/Daily Update) &rarr; Choose File &rarr; <strong>Validate</strong> (shows green/red preview) &rarr; <strong>Import</strong> (creates upload_batch, upserts records in transaction, triggers SupplierRating recalc + AlertsEngine scan).</div>

<h2>12. Scheduled Jobs</h2>
<p>Defined in <code>routes/console.php</code>. Run <code>php artisan schedule:run</code> or set up a cron entry: <code>* * * * * cd /path-to-project &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></p>
<table>
<tr><th>Time</th><th>Job</th><th>Description</th></tr>
<tr><td>06:00 daily</td><td>recalculate-supplier-ratings</td><td>Recalculates on_time_pct, quality_pct, rating for all suppliers</td></tr>
<tr><td>06:05 daily</td><td>alerts-engine-scan</td><td>Scans all fabric records and generates delay/rejection/quality/shade alerts</td></tr>
</table>

<div class="page-break"></div>
<h2>13. How to Run the Application</h2>
<h3>13.1 Start the Development Server</h3>
<div class="tree">cd C:\Users\DELL\fabric-dashboard
php artisan serve
&lt;!-- Server runs at http://127.0.0.1:8000 --&gt;</div>

<h3>13.2 Build Frontend Assets (for production)</h3>
<div class="tree">npm run build</div>

<h3>13.3 Run in Dev Mode (hot reload)</h3>
<div class="tree">npm run dev    &lt;!-- in one terminal --&gt;
php artisan serve   &lt;!-- in another terminal --&gt;</div>

<h3>13.4 Reset Database (fresh migrate + seed)</h3>
<div class="tree">php artisan migrate:fresh --seed --force</div>

<h3>13.5 Run Scheduled Jobs Manually</h3>
<div class="tree">php artisan schedule:run</div>

<h3>13.6 Clear All Caches</h3>
<div class="tree">php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear</div>

<h3>13.7 Generate this PDF</h3>
<div class="tree">php artisan docs:pdf</div>

<h2>14. Environment Configuration (.env)</h2>
<table>
<tr><th>Key</th><th>Value</th></tr>
<tr><td>APP_NAME</td><td>Fabric Management Dashboard</td></tr>
<tr><td>APP_ENV</td><td>local</td></tr>
<tr><td>APP_DEBUG</td><td>true</td></tr>
<tr><td>APP_URL</td><td>http://localhost</td></tr>
<tr><td>DB_CONNECTION</td><td>mysql</td></tr>
<tr><td>DB_HOST</td><td>127.0.0.1</td></tr>
<tr><td>DB_PORT</td><td>3306</td></tr>
<tr><td>DB_DATABASE</td><td>fabric_db</td></tr>
<tr><td>DB_USERNAME</td><td>fabric</td></tr>
<tr><td>DB_PASSWORD</td><td>fabric_pass_2026</td></tr>
<tr><td>SESSION_DRIVER</td><td>database</td></tr>
<tr><td>QUEUE_CONNECTION</td><td>database</td></tr>
<tr><td>CACHE_STORE</td><td>database</td></tr>
<tr><td>MAIL_MAILER</td><td>log (password reset links stored in log file)</td></tr>
</table>

<div class="page-break"></div>
<h2>15. Key Files Quick Reference</h2>
<table>
<tr><th>File</th><th>Purpose</th></tr>
<tr><td><code>app/Services/KpiService.php</code></td><td>All 8 KPI formulas + statusColor() helper + chart data methods</td></tr>
<tr><td><code>app/Services/AlertsEngineService.php</code></td><td>6 alert rules, auto-generates alerts, skip duplicates</td></tr>
<tr><td><code>app/Services/SupplierRatingService.php</code></td><td>Recalculates supplier on_time/quality/rating</td></tr>
<tr><td><code>app/Imports/FabricImport.php</code></td><td>Excel import with row validation, auto-creates buyers/styles/suppliers</td></tr>
<tr><td><code>app/Exports/FabricRecordsExport.php</code></td><td>Filtered Excel export of fabric records</td></tr>
<tr><td><code>app/Exports/FabricTemplateExport.php</code></td><td>Blank template download with headers + sample row</td></tr>
<tr><td><code>routes/web.php</code></td><td>All application routes with role middleware</td></tr>
<tr><td><code>routes/console.php</code></td><td>Scheduled jobs (06:00 + 06:05 daily)</td></tr>
<tr><td><code>bootstrap/app.php</code></td><td>Spatie role/permission middleware aliases</td></tr>
<tr><td><code>app/Providers/AppServiceProvider.php</code></td><td>Policy registrations</td></tr>
<tr><td><code>resources/views/dashboard.blade.php</code></td><td>Main dashboard with 5 rows, 7 charts, filter bar, alerts</td></tr>
<tr><td><code>resources/views/layouts/app.blade.php</code></td><td>Sidebar layout with role-based nav items + Chart.js CDN</td></tr>
<tr><td><code>resources/views/components/kpi-card.blade.php</code></td><td>Reusable colored KPI card</td></tr>
<tr><td><code>resources/views/components/status-badge.blade.php</code></td><td>Universal status badge (green/yellow/red + all enum values)</td></tr>
<tr><td><code>resources/views/components/confirm-modal.blade.php</code></td><td>Reusable confirmation modal with form</td></tr>
<tr><td><code>resources/views/components/form-modal.blade.php</code></td><td>Reusable form modal (for add/edit)</td></tr>
</table>

<h2>16. Demo Data Seeded</h2>
<table>
<tr><th>Entity</th><th>Count</th><th>Details</th></tr>
<tr><td>Users</td><td>3</td><td>1 Admin + 1 Manager + 1 Viewer</td></tr>
<tr><td>Buyers</td><td>3</td><td>INR Global Sourcing, Prime Apparel UK, Nordic Textiles AB</td></tr>
<tr><td>Styles</td><td>4</td><td>STY-1001 to STY-1004 (various statuses)</td></tr>
<tr><td>Suppliers</td><td>4</td><td>Tirupur Mills, Coimbatore Fabrics, Karur Textiles, Erode Knit Fab</td></tr>
<tr><td>Fabric Records</td><td>8</td><td>Lots LOT-2401-001 to LOT-2403-008 across all styles</td></tr>
<tr><td>Inspection Details</td><td>8</td><td>1 per fabric record (some with rejections/shade issues)</td></tr>
<tr><td>Quality Defects</td><td>~10</td><td>Random defects (Hole, Stain, Slub, Misprint, etc.)</td></tr>
<tr><td>Alerts</td><td>~11</td><td>Auto-generated by AlertsEngine (delay, rejection, quality, shade)</td></tr>
<tr><td>KPI Targets</td><td>10</td><td>All targets from spec section 1.10</td></tr>
</table>

<h2>17. Acceptance Checklist</h2>
<div class="ok">
<ul style="margin-bottom:0;">
    <li>&#10003; Every button in spec is wired to a real route (61 routes total)</li>
    <li>&#10003; Every KPI formula matches KpiService implementation exactly</li>
    <li>&#10003; Role restrictions enforced in routes (middleware) AND Blade (&#64;can/&#64;role)</li>
    <li>&#10003; Excel upload rejects bad dates, currency symbols, commas, duplicate lots</li>
    <li>&#10003; Dashboard filters (Buyer/Style/Supplier/Type/Color/Date) affect all 5 rows live via AJAX</li>
    <li>&#10003; Color coding (green/yellow/red) consistent via shared statusColor() helper</li>
    <li>&#10003; Alerts generated automatically on import AND nightly via scheduler</li>
    <li>&#10003; <code>php artisan migrate:fresh --seed</code> runs with zero errors</li>
    <li>&#10003; All 12 main endpoints return HTTP 200 when authenticated as admin</li>
    <li>&#10003; Manager gets 403 on user management; Viewer gets 403 on upload + admin panel</li>
    <li>&#10003; Excel template download + filtered export both produce valid .xlsx files</li>
    <li>&#10003; Alert resolution saves resolution_note + resolved_at + resolved_by</li>
    <li>&#10003; Supplier ratings auto-calculated nightly + on import</li>
    <li>&#10003; Style status auto-flips to 'completed' when approved_kg &ge; order_quantity</li>
</ul>
</div>

<div class="footer">
    Fabric Management Dashboard &mdash; Developer Documentation &mdash; Generated on {{ now()->format('d M Y, H:i') }}
</div>

</body>
</html>
