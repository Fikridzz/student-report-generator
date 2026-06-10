<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Imports\StudentsImport;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Style\Table;

new class extends Component {
    use WithFileUploads;

    public $title;
    public $teacherName;
    public $teacherRole;
    public $excelFile;
    public $generatedReports = [];

    protected $rules = [
        'title' => 'required|string|min:2|max:255',
        'teacherName' => 'required|string|min:2|max:255',
        'teacherRole' => 'required|string|min:2|max:255',
        'excelFile' => 'required|mimes:xlsx,xls,csv|max:5120',
    ];

    public function generateReports()
    {
        $this->validate();

        $allSheets = Excel::toArray(new StudentsImport(), $this->excelFile->getRealPath());
        $this->generatedReports = [];
        $batchId = now()->format('Ymd_His');

        // Ensure directories exist
        Storage::makeDirectory('reports');

        // Define the path to your .docx template
        $templatePath = storage_path('app/templates/report_template_v2.docx');

        if (!file_exists($templatePath)) {
            session()->flash('error', 'Template Word tidak ditemukan di storage/app/templates/report_template.docx');
            return;
        }

        foreach ($allSheets as $sheetIndex => $studentsData) {
            if (empty($studentsData)) {
                continue;
            }

            // 1. Initialize Word Template Processor
            $template = new TemplateProcessor($templatePath);

            // 2. Map & Set Profile Data
            $template->setValue('name', $studentsData[0][1] ?? 'N/A');
            $template->setValue('class', $studentsData[1][1] ?? 'N/A');
            $template->setValue('student_id', $studentsData[2][1] ?? 'N/A');
            $template->setValue('date', $studentsData[3][1] ?? 'N/A');
            $template->setValue('memorized', $studentsData[4][1] ?? 'N/A');
            $template->setValue('last_surah', $studentsData[5][1] ?? 'N/A');
            $template->setValue('title', $this->title);
            $template->setValue('teacherName', $this->teacherName);
            $template->setValue('teacherRole', $this->teacherRole);
            $template->setValue('dateGenerated', now()->format('d M Y'));

            // 3. Process the 40 Surahs
            $mappedSurahs = [];
            for ($i = 1; $i <= 40; $i++) {
                if (!isset($studentsData[$i])) {
                    break;
                }
                $mappedSurahs[] = [
                    'name' => $studentsData[$i][3] ?? '',
                    'score' => $studentsData[$i][4] ?? '',
                    'keterangan' => $studentsData[$i][5] ?? '',
                ];
            }

            // 4. Clone the table row 20 times dynamically
            // Define Compact Table, Font, and Paragraph Styles
            $tableStyle = [
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 30, // Reduced padding to make rows shorter
                'width' => 100 * 50, // 5000 = 100% Width
                'unit' => 'pct', // Forces table to stretch left-to-right margins
                'alignment' => 'center',
            ];

            $globalFontStyle = [
                'name' => 'Times New Roman',
                'size' => 12, // Reduced from 12 to guarantee 1-page fit
                'bold' => true,
                'color' => '000000',
            ];

            $headerFontStyle = [
                'name' => 'Times New Roman',
                'size' => 12, // Slightly larger for headers
                'bold' => true,
                'color' => 'FFFFFF',
            ];

            $centerParagraph = [
                'alignment' => 'center',
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ];

            // Create the Table
            $table = new \PhpOffice\PhpWord\Element\Table($tableStyle);

            // Define strict column widths to fill the page evenly (Total approx 10000 twips)
            $wNo = 500; // Narrowest column for numbers
            $wNama = 1500; // Wider column for Surah names
            $wNilai = 800; // Small column for scores
            $wKet = 2200; // Medium column for remarks

            // Add the Blue Header Row
            $table->addRow();
            $headerCellStyle = ['bgColor' => '095AB2', 'vAlign' => 'center'];

            $table->addCell($wNo, $headerCellStyle)->addText('No', $headerFontStyle, $centerParagraph);
            $table->addCell($wNama, $headerCellStyle)->addText('Nama Surat', $headerFontStyle, $centerParagraph);
            $table->addCell($wNilai, $headerCellStyle)->addText('Nilai', $headerFontStyle, $centerParagraph);
            $table->addCell($wKet, $headerCellStyle)->addText('Keterangan', $headerFontStyle, $centerParagraph);

            $table->addCell($wNama, $headerCellStyle)->addText('Nama Surat', $headerFontStyle, $centerParagraph);
            $table->addCell($wNilai, $headerCellStyle)->addText('Nilai', $headerFontStyle, $centerParagraph);
            $table->addCell($wKet, $headerCellStyle)->addText('Keterangan', $headerFontStyle, $centerParagraph);

            // Loop 20 times for the data rows
            for ($i = 0; $i < 20; $i++) {
                // Setting exact row height (250 twips) forces it to be as compact as possible
                $table->addRow(250);
                $defaultCellStyle = ['vAlign' => 'center'];

                // --- LEFT SIDE ---
                $leftScore = $mappedSurahs[$i]['score'] ?? '';
                $isLeftFilled = !empty($leftScore) || $leftScore === 0 || $leftScore === '0';
                $leftBg = $isLeftFilled ? ['bgColor' => '82C940', 'vAlign' => 'center'] : $defaultCellStyle;

                $table->addCell($wNo, $defaultCellStyle)->addText($i + 1 . '.', $globalFontStyle, $centerParagraph);
                $table->addCell($wNama, $leftBg)->addText($mappedSurahs[$i]['name'] ?? '', $globalFontStyle, $centerParagraph);
                $table->addCell($wNilai)->addText($leftScore, $globalFontStyle, $centerParagraph);
                $table->addCell($wKet)->addText($mappedSurahs[$i]['keterangan'] ?? '', $globalFontStyle, $centerParagraph);

                // --- RIGHT SIDE ---
                $rightIndex = $i + 20;
                $rightScore = $mappedSurahs[$rightIndex]['score'] ?? '';
                $isRightFilled = !empty($rightScore) || $rightScore === 0 || $rightScore === '0';
                $rightBg = $isRightFilled ? ['bgColor' => '82C940', 'vAlign' => 'center'] : $defaultCellStyle;

                $table->addCell($wNama, $rightBg)->addText($mappedSurahs[$rightIndex]['name'] ?? '', $globalFontStyle, $centerParagraph);
                $table->addCell($wNilai)->addText($rightScore, $globalFontStyle, $centerParagraph);
                $table->addCell($wKet)->addText($mappedSurahs[$rightIndex]['keterangan'] ?? '', $globalFontStyle, $centerParagraph);
            }

            // Inject the styled table into the template replacing ${surah_table}
            $template->setComplexBlock('surah_table', $table);

            // 5. Ensure the absolute target folder exists before saving
            $targetDirectory = storage_path('app/reports');
            if (!file_exists($targetDirectory)) {
                mkdir($targetDirectory, 0755, true);
            }

            // Map a clean file name
            $studentName = str_replace([' ', '/'], '_', $studentsData[0][1] ?? "Sheet_{$sheetIndex}");
            $fileName = "Rapot_Tahfidz_{$studentName}.docx";

            // Save using the verified directory path
            $template->saveAs($targetDirectory . '/' . $fileName);

            $this->generatedReports[] = [
                'title' => 'Laporan - ' . ($studentsData[0][1] ?? "Sheet {$sheetIndex}"),
                'fileName' => $fileName,
            ];
        }

        $this->reset(['title', 'teacherName', 'teacherRole', 'excelFile']);
        $this->dispatch('close-modal');
    }

    public function downloadSingle($fileName)
    {
        $filePath = storage_path('app/reports/' . $fileName);

        if (file_exists($filePath)) {
            return response()->download($filePath);
        }

        session()->flash('error', 'File tidak ditemukan.');
    }

    public function downloadAll()
    {
        if (empty($this->generatedReports)) {
            return;
        }

        $zip = new \ZipArchive();
        $zipFileName = 'All_Reports_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            foreach ($this->generatedReports as $report) {
                $filePath = storage_path('app/reports/' . $report['fileName']);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $report['fileName']);
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}; ?>

<div x-data="{ open: false }" @close-modal.window="open = false" class="w-full max-w-4xl mx-auto">

    <!-- Top Action Area -->
    <div class="flex justify-center mb-10">
        <button @click="open = true"
            class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300 ease-in-out">
            Open Report Generator
        </button>
    </div>

    <!-- LIST OF GENERATED REPORTS -->
    @if (count($generatedReports) > 0)
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Generated Reports</h3>
                    <p class="text-sm text-gray-500">Your reports are ready to download.</p>
                </div>

                <!-- Download All Button -->
                <button wire:click="downloadAll"
                    class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg shadow hover:bg-green-700 transition flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download All (ZIP)
                </button>
            </div>

            <ul class="divide-y divide-gray-200">
                @foreach ($generatedReports as $report)
                    <li class="py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-800 font-medium">{{ $report['title'] }}</span>
                        </div>

                        <!-- Individual Download Button -->
                        <button wire:click="downloadSingle('{{ $report['fileName'] }}')"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                            Download
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Modal Overlay (Remains unchanged except for form UI) -->
    <div x-show="open" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">

        <div @click.away="open = false" class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-8 relative"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">

            <button @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Generate Student Reports</h2>
                <p class="text-gray-500 text-sm mt-1">Upload an Excel file with multiple sheets.</p>
            </div>

            <form wire:submit.prevent="generateReports" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Document Title</label>
                    <input type="text" wire:model="title" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('title')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Teacher's Name</label>
                    <input type="text" wire:model="teacherName" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('teacherName')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Teacher's Role</label>
                    <input type="text" wire:model="teacherRole" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('teacherRole')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Excel Document (.xlsx)</label>
                    <input type="file" wire:model="excelFile" accept=".xlsx, .xls, .csv" required
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md">
                    <div wire:loading wire:target="excelFile" class="text-blue-500 text-xs mt-1">Uploading...</div>
                    @error('excelFile')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" @click="open = false"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">
                        Cancel
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition focus:outline-none disabled:opacity-50 flex items-center">
                        <span wire:loading.remove wire:target="generateReports">Generate Reports</span>
                        <span wire:loading wire:target="generateReports">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // 4. Clone the table row 20 times dynamically
$template->cloneRow('noL', 20);

for ($i = 0; $i < 20; $i++) {
    $rowNum = $i + 1;

    // Left Side
    $template->setValue("noL#{$rowNum}", $rowNum . '.');
    $template->setValue("sL#{$rowNum}", $mappedSurahs[$i]['name'] ?? '');
    $template->setValue("nL#{$rowNum}", $mappedSurahs[$i]['score'] ?? '');
    $template->setValue("kL#{$rowNum}", $mappedSurahs[$i]['keterangan'] ?? '');

    // Right Side
    $rightIndex = $i + 20;
    $template->setValue("sR#{$rowNum}", $mappedSurahs[$rightIndex]['name'] ?? '');
    $template->setValue("nR#{$rowNum}", $mappedSurahs[$rightIndex]['score'] ?? '');
    $template->setValue("kR#{$rowNum}", $mappedSurahs[$rightIndex]['keterangan'] ?? '');
} --}}
