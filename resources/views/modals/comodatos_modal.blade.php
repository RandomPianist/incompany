
<!-- Modal -->
<div class = "modal fade" id = "comodatosModal" aria-labelledby = "comodatosModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "comodatosModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/maquinas/comodato/criar" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input class = "id_maquina" name = "id_maquina" type = "hidden" />
                        <div class = "row">
                            <div class = "col-11 pr-0 form-search form-search-2">
                                <label for = "comodato-empresa" class = "custom-label-form">Empresa: *</label>
                                <input id = "comodato-empresa"
                                    name = "empresa"
                                    class = "form-control autocomplete"
                                    data-input = "#comodato-id_empresa"
                                    data-table = "empresas"
                                    data-column = "nome_fantasia"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "comodato-id_empresa" name = "id_empresa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <a href = "{{ config('app.root_url') }}/empresas" title = "Cadastro de empresas" target = "_blank">
                                    <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-6">
                                <label for = "comodato-inicio" class = "custom-label-form">Início: *</label>
                                <input id = "comodato-inicio" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                            <div class = "col-6">
                                <label for = "comodato-fim" class = "custom-label-form">Fim: *</label>
                                <input id = "comodato-fim" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto mb-4 my-4 px-5" onclick = "validar_comodato()">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type = "text/javascript" language = "JavaScript">
    function comodatar(id) {
        $.get(URL + "/valores/{{ $alias }}/mostrar/" + id, function(descr) {
            document.getElementById("comodatosModalLabel").innerHTML = "Locando " + descr;
            Array.from(document.getElementsByClassName("id_maquina")).forEach((el) => {
                el.value = id;
            });
            document.getElementById("comodato-inicio").value = hoje();
            modal2("comodatosModal", ["comodato-fim", "comodato-empresa", "comodato-id_empresa"]);
        });
    }

    function encerrar(_id_maquina) {
        Swal.fire({
            title: "Aviso",
            html : "Tem certeza que deseja encerrar essa locação?",
            showDenyButton : true,
            confirmButtonText : "NÃO",
            confirmButtonColor : "rgb(31, 41, 55)",
            denyButtonText : "SIM"
        }).then((result) => {
            if (result.isDenied) {
                $.post(URL + "/maquinas/comodato/encerrar", {
                    _token : $("meta[name='csrf-token']").attr("content"),
                    id_maquina : _id_maquina
                }, function() {
                    location.reload();
                });
            }
        });
    }

    function validar_comodato() {
        limpar_invalido();
        let erro = verifica_vazios(["comodato-empresa", "comodato-inicio", "comodato-fim"]).erro;
        let el_inicio = document.getElementById("comodato-inicio");
        let el_fim = document.getElementById("comodato-fim");
        let el_empresa = document.getElementById("comodato-empresa");
        if (!erro) erro = validar_datas(el_inicio, el_fim, true);
        $.get(URL + "/maquinas/comodato/consultar/", {
            inicio : el_inicio.value,
            fim : el_fim.value,
            empresa : el_empresa.value,
            id_empresa : document.getElementById("comodato-id_empresa").value,
            id_maquina : document.getElementsByClassName("id_maquina")[0].value
        }, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.texto) {
                if (data.invalida_inicio !== undefined) {
                    if (data.invalida_inicio == "S") el_inicio.classList.add("invalido");
                    if (data.invalida_fim == "S") el_fim.classList.add("invalido");
                } else el_empresa.classList.add("invalido");
                erro = data.texto;
            }
            if (!erro) document.querySelector("#comodatosModal form").submit();
            else s_alert(erro);
        });
    }
</script>