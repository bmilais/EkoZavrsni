/* View ciklusa - lista i forma */
const CiklusView = {
  list(data) {
    Crud.list({
      title: 'Ciklusi',
      newBtn: { label: '+ Novi ciklus', route: '/admin/ciklusi/novi' },
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Naziv', render: r => UI.esc(r.NAZIV) },
        { label: 'Školska godina', render: r => UI.esc(r.SKOLSKA_GODINA) },
        { label: 'Status', render: r => UI.badge(r.STATUS_LABEL, { priprema: 'gray', otvoreno: 'green', zakljucano: 'blue', arhivirano: 'gray' }[r.STATUS] || 'gray') },
        { label: 'Otvaranje', render: r => View.fmtDate(r.DATUM_OTVARANJA) },
        { label: 'Zatvaranje', render: r => View.fmtDate(r.DATUM_ZATVARANJA) },
        { label: 'Max učenika/mentoru', render: r => r.MAX_UCENIKA_PO_MENTORU ?? '-' },
        { label: 'Max tema/predmetu', render: r => r.MAX_TEMA_PO_PREDMETU ?? '-' },
      ],
      load: async () => data,
      rowActions: row => this.akcije(row),
    });
  },

  akcije(row) {
    const labels = { priprema: 'Priprema', otvoreno: 'Otvoreno', zakljucano: 'Zaključano', arhivirano: 'Arhivirano' };
    let h = '<a href="#/admin/ciklusi/uredi?id=' + row.ID + '">Uredi</a>';
    (row.MOGUCI_STATUSI || []).forEach(s => {
      h += ' | <button class="btn small secondary" onclick="Pages.ciklusStatus(' + row.ID + ', \'' + s + '\')">' + labels[s] + '</button>';
    });
    h += ' | <button class="btn small danger" onclick="Crud.deleteItem({url:\'ciklusi/obrisi\', id:' + row.ID + ', title:\'Brisanje ciklusa\', confirmText:\'Obrisati ciklus?\', onClick:()=>window.Router.run()})">Obriši</button>';
    return h;
  },

  form(id, values) {
    Crud.form({
      title: id > 0 ? 'Uredi ciklus' : 'Novi ciklus',
      id, backRoute: '/admin/ciklusi',
      saveUrl: 'ciklusi/spremi', values,
      fields: [
        { name: 'naziv', label: 'Naziv ciklusa', type: 'text', required: true, size: 60 },
        { name: 'skolska_godina', label: 'Školska godina', type: 'text', required: true, small: 'npr. 2025/2026' },
        { name: 'max_ucenika_po_mentoru', label: 'Max učenika po mentoru', type: 'number', small: 'opcionalno' },
        { name: 'max_tema_po_predmetu', label: 'Max tema po predmetu', type: 'number', small: 'opcionalno' },
        { name: 'upute_pdf_url', label: 'URL PDF uputa', type: 'url', small: 'opcionalno', size: 80 },
      ],
    });
  },
};
