/*
 * SimpleWallet - script lato client
 *
 * Il filtraggio vero e proprio è fatto dal server (controller Payment::index, che legge i
 * parametri GET e applica anche la paginazione). Questo script si occupa solo di rendere
 * l'uso dei filtri comodo; senza JavaScript il form funziona comunque col pulsante "Filtra".
 *
 *  - i filtri partono solo quando si preme "Filtra" (o Invio in un campo): cambiare un valore non fa nulla
 *  - non invia i filtri lasciati vuoti, così l'URL resta pulito (?type=Uscita invece di ?category_id=&type=Uscita&...)
 *  - evidenzia i filtri attualmente applicati
 *  - dopo un filtro si riparte sempre da pagina 1 (il form non contiene il parametro "page")
 *
 * Paginazione dello storico (initPagination): mostra una finestra di 5 pagine alla volta.
 *  - ‹ e › spostano la finestra di 1 pagina, « e » la portano all'inizio / alla fine
 *  - le frecce NON cambiano pagina: la pagina corrente resta quella caricata finché non si clicca un numero
 */
(function () {
    'use strict';

    function initFilterBar() {
        var form = document.querySelector('.filter-bar');
        if (!form) {
            return;
        }

        var fields = Array.prototype.slice.call(
            form.querySelectorAll('select, input[type="text"]')
        );

        // Segna come "attivi" i filtri con un valore (per gli intervalli, solo la casella compilata)
        function markActive() {
            fields.forEach(function (field) {
                field.classList.toggle('is-active', field.value !== '');
            });
            Array.prototype.forEach.call(form.querySelectorAll('.filter-field'), function (wrapper) {
                wrapper.classList.toggle('is-active', wrapper.querySelector('.is-active') !== null);
            });
        }

        // Disabilita i campi vuoti: i campi disabilitati non vengono inviati col form
        function dropEmptyFields() {
            fields.forEach(function (field) {
                field.disabled = (field.value === '');
            });
        }

        form.addEventListener('submit', dropEmptyFields);

        // Tornando indietro col browser la pagina può essere riproposta con i campi ancora disabilitati
        window.addEventListener('pageshow', function () {
            fields.forEach(function (field) {
                field.disabled = false;
            });
            markActive();
        });

        markActive();
    }

    function initPagination() {
        var nav = document.querySelector('.pagination');
        if (!nav) {
            return;
        }

        var WINDOW_SIZE = 5;
        var total = parseInt(nav.getAttribute('data-total'), 10) || 1;
        var current = parseInt(nav.getAttribute('data-current'), 10) || 1;
        var links = Array.prototype.slice.call(nav.querySelectorAll('a.pagination-link'));
        var maxStart = Math.max(1, total - WINDOW_SIZE + 1);

        // La finestra parte centrata sulla pagina corrente
        var start = Math.min(Math.max(1, current - Math.floor(WINDOW_SIZE / 2)), maxStart);

        function createButton(action, label, title) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'pagination-link';
            button.textContent = label;
            button.title = title;
            button.setAttribute('aria-label', title);
            button.setAttribute('data-nav', action);
            return button;
        }

        var first = createButton('first', '\u00AB', 'Prime pagine');
        var prev = createButton('prev', '\u2039', 'Scorri indietro di 1');
        var next = createButton('next', '\u203A', 'Scorri avanti di 1');
        var last = createButton('last', '\u00BB', 'Ultime pagine');

        nav.insertBefore(prev, links[0]);
        nav.insertBefore(first, prev);
        nav.appendChild(next);
        nav.appendChild(last);

        // Mostra solo le pagine della finestra e aggiorna lo stato delle frecce
        function render() {
            links.forEach(function (link, index) {
                var page = index + 1;
                link.hidden = (page < start || page >= start + WINDOW_SIZE);
            });
            first.disabled = prev.disabled = (start <= 1);
            next.disabled = last.disabled = (start >= maxStart);
        }

        first.addEventListener('click', function () { start = 1; render(); });
        prev.addEventListener('click', function () { start = Math.max(1, start - 1); render(); });
        next.addEventListener('click', function () { start = Math.min(maxStart, start + 1); render(); });
        last.addEventListener('click', function () { start = maxStart; render(); });

        render();
    }

    // Modalità scura (pagina account): il tema è l'attributo data-theme su <html>.
    // Lo si ricorda nel cookie "theme", che header.php rilegge ad ogni pagina.
    function initThemeToggle() {
        var toggle = document.getElementById('dark_mode');
        if (!toggle) return;

        var root = document.documentElement;
        toggle.checked = root.getAttribute('data-theme') === 'dark';

        toggle.addEventListener('change', function () {
            if (toggle.checked) {
                root.setAttribute('data-theme', 'dark');
            } else {
                root.removeAttribute('data-theme');
            }
            document.cookie = 'theme=' + (toggle.checked ? 'dark' : 'light') +
                '; path=/; max-age=31536000; SameSite=Lax';
        });
    }

    // Grafico a torta dei metodi di pagamento (pagina Grafici). Chart.js lo carica header.php
    // solo in quella pagina; i dati arrivano dagli attributi data-* del canvas, i colori dal CSS.
    function initPieChart() {
        var canvas = document.getElementById('pieChart');
        if (!canvas || typeof Chart === 'undefined') return;

        var styles = getComputedStyle(document.documentElement);
        var colors = [];
        for (var i = 1; i <= 6; i++) {
            var color = styles.getPropertyValue('--chart-' + i).trim();
            if (color) colors.push(color);
        }

        new Chart(canvas.getContext('2d'), {
            type: 'pie',
            data: {
                labels: JSON.parse(canvas.dataset.labels),
                datasets: [{
                    data: JSON.parse(canvas.dataset.values),
                    backgroundColor: colors,
                    borderColor: styles.getPropertyValue('--white').trim(),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: styles.getPropertyValue('--ink').trim() }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var value = context.parsed;

                                // Percentuale sul totale delle spese
                                var total = context.dataset.data.reduce(function (sum, amount) {
                                    return sum + amount;
                                }, 0);
                                var percentage = (value / total) * 100;

                                return context.label + ': CHF ' +
                                    value.toLocaleString('de-CH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    }) +
                                    ' (' + percentage.toFixed(1) + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Grafico lineare (pagina Grafici): spesa accumulata nel mese corrente rispetto al limite mensile,
    // che è la linea rossa tratteggiata. Dati e limite arrivano dagli attributi data-* del canvas.
    function initLineChart() {
        var canvas = document.getElementById('lineChart');
        if (!canvas || typeof Chart === 'undefined') return;

        var styles = getComputedStyle(document.documentElement);
        var labels = JSON.parse(canvas.dataset.labels);
        var values = JSON.parse(canvas.dataset.values);
        var limit = parseFloat(canvas.dataset.limit);
        var inkSoft = styles.getPropertyValue('--ink-soft').trim();
        var gridColor = styles.getPropertyValue('--line').trim();

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Spese accumulate',
                        data: values,
                        borderColor: styles.getPropertyValue('--chart-1').trim(),
                        backgroundColor: styles.getPropertyValue('--chart-1').trim(),
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 3
                    },
                    {
                        label: 'Limite mensile',
                        data: labels.map(function () { return limit; }),
                        borderColor: styles.getPropertyValue('--rust').trim(),
                        backgroundColor: styles.getPropertyValue('--rust').trim(),
                        borderWidth: 2,
                        borderDash: [6, 4],
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: styles.getPropertyValue('--ink').trim() }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': CHF ' +
                                    context.parsed.y.toLocaleString('de-CH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: {
                            color: inkSoft,
                            // Le etichette sono date complete (yyyy-MM-dd): sull'asse mostro solo il giorno
                            callback: function (value) { return labels[value].slice(8); }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: limit * 1.1,      // il limite resta sempre visibile
                        grid: { color: gridColor },
                        ticks: {
                            color: inkSoft,
                            callback: function (value) { return value.toLocaleString('de-CH'); }
                        }
                    }
                }
            }
        });
    }

    // Snackbar: avvisi temporanei in basso che si chiudono da soli (con pulsante di chiusura).
    // Dal codice: showSnackbar('Testo', 'success' | 'error' | 'info').
    // Gli avvisi che le pagine stampano già in HTML vengono trasformati da initSnackbars().
    var SNACKBAR_MS = { success: 5000, info: 5000, error: 8000 };      // durata prima della chiusura
    var SNACKBAR_MAX = 3;                                              // quanti ne restano insieme a schermo
    var snackbarContainer = null;

    function showSnackbar(message, type) {
        type = SNACKBAR_MS[type] ? type : 'info';

        if (!snackbarContainer) {
            snackbarContainer = document.createElement('div');
            snackbarContainer.className = 'snackbar-container';
            document.body.appendChild(snackbarContainer);
        }
        while (snackbarContainer.children.length >= SNACKBAR_MAX) {
            snackbarContainer.firstChild.remove();                      // fa posto eliminando il più vecchio
        }

        var bar = document.createElement('div');
        bar.className = 'snackbar snackbar-' + type;
        bar.setAttribute('role', type === 'error' ? 'alert' : 'status');   // lo leggono anche gli screen reader

        var text = document.createElement('span');
        text.className = 'snackbar-text';
        text.textContent = message;                                     // textContent: niente HTML iniettato

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'snackbar-close';
        close.setAttribute('aria-label', 'Chiudi');
        close.textContent = '\u00D7';

        bar.appendChild(text);
        bar.appendChild(close);
        snackbarContainer.appendChild(bar);

        var timer;
        function dismiss() {
            clearTimeout(timer);
            bar.classList.remove('is-visible');
            setTimeout(function () { bar.remove(); }, 300);             // dopo l'animazione di uscita
        }
        function startTimer() { timer = setTimeout(dismiss, SNACKBAR_MS[type]); }

        close.addEventListener('click', dismiss);
        bar.addEventListener('mouseenter', function () { clearTimeout(timer); });   // col mouse sopra non si chiude
        bar.addEventListener('mouseleave', startTimer);

        requestAnimationFrame(function () { bar.classList.add('is-visible'); });
        startTimer();
    }

    window.showSnackbar = showSnackbar;

    // Trasforma in snackbar gli avvisi già presenti nella pagina:
    //  - .auth-notice dentro .main-content = messaggio di successo (Flash 'success') delle pagine interne
    //  - .form-error-banner = errore generale (Flash 'errors'), tranne quelli accanto ai filtri
    // Restano com'erano le istruzioni fisse delle pagine di conferma e gli errori sotto i singoli campi.
    // Senza JS gli avvisi restano visibili come prima.
    function initSnackbars() {
        var notices = document.querySelectorAll('.main-content .auth-notice, .form-error-banner');

        Array.prototype.forEach.call(notices, function (notice) {
            if (notice.closest('.filter-errors')) return;

            var type = notice.classList.contains('form-error-banner') ? 'error' : 'success';
            var message = notice.textContent.replace(/\s+/g, ' ').trim();

            notice.remove();
            if (message) showSnackbar(message, type);
        });
    }

    document.addEventListener('DOMContentLoaded', initFilterBar);
    document.addEventListener('DOMContentLoaded', initPagination);
    document.addEventListener('DOMContentLoaded', initThemeToggle);
    document.addEventListener('DOMContentLoaded', initPieChart);
    document.addEventListener('DOMContentLoaded', initLineChart);
    document.addEventListener('DOMContentLoaded', initSnackbars);
})();