/* View učenikovog panela - moje teme, odabir i otkazivanje */
const UcenikPanelView = {
  render(moji, dostupne, ciklus) {
    const cikId = ciklus ? Number(ciklus.ID) : null;
    const aktivni = moji.find(o => o.STATUS === 'aktivan' && (cikId === null || Number(o.IDCIKLUSA) === cikId));
    const povijest = moji.filter(o => o.OID !== (aktivni ? aktivni.OID : null));

    let aktivniHtml = '<p class="muted">Trenutno nemate odabranu temu.</p>';
    if (aktivni) {
      const mozePonistiti = cikId !== null && Number(aktivni.IDCIKLUSA) === cikId;
      // Naziv teme ide u data- atribut (escapiran s UI.esc) i čita se kroz
      // this.dataset, pa apostrof ili JS u nazivu ne mogu prekinuti onclick.
      aktivniHtml =
        '<div class="card">' +
        '<p><strong>' + UI.esc(aktivni.TEMA_NAZIV) + '</strong></p>' +
        '<p class="muted">' + UI.esc(aktivni.PREDMET_NAZIV) + ' — mentor: ' + UI.esc(aktivni.PROFESOR_NAZIV) + '</p>' +
        '<p class="muted">Odabrano: ' + View.fmtDate(aktivni.DATUM) + '</p>' +
        (mozePonistiti
          ? '<button class="btn danger small" data-naziv="' + UI.esc(aktivni.TEMA_NAZIV) + '"' +
            ' onclick="Pages.ucenikPonisti(' + Number(aktivni.OID) + ', this.dataset.naziv)">Poništi odabir</button>'
          : '<p class="muted">Ciklus je zaključan — odabir je konačan. Za promjenu se obratite administratoru.</p>') +
        '</div>';
    }

    let povijestHtml = '';
    if (povijest.length) {
      povijestHtml = '<h3>Povijest odabira</h3>' + UI.table(
        ['Tema', 'Predmet', 'Mentor', 'Odabrano', 'Status', 'Poništeno', 'Razlog'],
        povijest.map(o => [
          UI.esc(o.TEMA_NAZIV),
          UI.esc(o.PREDMET_NAZIV),
          UI.esc(o.PROFESOR_NAZIV),
          View.fmtDate(o.DATUM),
          o.STATUS === 'aktivan' ? UI.badge('Prošli ciklus', 'gray') : UI.badge('Poništen', 'red'),
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
            akcija = UI.badge('Predmet pun', 'red');
          } else if (punMentor) {
            akcija = UI.badge('Mentor pun', 'orange');
          } else {
            akcija = '<button class="btn small" data-naziv="' + UI.esc(t.NAZIV) + '"' +
              ' onclick="Pages.ucenikOdaberi(' + Number(t.ID) + ', this.dataset.naziv)">Odaberi</button>';
          }

          const pop = (br, lim) => {
            if (lim === null) return '-';
            const puno = parseInt(br, 10) >= parseInt(lim, 10);
            return (puno ? UI.badge(br + ' / ' + lim, 'orange') : br + ' / ' + lim);
          };

          return [
            UI.esc(t.NAZIV),
            UI.esc(t.PREDMET_NAZIV),
            UI.esc(t.PROFESOR_NAZIV),
            pop(t.P_BR, t.PREDMET_LIMIT),
            pop(t.M_BR, t.M_MAX),
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

  ponistiModal(tema, onConfirm) {
    const body =
      '<p>Poništavate odabir teme <strong>' + UI.esc(tema) + '</strong>.</p>' +
      '<div class="field"><label>Razlog poništenja (obavezno):</label>' +
      '<textarea id="razlog-ponistenja" rows="4" cols="50" required></textarea></div>';
    const foot =
      '<button class="btn secondary" onclick="UI.closeModal()">Odustani</button>' +
      '<button class="btn danger" id="modal-confirm">Poništi odabir</button>';

    UI.modal({ title: 'Poništi odabir', body, foot });

    document.getElementById('modal-confirm').onclick = async () => {
      const txt = document.getElementById('razlog-ponistenja').value.trim();
      if (!txt) { UI.toast('Razlog poništenja je obavezan.', 'error'); return; }
      const btn = document.getElementById('modal-confirm');
      btn.disabled = true;
      try {
        await onConfirm(txt);
        UI.closeModal();
      } catch (e) {
        btn.disabled = false;
        UI.toast(e.message, 'error');
      }
    };
  },
};
