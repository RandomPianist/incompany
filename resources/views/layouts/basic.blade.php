<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link href = "{{ asset('css/my-style.css') }}" rel = "stylesheet" />
    </head>
    <body>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/sweetalert2.js') }}"></script>
        @yield("content")
    </body>
</html>