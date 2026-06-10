<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Imports\StudentsImport;
use Illuminate\Support\Facades\Storage;
use ArPHP\I18N\Arabic;

new class extends Component {
    use WithFileUploads;

    public $teacherName;
    public $title;
    public $excelFile;

    // New property to hold our generated list
    public $generatedReports = [];

    protected $rules = [
        'teacherName' => 'required|string|min:2|max:255',
        'title' => 'required|string|min:2|max:255',
        'excelFile' => 'required|mimes:xlsx,xls,csv|max:5120',
    ];

    public function generatePdf()
    {
        // $allSheets = Excel::toArray(new StudentsImport(), $this->excelFile->getRealPath());

        // // This will pause execution and print your Excel structure to the screen
        // dd($allSheets[0][0]);

        $this->validate();

        // 1. Parse Excel (Gets ALL sheets as an array of arrays)
        $allSheets = Excel::toArray(new StudentsImport(), $this->excelFile->getRealPath());

        $this->generatedReports = []; // Reset the list for a new upload
        $batchId = now()->format('Ymd_His'); // Unique ID for this generation batch

        // 2. Loop through every sheet
        foreach ($allSheets as $sheetIndex => $studentsData) {
            if (empty($studentsData)) {
                continue;
            }

            // Extracting fields using exact row and column coordinates
            $mappedStudent = [
                'name' => $studentsData[0][1] ?? 'N/A', // Row 1, Column B
                'class' => $studentsData[1][1] ?? 'N/A', // Row 2, Column B
                'student_id' => $studentsData[2][1] ?? 'N/A', // Row 3, Column B
                'date' => $studentsData[3][1] ?? 'N/A', // Row 4, Column B

                // Bonus fields found in your screenshot:
                'memorized' => $studentsData[4][1] ?? 'N/A', // Row 5, Column B (Telah Menghafal)
                'last_surah' => $studentsData[5][1] ?? 'N/A', // Row 6, Column B (Capaian Surat Terakhir)
            ];

            // 2. Map the 38 Surah rows (Starting from Row 7 / Index 6)
            $mappedSurahs = [];

            // We run a loop 38 times to grab rows 7 through 44
            for ($i = 1; $i <= 40; $i++) {
                // Prevent errors if the row happens to be empty
                if (!isset($studentsData[$i])) {
                    break;
                }
                $mappedSurahs[] = [
                    'name' => $studentsData[$i][3] ?? '',
                    'score' => $studentsData[$i][4] ?? '',
                    'keterangan' => $studentsData[$i][5] ?? '',
                ];
            }

            // Generate PDF for this specific student profile sheet
            $pdf = Pdf::loadView('student-report', [
                'teacherName' => $this->teacherName,
                'title' => $this->title,
                'student' => $mappedStudent,
                'surahs' => $mappedSurahs,
                'dateGenerated' => now()->format('d M Y'),
            ]);

            $sheetNumber = $sheetIndex + 1;
            // We can now use the student's actual name for the saved file name!
            $safeName = str_replace(' ', '_', $mappedStudent['name']);
            $fileName = "Report_{$safeName}_Sheet_{$sheetNumber}_{$batchId}.pdf";

            Storage::put('reports/' . $fileName, $pdf->output());

            $this->generatedReports[] = [
                'title' => 'Report Card - ' . $mappedStudent['name'],
                'fileName' => $fileName,
            ];
        }

        // Reset the form
        $this->reset(['teacherName', 'title', 'excelFile']);

        // Close the modal using Alpine
        $this->dispatch('close-modal');
    }

    // Method to download a single PDF
    public function downloadSingle($fileName)
    {
        if (Storage::exists('reports/' . $fileName)) {
            return Storage::download('reports/' . $fileName);
        }
    }

    // Method to Zip all PDFs and download them at once
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
                    // Add file to zip
                    $zip->addFile($filePath, $report['fileName']);
                }
            }
            $zip->close();
        }

        // Download the zip and delete it from the server after sending
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

            <form wire:submit.prevent="generatePdf" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Teacher's Name</label>
                    <input type="text" wire:model="teacherName" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('teacherName')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Title Document</label>
                    <input type="text" wire:model="title" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('title')
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
                        <span wire:loading.remove wire:target="generatePdf">Generate Reports</span>
                        <span wire:loading wire:target="generatePdf">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
