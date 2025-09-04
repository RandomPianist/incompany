
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
            <form action = "{{ $root_url }}/maquinas/comodato/criar" method = "POST">
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
                                    data-prox = "comodato-inicio"
                                    autocomplete = "off"
                                />
                                <input id = "comodato-id_empresa" name = "id_empresa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <a href = "{{ $root_url }}/empresas" title = "Cadastro de empresas" target = "_blank">
                                    <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-6">
                                <label for = "comodato-inicio" class = "custom-label-form">Início: *</label>
                                <input id = "comodato-inicio" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" data-prox = "comodato-fim" />
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

<script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/comodatos.js') }}"></script>