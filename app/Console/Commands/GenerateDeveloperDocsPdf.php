<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateDeveloperDocsPdf extends Command
{
    protected $signature = 'docs:pdf {--output= : Output file path (defaults to public/developer-help.pdf)}';
    protected $description = 'Generate the developer help PDF documentation';

    public function handle(): int
    {
        $output = $this->option('output') ?: public_path('developer-help.pdf');

        $pdf = Pdf::loadView('docs.developer-help')
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        file_put_contents($output, $pdf->output());

        $this->info("Developer help PDF generated successfully:");
        $this->info("  Path: {$output}");
        $this->info("  Size: " . round(filesize($output) / 1024, 1) . " KB");
        $this->newLine();
        $this->info("Access it at: " . config('app.url') . "/developer-help.pdf");

        return self::SUCCESS;
    }
}
