
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
            <form action = "{{ config('app.root_url') }}/relatorios/ranking" method = "GET" target = "_blank">
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "rel-inicio4" class = "custom-label-form">Início:</label>
                                <input id = "rel-inicio4" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "rel-fim4" class = "custom-label-form">Fim:</label>
                                <input id = "rel-fim4" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
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