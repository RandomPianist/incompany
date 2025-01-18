@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Locação</h6>
                </li>
                <li class = "text-right">
                    <h6 class = "m-0 traduzir">
                        @php
                            date_default_timezone_set("America/Sao_Paulo");
                            echo ucfirst(strftime("%A, %d de %B de %Y"));
                        @endphp
                    </h6>
                </li>
            </ul>
        </div>
    </div>
    <div class = "mt-2 mb-3 linha"></div>
    <table class = "report-body table table-sm table-bordered table-striped px-5">
        <thead>
            <tr class = "report-row">
                <td width = "35%">Máquina</td>
                <td width = "35%">Empresa</td>
                <td width = "15%" class = "text-center">Início</td>
                <td width = "15%" class = "text-center">Fim</td>
            </tr>
        </thead>
    </table>
    <div class = "mb-3">
        <table class = "report-body table table-sm table-bordered table-striped">
            <tbody>
                @foreach ($resultado as $item)
                    <tr class = "report-row">
                        <td width = "35%">{{ $item->maquina }}</td>
                        <td width = "35%">{{ $item->empresa }}</td>
                        <td width = "15%" class = "text-center">{{ $item->inicio }}</td>
                        <td width = "15%" class = "text-center">{{ $item->fim }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection