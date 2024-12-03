@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Ranking de retiradas</h6>
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
        <h5>{{ $item["setor"] }}</h5>
        <table class = "report-body table table-sm table-bordered table-striped px-5">
            <thead>
                <tr class = "report-row">
                    <td width = "80%">Colaborador</td>
                    <td width = "20%" class = "text-right">Quantidade</td>
                </tr>
            </thead>
        </table>
        <div class = "mb-3">
            <table class = "report-body table table-sm table-bordered table-striped">
                <tbody>
                    @for ($i = 0; $i < sizeof($item["pessoas"]); $i++)
                        <tr class = "report-row">
                            <td width = "5%" class = "text-right">{{ ($i + 1) }}</td>
                            <td width = "75%">{{ $item["pessoas"][$i]["nome"] }}</td>
                            <td width = "20%" class = "text-right">{{ number_format($item["pessoas"][$i]["retirados"], 0) }}</td>
                        </tr>
                    @endfor
                    <tr class = "report-row">
                        <td width = "80%" colspan = 2>
                            <b>Total</b>
                        </td>
                        <td width = "20%" class = "text-right"><b>{{ number_format($item["total_qtd"], 0) }}</b></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class = "line-div"></div>
    @endforeach
    <table class = "report-body table table-sm table-bordered table-striped">
        <tbody>
            <tr>
                <td width = "80%">
                    <h5>Totais:</h5>
                </td>
                <td width = "20%" class = "text-right">
                    <h5>{{ number_format($qtd_total, 0) }}</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endsection