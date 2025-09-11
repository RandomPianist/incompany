<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <meta name = "csrf-token" content = "{{ csrf_token() }}" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('storage/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('storage/favicon.ico') }}" type = "image/x-icon" />
        <link href = "{{ asset('css/geral/rel.css') }}" rel = "stylesheet" />
        <link href = "{{ asset('css/lib/fa.css')    }}" rel = "stylesheet" />
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
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/jquery.min.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/mascaras.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/rel.js')      }}"></script>
    </body>
</html>