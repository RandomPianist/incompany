
<!-- Modal -->
<div class = "modal fade" id = "retiradasListaModal" aria-labelledby = "retiradasListaModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h3 class = "modal-title header-color" id = "retiradasListaModalLabel">Produtos retirados</h3>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "w-100">
                        <table id = "table-retirados" class = "table-itens-em-atraso w-100 mb-4" border = 1>
                            <thead>
                                <tr>
                                    <td class = "pl-2">Produto</td>
                                    <td class = "text-right pr-2">Quantidade</td>
                                </tr>
                            </thead>
                            <tbody id = "table-retirados-dados"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>