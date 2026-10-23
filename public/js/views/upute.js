/* View panela "Upute" - PDF upute za učenike odabranog ciklusa */
const UputeView = {
  velicina(bajtova) {
    const n = Number(bajtova) || 0;
    if (n >= 1024 * 1024) return (n / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
    return Math.max(1, Math.round(n / 1024)) + ' kB';
  },

  render(ciklus, info) {
    const cid = ciklus.ID;
    const maxMb = Math.round((Number(info.MAX_VELICINA) || 0) / 1024 / 1024);

    const pdfHtml = info.POSTOJI
      ? '<div class="card">' +
          '<p><strong>' + UI.esc(info.NAZIV) + '</strong></p>' +
          '<p class="muted">' + this.velicina(info.VELICINA) + ' · učitano ' + View.fmtDate(info.DATUM) + '</p>' +
          '<a class="btn secondary small" href="' + UputeModel.preuzmiUrl(cid) + '" target="_blank" rel="noopener">Otvori PDF</a>' +
        '</div>'
      : '<p class="muted">Za ovaj ciklus još nije učitan PDF s uputama.</p>';

    const neposlano = info.UKUPNO - info.POSLANO;
    let slanjeHtml;
    if (!info.POSTOJI) {
      slanjeHtml = '<p class="muted">Slanje je moguće kad učitate PDF.</p>';
    } else if (info.UKUPNO === 0) {
      slanjeHtml = '<p class="muted">U ovom ciklusu još nema učenika.</p>';
    } else {
      slanjeHtml =
        '<p>Upute je dobilo <strong>' + info.POSLANO + '</strong> od <strong>' + info.UKUPNO + '</strong> učenika' +
        (neposlano > 0 ? ' (' + neposlano + ' ih još nije dobilo).' : '.') + '</p>' +
        (info.STARA_VERZIJA > 0
          ? '<p class="hint-card">PDF je zamijenjen nakon slanja: <strong>' + info.STARA_VERZIJA +
            '</strong> učenika ima stariju verziju. Da dobiju novu, pri slanju označite „Pošalji ponovno svima”.</p>'
          : '') +
        '<button class="btn" onclick="Pages.uputePosalji(' + Number(cid) + ')">Pošalji upute učenicima</button>' +
        '<p class="muted">Pojedinom učeniku upute možete poslati gumbom „Pošalji” u tablici ispod.</p>';
    }

    // Slanje jednom učeniku - samo kad je PDF učitan.
    const akcija = (u) => !info.POSTOJI ? '' :
      '<button class="btn link" onclick="Pages.uputePosaljiUceniku(' + Number(u.ID) + ', this)">' +
      (u.UPUTE_POSLANE ? 'Pošalji ponovno' : 'Pošalji') + '</button>';

    const tablica = UI.table(
      ['Učenik', 'Email', 'Upute poslane', 'Akcije'],
      (info.UCENICI || []).map(u => [
        UI.esc(u.PREZIME + ' ' + u.IME),
        UI.esc(u.EMAIL),
        u.UPUTE_POSLANE
          ? View.fmtDate(u.UPUTE_POSLANE) + (Number(u.STARA_VERZIJA) === 1 ? ' ' + UI.badge('starija verzija', 'orange') : '')
          : UI.badge('Nisu poslane', 'gray'),
        akcija(u),
      ])
    );

    View.set(`
      <div class="page">
        <p class="back-link"><a href="#/admin">&larr; Povratak na admin panel</a></p>
        <h1>Upute za učenike — ${UI.esc(ciklus.NAZIV)}</h1>
        <p class="muted">PDF s uputama šalje se učenicima kao privitak, odvojeno od maila s linkom za prijavu.</p>

        <h2>PDF s uputama</h2>
        ${pdfHtml}

        <form class="form" id="upute-form">
          <input type="hidden" name="ciklus_id" value="${Number(cid)}">
          <div class="field">
            <label>${info.POSTOJI ? 'Zamijeni PDF' : 'Učitaj PDF'} <small>najviše ${maxMb} MB${info.POSTOJI ? ', novi PDF zamjenjuje postojeći' : ''}</small></label>
            <input type="file" name="pdf" accept="application/pdf,.pdf" required>
          </div>
          <button type="submit" class="btn" id="upute-upload-btn">${info.POSTOJI ? 'Zamijeni PDF' : 'Učitaj PDF'}</button>
        </form>

        <h2>Slanje učenicima</h2>
        ${slanjeHtml}

        <h3>Učenici ciklusa</h3>
        ${tablica}
      </div>`);
  },

  attachUpload(maxVelicina, onUpload) {
    document.getElementById('upute-form').onsubmit = async (e) => {
      e.preventDefault();
      const input = e.target.querySelector('input[type=file]');
      const datoteka = input.files && input.files[0];
      if (!datoteka) { UI.toast('Odaberite PDF datoteku.', 'error'); return; }
      if (!/\.pdf$/i.test(datoteka.name)) { UI.toast('Datoteka mora biti PDF.', 'error'); return; }
      if (maxVelicina && datoteka.size > maxVelicina) {
        UI.toast('Datoteka je prevelika (' + this.velicina(datoteka.size) + ', najviše ' + this.velicina(maxVelicina) + ').', 'error');
        return;
      }

      const btn = document.getElementById('upute-upload-btn');
      btn.disabled = true;
      try {
        await onUpload(new FormData(e.target));
      } catch (err) {
        btn.disabled = false;
        UI.toast(err.message, 'error', 8000);
      }
    };
  },
};
