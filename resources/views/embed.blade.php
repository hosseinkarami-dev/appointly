<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f7f4">
    <title>{{ $tenant->name }} booking</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f8f7f4] antialiased">
    <livewire:public-booking-page :tenant="$tenant" />
    @livewireScripts
</body>
</html>
