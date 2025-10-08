function apenasNumeros(val) {
    return val.replace(/\D/g, "").replace(",", "");
}

function numerico(el) {
    $(el).val(apenasNumeros($(el).val()).substring(0, 4));
}

function dinheiro(texto_final) {
    if (texto_final !== texto_final.toString()) texto_final = texto_final.toFixed(2);
    texto_final = apenasNumeros(texto_final.toString());
    if (texto_final.length > 2) {
        let valor_inteiro = parseInt(texto_final.substring(0, texto_final.length - 2)).toString();
        let resultado_pontuado = "";
        let cont = 0;
        for (var i = valor_inteiro.length - 1; i >= 0; i--) {
            if (cont % 3 == 0 && cont > 0) resultado_pontuado = "." + resultado_pontuado;
            resultado_pontuado = valor_inteiro[i] + resultado_pontuado;
            cont++;
        }
        texto_final = resultado_pontuado + "," + texto_final.substring(texto_final.length - 2).padStart(2, "0");
    } else texto_final = "0," + texto_final.padStart(2, "0");
    texto_final = "R$ " + texto_final;
    return texto_final;
}

const phoneMask = (value) => {
    if (!value) return "";
    value = apenasNumeros(value);
    if (value.length >= 8 && value.length <= 13) {
        if (value.length == 10 || value.length == 11) value = value.replace(/(\d{2})(\d)/, "($1) $2");
        else if (value.length == 12 || value.length == 13) value = value.replace(/(\d{2})(\d{2})(\d)/, "+$1 ($2) $3");
        value = value.replace(/(\d)(\d{4})$/, "$1-$2");
    }
    return value.substring(0, 20);
}