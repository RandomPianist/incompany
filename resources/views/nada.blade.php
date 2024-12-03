<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <title>Kx-safe</title>
        <link rel = "icon shortcut" href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link href = "{{ asset('css/my-style.css') }}" rel = "stylesheet" />
    </head>
    <body>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/sweetalert2.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript">
            window.onload = function() {
                Swal.fire({
                    icon : "warning",
                    title : "Aviso",
                    text : "Não há nada para exibir",
                    confirmButtonColor : "rgb(31, 41, 55)"
                }).then(function() {
                    self.close();
                });
            }
        </script>
    </body>
</html>