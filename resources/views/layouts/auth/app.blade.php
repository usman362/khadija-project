<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <script src="https://nobleui.com/html/template/assets/js/color-modes.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://nobleui.com/html/template/assets/vendors/core/core.css">
    <link rel="stylesheet" href="https://nobleui.com/html/template/assets/css/demo1/style.css">
    <link rel="shortcut icon" href="https://nobleui.com/html/template/assets/images/favicon.png" />
</head>
<body>
<div class="main-wrapper">
    <div class="page-wrapper full-page">
        <div class="page-content container-xxl d-flex align-items-center justify-content-center">
            <div class="row w-100 mx-0 auth-page">
                @yield('auth_content')
            </div>
        </div>
    </div>
</div>

<script src="https://nobleui.com/html/template/assets/vendors/core/core.js"></script>
<script src="https://nobleui.com/html/template/assets/js/app.js"></script>
@stack('scripts')
</body>
</html>
