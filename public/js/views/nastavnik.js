/* View nastavničkog panela - vlastite teme i odabiri učenika */
const NastavnikView = {
  render(teme, odabiri, temaId, filter) {
    let temeHtml = '<p class="muted">Nemate zadanih tema.</p>';
    if (teme.length) {
      temeHtml = UI.table(
        ['Tema', 'Predmet', 'Razred', 'Ciklus', 'Odabira', 'Predmet (popunjenost)', 'Mentor (popunjenost)'],
        teme.map(t => {
          const pop = (br, lim) => {
            if (lim === null) return '-';
            const puno = parseInt(br, 10) >= parseInt(lim, 10);
            return puno ? UI.badge(br + ' / ' + lim, 'orange') : br + ' / ' + lim;
          };
          return [
            UI.esc(t.NAZIV),
            UI.esc(t.PREDMET_NAZIV),
            UI.esc(t.RAZRED_NAZIV),
            UI.esc(t.CIKLUS_NAZIV || '-'),
            t.BR_ODABIRA,
            pop(t.P_BR, t.PREDMET_LIMIT),
            pop(t.M_BR, t.M_MAX),
          ];
        })
      );
    }

    const filterOpcije = [
      ['aktivni', 'Aktivni'],
      ['ponisteni', 'Poništeni'],
      ['svi', 'Svi'],
    ].map(f => '<option value="' + f[0] + '"' + (f[0] === filter ? ' selected' : '') + '>' + f[1] + '</option>').join('');

    const temaFilter =
      '<select onchange="Pages.nastavnikFilterTema(this.value)">' +
      '<option value="0"' + (!temaId ? ' selected' : '') + '>Sve teme</option>' +
      teme.map(t => '<option value="' + t.ID + '"' + (t.ID === temaId ? ' selected' : '') + '>' + UI.esc(t.NAZIV) + '</option>').join('') +
      '</select>';

    const statusFilter =
      '<select onchange="Pages.nastavnikFilterStatus(this.value)">' +
      filterOpcije +
      '</select>';

    let odabiriHtml = '<p class="muted">Nema odabira.</p>';
    if (odabiri.length) {
      odabiriHtml = UI.table(
        ['Učenik', 'E-mail', 'Razred', 'Tema', 'Datum odabira', 'Status', 'Poništeno', 'Razlog'],
        odabiri.map(o => [
          UI.esc(o.U_PREZIME + ' ' + o.U_IME),
          UI.esc(o.U_EMAIL),
          UI.esc(o.RAZRED_NAZIV),
          UI.esc(o.TEMA_NAZIV),
          View.fmtDate(o.DATUM_ODABIRA),
          o.STATUS === 'aktivan' ? UI.badge('Aktivan', 'green') : UI.badge('Poništen', 'red'),
          View.fmtDate(o.DATUM_PONISTENJA),
          UI.esc(o.OBRAZLOZENJE || '-'),
        ])
      );
    }

    View.set(`
      <div class="page">
        <h1>Nastavnički panel</h1>

        <h2>Moje teme</h2>
        ${temeHtml}

        <h2>Odabiri učenika</h2>
        <div class="filters">
          <span class="muted sm">Tema:</span> ${temaFilter}
          <span class="muted sm">Status:</span> ${statusFilter}
        </div>
        ${odabiriHtml}
      </div>`);
  },
};
