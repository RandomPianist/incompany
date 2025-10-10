<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('storage/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('storage/favicon.ico') }}" type = "image/x-icon" />
        <link href = "{{ asset('css/geral/app.css') }}" rel = "stylesheet" />
    </head>
    <body>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/sweetalert2.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/alerta.js')    }}"></script>
        <script type = "text/javascript" language = "JavaScript">
            async function principal() {
                await s_alert({
                    icon : "{{ $icon }}",
                    html : "{{ $text }}"
                });
                self.close();
            }

            window.onload = function() {
                principal();
            }
        </script>
    </body>
</html>