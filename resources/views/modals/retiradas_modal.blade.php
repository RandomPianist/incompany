
<!-- Modal -->

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
                                <input type = "range" id = "quantidade2" min = 1 max = {{ intval($max_atb) }} value = 1 class = "slider" oninput = "atribuicao.atualizarQtd()"/>
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

<link rel = "stylesheet" href = "{{ asset('css/especifico/retiradas.css') }}" />

@include("modals.supervisor_modal")