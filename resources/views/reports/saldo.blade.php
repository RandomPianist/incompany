@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Saldo por máquina</h6>
                </li>
                <li class = "text-right">
                    <h6 class = "m-0 traduzir">
                        @php
                            date_default_timezone_set("America/Sao_Paulo");
                            echo ucfirst(strftime("%A, %d de %B de %Y"));
                        @endphp
                    </h6>
                </li>
                <li class = "text-right">
                    @if ($criterios)
                        <h6 class = "m-0">Critérios:</h6>
                        <small>{{ $criterios }}</small>
                    @endif
                </li>
            </ul>
        </div>
    </div>
    <div class = "mt-2 mb-3 linha"></div>
    @foreach ($resultado AS $item)
        <h5>{{ $item["maquina"]["descr"] }}</h5>
        <table class = "report-body table table-sm table-bordered table-striped px-5">
            <thead>
                <tr class = "report-row">
                    <td width = "40%">Produto</td>
                    <td width = "15%" class = "text-right">Saldo Ant.</td>
                    <td width = "15%" class = "text-right">Entradas</td>
                    <td width = "15%" class = "text-right">Saídas</td>
                    <td width = "15%" class = "text-right">Saldo Final</td>
                </tr>
            </thead>
        </table>
        <div class = "mb-3">
            <table class = "report-body table table-sm table-bordered table-striped">
                <tbody>
                    @foreach ($item["maquina"]["produtos"] as $produto)
                        <tr class = "report-row">
                            <td width = "40%">{{ $produto["descr"] }}</td>
                            <td width = "15%" class = "text-right">{{ $produto["saldo_ant"] }}</td>
                            <td width = "15%" class = "text-right">{{ $produto["entradas"] }}</td>
                            <td width = "15%" class = "text-right">{{ $produto["saidas"] }}</td>
                            <td width = "15%" class = "text-right">{{ $produto["saldo_res"] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endsection