@extends("layouts.basic")

@section("content")
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
@endsection