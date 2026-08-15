/* View stanja - zauzetost mentora, predmeta i ciklusa */
const StanjeView = {
  render(res) {
    const profRows = res.profesori.map(p => [
      UI.esc(p.PREZIME + ' ' + p.IME),
      p.BR_TEMA, p.BR_ODABIRA, p.BR_AKTIVNIH,
    ]);
    const predRows = res.predmeti.map(p => [
      UI.esc(p.NAZIV),
      p.BR_TEMA, p.BR_ODABIRA,
      p.LIMIT !== null ? p.LIMIT : '-',
      p.POPUNJENOST !== null ? p.POPUNJENOST + '%' : '-',
    ]);
    const cikRows = res.ciklusi.map(c => [
      UI.esc(c.NAZIV),
      UI.badge(c.STATUS_LABEL, { priprema: 'gray', otvoreno: 'green', zakljucano: 'blue', arhivirano: 'gray' }[c.STATUS] || 'gray'),
      c.BR_TEMA, c.BR_ODABIRA,
    ]);

    View.set(`
      <div class="page">
        <h1>Stanje zauzetosti tema i mentora</h1>

        <h2>Mentori (profesori)</h2>
        ${UI.table(['Profesor', 'Tema', 'Ukupno odabira', 'Aktivnih odabira'], profRows)}

        <h2>Predmeti</h2>
        ${UI.table(['Predmet', 'Tema', 'Odabira', 'Limit', 'Popunjenost'], predRows)}

        <h2>Ciklusi</h2>
        ${UI.table(['Ciklus', 'Status', 'Tema', 'Odabira'], cikRows)}

        <p class="mt"><a href="#/admin">&larr; Povratak na admin panel</a></p>
      </div>`);
  },
};
