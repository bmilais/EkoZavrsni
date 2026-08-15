/* View učenikovog panela - moje teme, odabir i otkazivanje */
const UcenikPanelView = {
  render(moji, dostupne, ciklus) {
    const aktivni = moji.find(o => o.STATUS === 'aktivan');
    const povijest = moji.filter(o => o.STATUS !== 'aktivan');

    let aktivniHtml = '<p class="muted">Trenutno nemate odabranu temu.</p>';
    if (aktivni) {
      aktivniHtml =
        '<div class="card">' +
        '<p><strong>' + UI.esc(aktivni.TEMA_NAZIV) + '</strong></p>' +
        '<p class="muted">' + UI.esc(aktivni.PREDMET_NAZIV) + ' — mentor: ' + UI.esc(aktivni.PROFESOR_NAZIV) + '</p>' +
        '<p class="muted">Odabrano: ' + View.fmtDate(aktivni.DATUM) + '</p>' +
        '<button class="btn danger small" onclick="Pages.ucenikPonisti(' + aktivni.OID + ', \'' + encodeURIComponent(aktivni.TEMA_NAZIV) + '\')">Poništi odabir</button>' +
        '</div>';
    }

    let povijestHtml = '';
    if (povijest.length) {
      povijestHtml = '<h3>Povijest odabira</h3>' + UI.table(
        ['Tema', 'Predmet', 'Mentor', 'Odabrano', 'Poništeno', 'Razlog'],
        povijest.map(o => [
          UI.esc(o.TEMA_NAZIV),
          UI.esc(o.PREDMET_NAZIV),
          UI.esc(o.PROFESOR_NAZIV),
          View.fmtDate(o.DATUM),
          View.fmtDate(o.DATUM_PONISTENJA),
          UI.esc(o.OBRAZLOZENJE || '-'),
        ])
      );
    }

    let temeHtml = '';
    if (!ciklus) {
      temeHtml = '<p class="muted">Trenutno nema otvorenog ciklusa za odabir tema.</p>';
    } else if (!dostupne.length) {
      temeHtml = '<p class="muted">Nema dostupnih tema za vaš razred (ili ste sve već odabrali).</p>';
    } else {
      temeHtml = UI.table(
        ['Tema', 'Predmet', 'Mentor', 'Predmet (popunjenost)', 'Mentor (popunjenost)', 'Odaberi'],
        dostupne.map(t => {
          const pun = t.PREDMET_LIMIT !== null && parseInt(t.P_BR, 10) >= parseInt(t.PREDMET_LIMIT, 10);
          const punMentor = t.M_MAX !== null && parseInt(t.M_BR, 10) >= parseInt(t.M_MAX, 10);

          let akcija;
          if (aktivni) {
            akcija = '<span class="muted">Već imate odabranu temu</span>';
          } else if (pun) {
            akcija = '<span style="color:#c62828">Predmet pun</span>';
          } else if (punMentor) {
            akcija = '<span style="color:#c62828">Mentor pun</span>';
          } else {
            akcija = '<button class="btn small" onclick="Pages.ucenikOdaberi(' + t.ID + ', \'' + encodeURIComponent(t.NAZIV) + '\')">Odaberi</button>';
          }

          return [
            UI.esc(t.NAZIV),
            UI.esc(t.PREDMET_NAZIV),
            UI.esc(t.PROFESOR_NAZIV),
            t.PREDMET_LIMIT !== null ? t.P_BR + ' / ' + t.PREDMET_LIMIT : '-',
            t.M_MAX !== null ? t.M_BR + ' / ' + t.M_MAX : '-',
            akcija,
          ];
        })
      );
    }

    View.set(`
      <div class="page">
        <h1>Moje teme</h1>
        <p class="muted">Ciklus: ${ciklus ? UI.esc(ciklus.NAZIV) : '-'}</p>

        <h2>Moj odabir</h2>
        ${aktivniHtml}

        <h2>Dostupne teme</h2>
        ${temeHtml}

        ${povijestHtml}
      </div>`);
  },
};
