<!-- Modal -->
<div class = "modal fade" id = "relatorioProdutosModal" aria-hidden = "true">
    <div class = "modal-dialog modal-lg modal-dialog-centered" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color">Produtos</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/relatorios/produtos" method = "GET" target = "_blank">
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "@if ($admin) col-5 @else col-6 @endif form-search form-search-2">
                                <label for = "categoria" class = "custom-label-form">Categoria:</label>
                                <input id = "rel-categoria"
                                    name = "categoria"
                                    class = "form-control autocomplete w-108"
                                    data-input = "#rel-id_categoria"
                                    data-table = "categorias"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_categoria" name = "id_categoria" type = "hidden"/>
                            </div>
                            @if ($admin)
                                <div class = "col-1 d-flex align-items-center pt-3 j-end">
                                    <i
                                        class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                        data-atalho = "categorias"
                                        data-campo_id = "rel-id_categoria"
                                        data-campo_descr = "rel-categoria"
                                    ></i>
                                </div>
                            @endif
                            <div class = "col-6">
                                <label for = "rel-ordenacao" class = "custom-label-form">Ordenação:</label>
                                <select class = "form-control" id = "rel-ordenacao" name = "ordenacao" onchange = "relatorio.mudaTipo()">
                                    <option value = "cod">Código</option>
                                    <option value = "descr">Descrição</option>
                                    <option value = "ranking">Ranking de retiradas</option>
                                </select>
                            </div>
                        </div>
                        <div class = "row d-none" id = "rel-datas2">
                            <div class = "col-6">
                                <label for = "rel-inicio5" class = "custom-label-form">Início:</label>
                                <input id = "rel-inicio5" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" data-prox = "rel-fim5" />
                            </div>
                            <div class = "col-6">
                                <label for = "rel-fim5" class = "custom-label-form">Fim:</label>
                                <input id = "rel-fim5" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
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