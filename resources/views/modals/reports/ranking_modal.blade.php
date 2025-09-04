
<!-- Modal -->
<div class = "modal fade" id = "relatorioRankingModal" aria-labelledby = "relatorioRankingModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "relatorioRankingModalLabel">Ranking de retiradas</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/relatorios/ranking" method = "GET" target = "_blank">
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-6">
                                <label for = "rel-inicio4" class = "custom-label-form">Início:</label>
                                <input id = "rel-inicio4" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" data-prox = "rel-fim4" />
                            </div>
                            <div class = "col-6">
                                <label for = "rel-fim4" class = "custom-label-form">Fim:</label>
                                <input id = "rel-fim4" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "rel-tipo3" class = "custom-label-form">Tipo:</label>
                                <select class = "form-control" id = "rel-tipo3" name = "tipo">
                                    <option value = "todos">Todos</option>
                                    <option value = "ativos">Ativos</option>
                                    <option value = "inativos">Inativos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto mb-4 my-4 px-5" onclick = "relatorio.validar()">Visualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>