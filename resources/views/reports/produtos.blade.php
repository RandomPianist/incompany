@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Relatório de produtos</h6>
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
        <h5>{{ $item["categoria"] }}</h5>
        <table class = "report-body table table-sm table-bordered table-striped px-5">
            <thead>
                <tr class = "report-row">
                    <td width = "8%">Código</td>
                    <td width = "36%">Descrição</td>
                    <td width = "8%" class = "text-right">Validade</td>
                    <td width = "8%">C. A.</td>
                    <td width = "8%">Validade do C. A.</td>
                    <td width = "16%">Referência</td>
                    <td width = "8%">Tamanho</td>
                    <td width = "8%" class = "text-right">Retiradas</td>
                </tr>
            </thead>
        </table>
        <div class = "mb-3">
            <table class = "report-body table table-sm table-bordered table-striped">
                <tbody>
                    @foreach ($item["produtos"] as $produto)
                        <tr class = "report-row">
                            <td width = "8%">{{ $produto["cod"] }}</td>
                            <td width = "36%">{{ $produto["descr"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["validade"] }}</td>
                            <td width = "8%">{{ $produto["ca"] }}</td>
                            <td width = "8%">{{ $produto["validade_ca"] }}</td>
                            <td width = "16%">{{ $produto["referencia"] }}</td>
                            <td width = "8%">{{ $produto["tamanho"] }}</td>
                            <td width = "8%" class = "text-right">{{ $produto["retiradas"] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endsection