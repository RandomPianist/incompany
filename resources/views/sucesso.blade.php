@extends("layouts.basic")

@section("content")
    <script type = "text/javascript" language = "JavaScript">
        window.onload = function() {
            Swal.fire({
                icon : "success",
                title : "Sucesso",
                text : "Solicitação realizada",
                confirmButtonColor : "rgb(31, 41, 55)"
            }).then(function() {
                self.close();
            });
        }
    </script>
@endsection