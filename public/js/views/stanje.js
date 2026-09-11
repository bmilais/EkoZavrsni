/* View stanja - zauzetost mentora, predmeta i ciklusa */
const StanjeView = {
  render(res, ciklus) {
    const cid = ciklus.ID;
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

    View.set(`
      <div class="page">
        <p class="back-link"><a href="#/admin/ciklusi">&larr; Povratak na popis ciklusa</a></p>
        <h1>Stanje zauzetosti — ${UI.esc(ciklus.NAZIV)}</h1>
        <div class="flex">
          <a class="btn secondary small" href="#/admin/ucenici?ciklus_id=${cid}">→ Učenici ovog ciklusa</a>
          <a class="btn secondary small" href="#/admin/teme?ciklus_id=${cid}">→ Teme ovog ciklusa</a>
          <a class="btn secondary small" href="#/admin/odabiri?ciklus_id=${cid}">→ Odabiri ovog ciklusa</a>
        </div>

        <h2>Mentori (profesori)</h2>
        ${UI.table(['Profesor', 'Tema', 'Ukupno odabira', 'Aktivnih odabira'], profRows)}

        <h2>Predmeti</h2>
        ${UI.table(['Predmet', 'Tema', 'Odabira', 'Limit', 'Popunjenost'], predRows)}
      </div>`);
  },
};
