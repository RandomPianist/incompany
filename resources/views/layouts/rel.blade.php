<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <meta name = "csrf-token" content = "{{ csrf_token() }}" />
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
        <div id = "btn-print" class = "btn btn-info floating-action-button" onclick = "window.print()" title = "Imprimir">
            <i class = "fa fa-print"></i>
        </div>
        <div class = "menu-container" id = "menu">
            <div class = "menu-btn" onclick = "this.parentElement.classList.toggle('open')">
                <i class = "fa fa-bars"></i>
            </div>
            <div class = "menu-item" onclick = "window.print()" title = "Imprimir">
                <i class = "fa fa-print"></i>
            </div>
            <div class = "menu-item" onclick = "recalcular()" title = "Recalcular">
                <i class = "fa fa-undo"></i>
            </div>
            <div class = "menu-item" onclick = "solicitar()" title = "Solicitar">
                <i class = "fa fa-cart-arrow-down"></i>
            </div>
        </div>
        <script type = "text/javascript" language = "JavaScript">
            const URL = "{{ config('app.root_url') }}";

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
                if (location.href.indexOf("solicitacoes") > -1) {
                    document.getElementById("btn-print").classList.add("d-none");
                    $.get(URL + "/solicitacoes/meus_comodatos?id_maquina=" + document.getElementById("id_maquina").value, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        document.getElementById("id_comodato").value = data[0];
                    });
                } else document.getElementById("menu").classList.add("d-none");
            }
        </script>
    </body>
</html>