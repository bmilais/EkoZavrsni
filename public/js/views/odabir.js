/* View odabira (admin) - lista i modal za poništavanje */
const OdabirView = {
  list(rows, filter) {
    const status = (o) => o.STATUS === 'aktivan'
      ? UI.badge('Aktivan', 'green')
      : UI.badge('Poništen', 'red');

    let akcije = '';
    const data = rows.map(o => {
      if (o.STATUS === 'aktivan') {
        akcije = '<button class="btn small danger" onclick="Pages.odabiriPonisti(' + o.OID + ', \'' +
          encodeURIComponent(o.U_PREZIME + ' ' + o.U_IME) + '\', \'' +
          encodeURIComponent(o.TEMA_NAZIV) + '\')">Poništi</button>';
      } else {
        akcije = UI.esc(o.OBRAZLOZENJE || '-');
      }
      return [
        o.OID,
        UI.esc(o.U_PREZIME + ' ' + o.U_IME),
        UI.esc(o.U_EMAIL),
        UI.esc(o.TEMA_NAZIV),
        UI.esc(o.PREDMET_NAZIV),
        UI.esc(o.PROFESOR_NAZIV),
        View.fmtDate(o.DATUM_ODABIRA),
        status(o),
        akcije,
      ];
    });

    const filt = (f, l) =>
      f === filter ? '<strong>' + l + '</strong>' : '<a href="#/admin/odabiri?filter=' + f + '">' + l + '</a>';

    const toolbar =
      '<div>' +
      filt('svi', 'Svi') + ' | ' + filt('aktivni', 'Aktivni') + ' | ' + filt('ponisteni', 'Poništeni') +
      '</div>' +
      '<div><a class="btn secondary small" href="api/export/odabiri-excel">Export Excel</a> ' +
      '<a class="btn secondary small" href="api/export/odabiri-pdf">Export PDF</a></div>';

    View.set(`
      <div class="page">
        <h1>Odabiri tema</h1>
        <div class="page-toolbar">${toolbar}</div>
        ${UI.table(['ID', 'Učenik', 'Email', 'Tema', 'Predmet', 'Profesor', 'Datum', 'Status', 'Akcije'], data)}
        <p class="mt"><a href="#/admin">&larr; Povratak na admin panel</a></p>
      </div>`);
  },

  ponistiModal(ucenik, tema, onConfirm) {
    const body =
      '<p>Poništavate odabir učenika <strong>' + UI.esc(ucenik) + '</strong> za temu <strong>' + UI.esc(tema) + '</strong>.</p>' +
      '<div class="field"><label>Obrazloženje poništenja (obavezno):</label>' +
      '<textarea id="obrazlozenje" rows="4" cols="50" required></textarea></div>';
    const foot =
      '<button class="btn secondary" onclick="UI.closeModal()">Odustani</button>' +
      '<button class="btn danger" id="modal-confirm">Poništi odabir</button>';

    UI.modal({ title: 'Poništi odabir', body, foot });

    document.getElementById('modal-confirm').onclick = async () => {
      const txt = document.getElementById('obrazlozenje').value.trim();
      if (!txt) { UI.toast('Obrazloženje je obavezno.', 'error'); return; }
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
