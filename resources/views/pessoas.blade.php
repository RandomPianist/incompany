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
            @include("components.busca")
        </div>
        <div class = "custom-table card">
            <div class = "table-header-scroll">
                <table>
                    <thead>
                        <tr class = "sortable-columns" for = "#table-dados">
                            <th width = "5%" class = 'nao-ordena'>&nbsp;</th>
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
            @include("components.table_dados")
        </div>
    </div>
    @include("components.naoencontrado")
    @include("components.add")
    <script type = "text/javascript" language = "JavaScript">
        function listar(coluna) {
            $.get(URL + "/colaboradores/listar/", {
                filtro : document.getElementById("busca").value,
                tipo : document.getElementById("titulo-tela").innerHTML.charAt(0)
            }, function(data) {
                const img_biometria = '{{ asset("img/biometria-sim.png") }}';
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                if (data.length) {
                    esconderImagemErro();
                    data.forEach((linha) => {
                        let biometria = "";
                        if (linha.possui_biometria.indexOf("possui") > -1) biometria = '<img src = "' + (linha.possui_biometria == "nao-possui" ? img_biometria.replace("sim", "nao") : img_biometria) + '" class = "imagem-biometria" />';
                        resultado += "<tr>" +
                            "<td width = '5%'>" + biometria + "</td>" +
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
                } else mostrarImagemErro();
            });
        }

        function proximas_retiradas(id_pessoa) {
            let tudo = document.getElementById("table-ret").classList;
            let container = document.getElementById("table-ret-dados");
            container.innerHTML = "";
            tudo.add("d-none");
            $.get(URL + "/colaboradores/mostrar/" + id_pessoa, function(resp) {
                if (typeof resp == "string") resp = $.parseJSON(resp);
                document.getElementById("proximasRetiradasModalLabel").innerHTML = "Próximas retiradas (" + resp.nome + ")";
                modal("proximasRetiradasModal", 0, function() {
                    $.get(URL + "/retiradas/proximas/" + id_pessoa, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        let referencia = false;
                        let tamanho = false;
                        let resultado = "";
                        let maximo_verde = 0;
                        let maximo_vermelho = 0;
                        data.forEach((linha) => {
                            let dias = parseInt(linha.dias);
                            if (dias > 0) {
                                if (dias > maximo_verde) maximo_verde = dias;
                            } else {
                                if (Math.abs(dias) > maximo_vermelho) maximo_vermelho = Math.abs(dias);
                            }
                        });
                        const hex = ["11", "22", "33", "44", "55", "66", "77", "88", "99", "AA", "BB", "CC", "DD", "EE", "FF"];
                        data.forEach((linha) => {
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
                        container.innerHTML = resultado;
                        tudo.remove("d-none");
                        Array.from(document.getElementsByClassName("tamanho")).forEach((el) => {
                            if (!tamanho) el.classList.add("d-none");
                            else el.classList.remove("d-none");
                        });
                        Array.from(document.getElementsByClassName("referencia")).forEach((el) => {
                            if (!referencia) el.classList.add("d-none");
                            else el.classList.remove("d-none");
                        });
                    });
                });
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
            req.tipo_colab = "ativos";
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

        function chamar_modal(id) {
            pessoa = new Pessoa(id);
        }
    </script>

    @include("modals.atribuicoes_modal")
    @include("modals.retiradas_modal")
    @include("modals.supervisor_modal")
    @include("modals.proximas_retiradas_modal")
@endsection