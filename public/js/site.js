// Comportamiento del lado cliente sin scripts en línea (compatible con la CSP).
(function () {
    "use strict";

    // Confirmación para formularios marcados con data-confirm (p. ej. borrar).
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (!window.confirm(form.getAttribute("data-confirm"))) {
                event.preventDefault();
            }
        });
    });

    // Campos que deben coincidir (repetir contraseña): data-match="idDelOtroCampo".
    // La validación real ocurre en el servidor; esto es solo mejora de UX.
    document.querySelectorAll("input[data-match]").forEach(function (input) {
        var other = document.getElementById(input.getAttribute("data-match"));
        if (!other) {
            return;
        }
        var check = function () {
            var mismatch = input.value !== other.value;
            input.setCustomValidity(mismatch ? (input.getAttribute("data-match-message") || "Los valores no coinciden") : "");
        };
        input.addEventListener("input", check);
        other.addEventListener("input", check);
    });
})();
