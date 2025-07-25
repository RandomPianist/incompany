<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link href = "{{ asset('css/relatorio.css') }}" rel = "stylesheet" />
        <link href = "{{ asset('css/fa.css')        }}" rel = "stylesheet" />
    </head>
    <body>
        <div class = "report">
            @yield("content")
        </div>
        <div id = "btn-print" class = "btn btn-info floating-action-button" onclick = "window.print()">
            <i class = "fa fa-print"></i>
        </div>
        <script type = "text/javascript" language = "JavaScript">
            window.onload = function() {
                Array.from(document.getElementsByClassName("traduzir")).forEach((el) => {
                    el.innerHTML = el.innerHTML
                        .replace("Monday", "Segunda-feira")
                        .replace("Tuesday", "Terça-feira")
                        .replace("Wednesday", "Quarta-feira")
                        .replace("Thursday", "Quinta-feira")
                        .replace("Friday", "Sexta-feira")
                        .replace("Saturday", "Sábado")
                        .replace("Sunday", "Domingo")
                        .replace("January", "janeiro")
                        .replace("February", "fevereiro")
                        .replace("March", "março")
                        .replace("April", "abril")
                        .replace("May", "maio")
                        .replace("June", "junho")
                        .replace("July", "julho")
                        .replace("August", "agosto")
                        .replace("September", "setembro")
                        .replace("October", "outubro")
                        .replace("November", "novembro")
                        .replace("December", "dezembro");
                });
            }
        </script>
    </body>
</html>