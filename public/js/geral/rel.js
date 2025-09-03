$(document).ready(function() {
    $(".traduzir").each(function() {
        $(this).html(
            $(this).html()
                .replace("Monday", "Segunda-feira")
                .replace("Tuesday", "Terça-feira")
                .replace("Wednesday", "Quarta-feira")
                .replace("Thursday", "Quinta-feira")
                .replace("Friday", "Sexta-feira")
                .replace("Saturday", "Sábado")
                .replace("Sunday", "Domingo")
                .replace("January", "janeiro")
                .replace("February", "fevereiro")
                .replace("March", "março")
                .replace("April", "abril")
                .replace("May", "maio")
                .replace("June", "junho")
                .replace("July", "julho")
                .replace("August", "agosto")
                .replace("September", "setembro")
                .replace("October", "outubro")
                .replace("November", "novembro")
                .replace("December", "dezembro")
        );      
        if (location.href.indexOf("solicitacoes") > -1) {
            $("#btn-print").addClass("d-none");
            carregar();
        } else $("#menu").addClass("d-none");
    });

    $(".dinheiro").each(function() {
        let resultado = dinheiro($(this).html());
        if ($(this).hasClass("analitico")) resultado = "Preço: " + resultado;
        $(this).html(resultado);
    });
});