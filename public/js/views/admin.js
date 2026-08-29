/* View admin dashboarda - kartice za brzi pristup modulima */
const AdminView = {
  render(u) {
    const init = ((u.ime ? u.ime[0] : '') + (u.prezime ? u.prezime[0] : '')).toUpperCase();

    const cards = [
      ['📅', '/admin/ciklusi', 'Ciklusi', 'Otvori/zatvori ciklus odabira'],
      ['🏫', '/admin/razredi', 'Razredi', 'Upravljaj razredima'],
      ['📚', '/admin/predmeti', 'Predmeti', 'Popis predmeta i limiti'],
      ['👩‍🏫', '/admin/profesori', 'Profesori', 'Nastavnici i njihovi računi'],
      ['🎓', '/admin/ucenici', 'Učenici', 'Popis učenika i linkovi za prijavu'],
      ['🗂️', '/admin/teme', 'Teme', 'Završne teme i mentori'],
      ['✅', '/admin/odabiri', 'Odabiri', 'Pregled i poništavanje odabira'],
      ['📊', '/admin/stanje', 'Stanje', 'Pregled cjelokupnog stanja'],
    ];

    const grid = cards.map(([ico, href, title, desc]) =>
      '<a class="dash-card" href="#' + href + '">' +
      '<div class="ico">' + ico + '</div>' +
      '<b>' + title + '</b>' +
      '<span>' + desc + '</span>' +
      '</a>'
    ).join('');

    View.set(`
      <div class="page">
        <div class="flex">
          <span class="avatar" style="width:52px;height:52px;font-size:1.4rem;background:linear-gradient(135deg,var(--green-100),var(--green-200));color:var(--green-800);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-weight:700">${UI.esc(init)}</span>
          <div style="margin-left:14px">
            <h1 style="margin-bottom:2px">Admin panel</h1>
            <p class="muted" style="margin:0">Pozdrav, ${UI.esc(u.ime + ' ' + u.prezime)}. Odaberite modul ispod za upravljanje.</p>
          </div>
        </div>
        <div class="dash-grid">${grid}</div>
      </div>`);
  },
};
