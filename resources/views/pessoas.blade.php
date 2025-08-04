@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3" id = "titulo-tela">{{ $titulo }}</h3>
                    </td>
                    <td class = "ultima-atualizacao">
                        <span class = "custom-label-form">{{ $ultima_atualizacao }}</span>
                    </td>
                </tr>
            </table>
            <div id = "filtro-grid-by0" class = "input-group col-12 mb-3" data-table = "#table-dados">
                <input id = "busca" type = "text" class = "form-control form-control-lg" placeholder = "Procurar por..." aria-label = "Procurar por..." aria-describedby = "btn-filtro" />
                <div class = "input-group-append">
                    <button class = "btn btn-secondary btn-search-grid" type = "button" onclick = "listar()">
                        <i class = "my-icon fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class = "custom-table card">
            <div class = "table-header-scroll">
                <table>
                    <thead>
                        <tr class = "sortable-columns" for = "#table-dados">
                            <th width = "5%" class = 'nao-ordena'>
                               
                            </th>
                            <th width = "10%" class = "text-right">
                                <span>Código</span>
                            </th>
                            <th width = "25%">
                                <span>Nome</span>
                            </th>
                            <th width = "20%">
                                <span>Empresa</span>
                            </th>
                            <th width = "20%">
                                <span>Centro de custo</span>
                            </th>
                            <th width = "20%" class = "text-center nao-ordena">
                                <span>Ações</span>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class = "table-body-scroll custom-scrollbar">
                <table id = "table-dados" class = "table">
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <button class = "btn btn-primary custom-fab" type = "button" onclick = "pessoa = new Pessoa(0)">
        <i class = "my-icon fas fa-plus"></i>
    </button>
    <script type = "text/javascript" language = "JavaScript">
        function listar(coluna) {
            $.get(URL + "/colaboradores/listar/", {
                filtro : document.getElementById("busca").value,
                tipo : document.getElementById("titulo-tela").innerHTML.charAt(0)
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    let biometria;
                    if(linha.possui_biometria === 'possui') {
                        biometria = "<td width = '5%'><img src={{ asset('img/biometria-sim.png') }} class = 'imagem-biometria'></td>";
                    } else if(linha.possui_biometria === 'nao-possui'){
                        biometria = "<td width = '5%'><img src={{ asset('img/biometria-nao.png') }} class = 'imagem-biometria'></td>";
                    } else {
                        biometria = "<td width = '5%'></td>";
                    }
                    resultado += "<tr>" +
                        biometria + 
                        "<td width = '10%' class = 'text-right'>" + linha.id.toString().padStart(4, "0") + "</td>" +
                        "<td width = '25%'>" + linha.nome + "</td>" +
                        "<td width = '20%'>" + linha.empresa + "</td>" +
                        "<td width = '20%'>" + linha.setor + "</td>" +
                        "<td class = 'text-center btn-table-action' width = '20%'>";
                    if (parseInt(linha.possui_retiradas)) {
                        resultado += "<i class = 'my-icon fa-light fa-file' title = 'Retiradas' onclick = 'retirada_pessoa(" + linha.id + ")'></i>" +
                            "<i class = 'my-icon fa-regular fa-clock-rotate-left' title = 'Desfazer retiradas' onclick = 'desfazer_retiradas(" + linha.id + ")'></i>";
                    }
                    if (parseInt(linha.possui_atribuicoes)) resultado += "<i class = 'my-icon far fa-calendar-alt' title = 'Próximas retiradas' onclick = 'proximas_retiradas(" + linha.id + ")'></i>";
                    resultado += "" +
                            "<i class = 'my-icon far fa-box'       title = 'Atribuir produto' onclick = 'atribuicao(false, " + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-tshirt'    title = 'Atribuir grade'   onclick = 'atribuicao(true, " + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-edit'      title = 'Editar'           onclick = 'pessoa = new Pessoa(" + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-trash-alt' title = 'Excluir'          onclick = 'excluir(" + linha.id + ", " + '"/colaboradores"' + ")'></i>" +
                        "</td>" +
                    "</tr>";
                });
                document.getElementById("table-dados").innerHTML = resultado;
                ordenar(coluna);
            });
        }

        function proximas_retiradas(id_pessoa) {
            $.get(URL + "/retiradas/proximas/" + id_pessoa, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                let referencia = false;
                let tamanho = false;
                let resultado = "";
                let maximo_verde = 0;
                let maximo_vermelho = 0;
                data.retiradas.forEach((linha) => {
                    let dias = parseInt(linha.dias);
                    if (dias > 0) {
                        if (dias > maximo_verde) maximo_verde = dias;
                    } else {
                        if (Math.abs(dias) > maximo_vermelho) maximo_vermelho = Math.abs(dias);
                    }
                });
                const hex = ["11", "22", "33", "44", "55", "66", "77", "88", "99", "AA", "BB", "CC", "DD", "EE", "FF"];
                data.retiradas.forEach((linha) => {
                    let dias = parseInt(linha.dias);
                    if (linha.tamanho) tamanho = true;
                    if (linha.referencia) referencia = true;
                    let op_verde = hex[parseInt((((dias / maximo_verde) * 100) * 14) / 100)];
                    let op_vermelho = hex[parseInt((((Math.abs(dias) / maximo_vermelho) * 100) * 14) / 100)];
                    resultado += "<tr>" +
                        "<td class = 'align-middle'>" + linha.id_produto.toString().padStart(6, "0") + "</td>" +
                        "<td class = 'align-middle'>" + linha.descr + "</td>" +
                        "<td class = 'align-middle'>" + linha.referencia + "</td>" +
                        "<td class = 'align-middle'>" + linha.tamanho + "</td>" +
                        "<td class = 'align-middle text-right'>" + linha.qtd + "</td>" +
                        "<td class = 'align-middle'>" + linha.proxima_retirada + "</td>" +
                        "<td class = 'align-middle' style = 'background:" + (dias < 0 ? "#ff0000" + op_vermelho : "#00ff00" + op_verde) + "'>" + Math.abs(dias) + "</td>" +
                    "</tr>";
                });
                document.getElementById("proximasRetiradasModalLabel").innerHTML = "Próximas retiradas (" + data.nome + ")"; 
                document.getElementById("table-ret-dados").innerHTML = resultado;
                Array.from(document.getElementsByClassName("tamanho")).forEach((el) => {
                    if (!tamanho) el.classList.add("d-none");
                    else el.classList.remove("d-none");
                });
                Array.from(document.getElementsByClassName("referencia")).forEach((el) => {
                    if (!referencia) el.classList.add("d-none");
                    else el.classList.remove("d-none");
                });
                modal("proximasRetiradasModal", 0);
            });
        }

        function retirada_pessoa(id_pessoa) {
            let req = {};
            ["inicio", "fim"].forEach((chave) => {
                req[chave] = "";
            });
            req.id_pessoa = id_pessoa;
            req.tipo = "A";
            req.rel_grupo = "pessoa";
            req.consumo = "todos";
            let link = document.createElement("a");
            link.href = URL + "/relatorios/retiradas?" + $.param(req);
            link.target = "_blank";
            link.click();
        }

        function desfazer_retiradas(_id_pessoa) {
            s_confirm("Tem certeza que deseja desfazer as retiradas?<br>Essa alteração é irreversível.", function() {
                $.post(URL + "/retiradas/desfazer", {
                    _token : $("meta[name='csrf-token']").attr("content"),
                    id_pessoa : _id_pessoa
                }, function() {
                    location.reload();
                });
            });
        }
    </script>

    @include("modals.atribuicoes_modal")
    @include("modals.retiradas_modal")
    @include("modals.supervisor_modal")
    @include("modals.proximas_retiradas_modal")
@endsection