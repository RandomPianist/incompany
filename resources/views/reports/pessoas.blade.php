@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Relatório de pessoas</h6>
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
        <h5>{{ $item["empresa"] }}</h5>
        @foreach ($item["setor"] as $setor)
            <h6 class = "pl-3 fw-600">{{ $setor["descr"] }}</h6>
            <table class = "report-body table table-sm table-bordered table-striped px-5">
                <thead>
                    <tr class = "report-row">
                        <td width = "25%">Nome</td>
                        <td width = "15%">CPF</td>
                        <td width = "20%">Função</td>
                        <td width = "20%">Telefone</td>
                        <td width = "20%">E-mail</td>
                    </tr>
                </thead>
            </table>
            <div class = "mb-3">
                <table class = "report-body table table-sm table-bordered table-striped">
                    <tbody>
                        @foreach ($setor["pessoas"] as $pessoa)
                            <tr class = "report-row">
                                <td width = "25%">$pessoa["nome"]</td>
                                <td width = "15%">$pessoa["cpf"]</td>
                                <td width = "20%">$pessoa["funcao"]</td>
                                <td width = "20%">$pessoa["telefone"]</td>
                                <td width = "20%">$pessoa["email"]</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class = "line-div"></div>
        @endforeach
    @endforeach
@endsection