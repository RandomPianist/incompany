@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Sugestão de compra</h6>
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
                    <td width = "36%">Produto</td>
                    <td width = "8%" class = "text-right">Saldo anterior</td>
                    <td width = "8%" class = "text-right">Entradas</td>
                    <td width = "8%" class = "text-right">Saídas avulsas</td>
                    <td width = "8%" class = "text-right">Retiradas</td>
                    <td width = "8%" class = "text-right">Saídas totais</td>
                    <td width = "8%" class = "text-right">Saldo final</td>
                    <td width = "8%" class = "text-right">
                        @if (request("tipo") == "G") Giro de estoque @else Qtde. mínima @endif
                    </td>
                    <td width = "8%" class = "text-right">Qtde. Sugerida</td>
                </tr>
            </thead>
        </table>
        <div class = "mb-3">
            <table class = "report-body table table-sm table-bordered table-striped">
                <tbody>
                    @foreach ($item["maquina"]["produtos"] as $produto)
                        <tr class = "report-row">
                            <td width = "36%">{{ $produto["descr"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["saldo_ant"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["entradas"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["saidas_avulsas"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["retiradas"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["saidas_totais"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["saldo_res"] }}</td>
                            <td width = "8%" class = "text-right">
                                @if (request("tipo") == "G") {{ $produto["giro"] }} @else {{ $produto["minimo"] }} @endif
                            </td>
                            <td width = "8%" class = "text-right">{{ $produto["sugeridos"] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endsection