
<!-- Modal -->
<div class = "modal fade" id = "proximasRetiradasModal" aria-labelledby = "proximasRetiradasModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "proximasRetiradasModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "w-100">
                        <table id = "table-ret" class = "table table-striped mb-4">
                            <thead>
                                <tr>
                                    <th>Cód.</th>
                                    <th>Produto</th>
                                    <th class = "referencia">Referência</th>
                                    <th class = "tamanho">Tamanho</th>
                                    <th class = "text-right">Qtde.</th>
                                    <th>Próxima retirada</th>
                                    <th class = "text-right">Dias</th>
                                </tr>
                            </thead>
                            <tbody id = "table-ret-dados"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>