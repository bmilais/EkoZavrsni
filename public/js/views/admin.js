/* View admin dashboarda - odabir ciklusa + kartice za brzi pristup modulima */
const AdminView = {
  render(u, ciklusi, selected) {
    const init = ((u.ime ? u.ime[0] : '') + (u.prezime ? u.prezime[0] : '')).toUpperCase();
    const statusLabels = { priprema: 'Priprema', otvoreno: 'Otvoreno', zakljucano: 'Zaključano', arhivirano: 'Arhivirano' };
    const statusColors = { priprema: 'gray', otvoreno: 'green', zakljucano: 'blue', arhivirano: 'gray' };

    let ciklusHtml;
    if (selected) {
      ciklusHtml = `
        <div class="hint-card flex" style="justify-content:space-between;flex-wrap:wrap">
          <div>
            <strong>Trenutno odabran ciklus:</strong> ${UI.esc(selected.NAZIV)}
            ${UI.badge(statusLabels[selected.STATUS] || selected.STATUS, statusColors[selected.STATUS] || 'gray')}
            <span class="muted"> — ${UI.esc(selected.SKOLSKA_GODINA || '')}</span>
          </div>
          <button class="btn secondary small" onclick="Pages.adminPromijeniCiklus()">Promijeni ciklus</button>
        </div>`;
    } else if (ciklusi.length) {
      const opcije = ciklusi.map(c =>
        '<option value="' + c.ID + '">' + UI.esc(c.NAZIV) + ' (' + UI.esc(c.SKOLSKA_GODINA) + ')</option>'
      ).join('');
      ciklusHtml = `
        <div class="hint-card">
          <p><strong>Nije odabran nijedan ciklus.</strong> Odaberite ciklus da biste pristupili učenicima, temama, odabirima i stanju tog ciklusa.</p>
          <div class="flex">
            <select id="admin-ciklus-select">${opcije}</select>
            <button class="btn small" onclick="Pages.adminOdaberiCiklus(document.getElementById('admin-ciklus-select').value)">Odaberi ciklus</button>
            <a class="btn secondary small" href="#/admin/ciklusi">Upravljaj ciklusima</a>
          </div>
        </div>`;
    } else {
      ciklusHtml = `
        <div class="hint-card">
          <p><strong>Još nema stvorenih ciklusa.</strong> <a href="#/admin/ciklusi/novi">Stvorite prvi ciklus</a> da biste mogli upravljati učenicima, temama, odabirima i stanjem.</p>
        </div>`;
    }

    const cid = selected ? selected.ID : null;

    const stalneKartice = [
      ['🏫', '/admin/razredi', 'Razredi', 'Upravljaj razredima'],
      ['📚', '/admin/predmeti', 'Predmeti', 'Popis predmeta i limiti'],
      ['👩‍🏫', '/admin/profesori', 'Profesori', 'Nastavnici i njihovi računi'],
      ['📅', '/admin/ciklusi', 'Ciklusi', 'Stvori, uredi i otvori/zatvori ciklus'],
    ];

    const ciklusKartice = [
      ['🎓', cid ? '/admin/ucenici?ciklus_id=' + cid : null, 'Učenici', 'Popis učenika odabranog ciklusa'],
      ['🗂️', cid ? '/admin/teme?ciklus_id=' + cid : null, 'Teme', 'Završne teme i mentori odabranog ciklusa'],
      ['✅', cid ? '/admin/odabiri?ciklus_id=' + cid : null, 'Odabiri', 'Pregled i poništavanje odabira odabranog ciklusa'],
      ['📊', cid ? '/admin/stanje?ciklus_id=' + cid : null, 'Stanje', 'Zauzetost tema i mentora odabranog ciklusa'],
    ];

    const karticaHtml = ([ico, href, title, desc]) => {
      if (!href) {
        return '<div class="dash-card" style="opacity:.5;cursor:not-allowed" title="Prvo odaberite ciklus">' +
          '<div class="ico">' + ico + '</div><b>' + title + '</b><span>' + desc + '</span></div>';
      }
      return '<a class="dash-card" href="#' + href + '">' +
        '<div class="ico">' + ico + '</div><b>' + title + '</b><span>' + desc + '</span></a>';
    };

    const grid = stalneKartice.map(karticaHtml).join('') + ciklusKartice.map(karticaHtml).join('');

    View.set(`
      <div class="page">
        <div class="flex">
          <span class="avatar" style="width:52px;height:52px;font-size:1.4rem;background:linear-gradient(135deg,var(--green-100),var(--green-200));color:var(--green-800);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-weight:700">${UI.esc(init)}</span>
          <div style="margin-left:14px">
            <h1 style="margin-bottom:2px">Admin panel</h1>
            <p class="muted" style="margin:0">Pozdrav, ${UI.esc(u.ime + ' ' + u.prezime)}.</p>
          </div>
        </div>
        ${ciklusHtml}
        <div class="dash-grid">${grid}</div>
      </div>`);
  },
};
