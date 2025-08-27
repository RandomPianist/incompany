
<!-- Modal -->

<style type = "text/css">
    .slider {
        -webkit-appearance:none;
        width:100%;
        height:25px;
        background:#d3d3d3;
        outline:none;
        opacity:0.7;
        -webkit-transition:.2s;
        transition:opacity .2s
    }

    .slider:hover {
        opacity:1
    }

    .slider::-webkit-slider-thumb {
        -webkit-appearance:none;
        appearance:none;
        width:25px;
        height:25px;
        background:#04AA6D;
        cursor:pointer
    }

    .slider::-moz-range-thumb {
        width:25px;
        height:25px;
        background:#04AA6D;
        cursor:pointer
    }
</style>

<div class = "modal fade" id = "retiradasModal" aria-labelledby = "retiradasModalLabel" aria-hidden = "true">
    <div class = "modal-dialog" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "retiradasModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    @csrf
                    <div class = "row">
                        <div class = "col-12">
                            <label for = "variacao" class = "custom-label-form">Selecione uma variação: *</label>
                            <select class = "form-control" id = "variacao"></select>
                        </div>
                    </div>
                    <div class = "row">
                        <div class = "col-12">
                            <div class = "w-100">
                                <input type = "range" id = "quantidade2" min = 1 max = {{ intval($max_atb) }} value = 1 class = "slider" oninput = "atualizaQtd()"/>
                                <p class = "custom-label-form">
                                    Quantidade:
                                    <span id = "quantidade2_label"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class = "row">
                        <div class = "col-12">
                            <label for = "data-ret" class = "custom-label-form">Data da retirada: *</label>
                            <input id = "data-ret" class = "form-control data" autocomplete = "off" type = "text" onclick = "limpar_invalido()" />
                        </div>
                    </div>
                </div>
            </div>
            <div class = "d-flex">
                <button id = "btn-retirada" type = "button" class = "btn btn-target mx-auto my-4 mb-4 px-5">Retirar</button>
            </div>
        </div>
    </div>
</div>

<script type = "text/javascript" language = "JavaScript">
    function atualizaQtd() {
        $("#quantidade2_label").html($("#quantidade2").val());
    }

    function retirar(id) {
        $("#quantidade2").val(1);
        atualizaQtd();
        $.get(URL + "/atribuicoes/produtos/" + id, function(data) {
            let pai = $($($("#variacao").parent()).parent());
            let resultado = "";
            if (typeof data == "string") data = $.parseJSON(data);
            data.forEach((variacao) => {
                resultado += "<option value = 'prod-" + variacao.id + "'>" + variacao.descr + "</option>";
            });
            $("#variacao").html(resultado);
            $(pai).removeClass("d-none");
            if (data.length < 2) $(pai).addClass("d-none");
            pai = $($($($("#quantidade2").parent()).parent()).parent());
            $(pai).addClass("d-none")
            if (parseInt($("#quantidade2").attr("max")) > 1) $(pai).removeClass("d-none");
            $("#btn-retirada").click(function() {
                let erro = "";
                
                if (!$("#data-ret").val()) erro = "Preencha o campo";
                else if (eFuturo($("#data-ret").val())) erro = "A retirada não pode ser no futuro";
                
                if (!erro) {
                    $.get(URL + "/retiradas/consultar", {
                        atribuicao : id,
                        qtd : $("#quantidade2").val(),
                        pessoa : pessoa_atribuindo
                    }, function(ok) {
                        if (!parseInt(ok)) modal2("supervisorModal", ["cpf2", "senha2"]);
                        else retirarMain(id);
                    });
                } else {
                    $("#data-ret").addClass("invalido");
                    s_alert(erro);
                }
            });
            let titulo = "Retirada retroativa - " + data[0].titulo;
            if (titulo.length > 46) titulo = titulo.substring(0, 46).trim() + "...";
            $("#retiradasModalLabel").html(titulo);
            $("#quantidade2").val(1);
            atualizaQtd();
            $("#data-ret").val("");
            $("#retiradasModal").modal();
        });
    }

    function validar() {
        limpar_invalido();
        let erro = "";

        if (!$("#cpf2").val()) {
            erro = "Preencha o campo";
            $("#cpf2").addClass("invalido");
        }

        if (!$("#senha2").val()) {
            if (!erro) erro = "Preencha o campo";
            else erro = "Preencha os campos";
            $("#senha2").addClass("invalido");
        }

        if (!erro && !validar_cpf($("#cpf2").val())) {
            erro = "CPF inválido";
            $("#cpf2").addClass("invalido");
        }

        if (!erro) {
            $.post(URL + "/colaboradores/supervisor", {
                _token : $("meta[name='csrf-token']").attr("content"),
                cpf : $("#cpf2").val().replace(/\D/g, ""),
                senha : $("#senha2").val()
            }, function(ok) {
                if (parseInt(ok)) retirarMain(id, ok);
                else s_alert("Supervisor inválido");
            });
        } else s_alert(erro);
    }

    function retirarMain(id, _supervisor) {
        if (_supervisor === undefined) _supervisor = 0;
        $.post(URL + "/retiradas/salvar", {
            _token : $("meta[name='csrf-token']").attr("content"),
            supervisor : _supervisor,
            atribuicao : id,
            pessoa : pessoa_atribuindo,
            produto : $("#variacao").val().replace("prod-", ""),
            data : $("#data-ret").val(),
            quantidade : $("#quantidade2").val()
        }, function() {
            $("#supervisorModal").modal("hide");
            $("#retiradasModal").modal("hide");
            Swal.fire({
                icon : "success",
                title : "Sucesso",
                confirmButtonColor : "rgb(31, 41, 55)"
            }).then((result) => {
                listar();
            });
        });
    }
</script>