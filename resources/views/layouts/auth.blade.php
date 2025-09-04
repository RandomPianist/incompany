<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "csrf-token" content = "{{ csrf_token() }}" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "stylesheet" href = "https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" />
        <link rel = "stylesheet" href = "{{ asset('css/lib/app.css')           }}" />
        <link rel = "stylesheet" href = "{{ asset('css/geral/app.css')         }}" />
        <link rel = "stylesheet" href = "{{ asset('css/lib/bootstrap.min.css') }}" />
        <link rel = "stylesheet" href = "{{ asset('css/especifico/login.css')  }}" />
        <link rel = "stylesheet" href = "{{ asset('css/lib/fa.css')            }}" />
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/app.js') }}" defer></script>        
    </head>
    <body class = "body-login row">
        <div class = "secao-esquerda d-flex flex-column justify-content-center align-items-center bg-primary text-white col-4">
            <img src = "{{ asset('img/logo-sm.png') }}" alt = "Logo" class = "mb-5 w-50">
            <h2 class = "text-center mx-2 mb-4" style = "font-size:20pt">Seja bem-vindo(a)</h2>
            <div class = "d-flex justify-content-center flex-wrap">
                <a href = "#" class = "btn">
                    <i class = "fab fa-facebook-f text-white"></i>
                </a>
                <a href = "#" class = "btn mx-2">
                    <i class = "fab fa-linkedin text-white"></i>
                </a>
                <a href = "#" class = "btn">
                    <i class = "fab fa-instagram text-white"></i>
                </a>
            </div>
        </div>
        <div class = "col-8 secao-direita d-flex justify-content-center align-items-center">
            @yield("content")    
        </div>
    </body>
</html>