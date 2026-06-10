<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Report Generator</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>

<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center">

    <div class="text-center mb-10">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-4">School Reporting System</h1>
        <p class="text-lg text-gray-600">Quickly generate PDF reports from your grading spreadsheets.</p>
    </div>

    <!-- Inject the Livewire Component (which contains our button and modal) -->
    <livewire:report-generator />

    @livewireScripts
</body>

</html>
