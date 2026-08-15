/* View predmeta - lista i forma */
const PredmetView = {
  list(data) {
    Crud.list({
      title: 'Predmeti',
      newBtn: { label: '+ Novi predmet', route: '/admin/predmeti/novi' },
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Naziv', render: r => UI.esc(r.NAZIV) },
        { label: 'Smjer', render: r => UI.esc(r.SMJER_LABEL) },
        { label: 'Razred', render: r => r.RAZRED ? r.RAZRED + '. razred' : '-' },
        { label: 'Limit', render: r => r.LIMIT ?? '-' },
      ],
      load: async () => data,
      rowActions: row =>
        '<a href="#/admin/predmeti/uredi?id=' + row.ID + '">Uredi</a>' +
        ' | <button class="btn small danger" onclick="Crud.deleteItem({url:\'predmeti/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati predmet?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values) {
    const razredi = [];
    for (let i = 1; i <= 5; i++) razredi.push({ value: i, label: i + '. razred' });
    Crud.form({
      title: id > 0 ? 'Uredi predmet' : 'Novi predmet',
      id, backRoute: '/admin/predmeti',
      saveUrl: 'predmeti/spremi', values,
      options: { smjer: [{ value: 1, label: 'Ekonomist' }, { value: 2, label: 'Trgovac' }], razred: razredi },
      fields: [
        { name: 'naziv', label: 'Naziv predmeta', type: 'text', required: true, size: 60 },
        { name: 'smjer', label: 'Smjer', type: 'select', required: true, placeholder: false },
        { name: 'razred', label: 'Razred', type: 'select', placeholder: '-- Nije vezano uz razred --' },
        { name: 'limit', label: 'Limit (max odabira)', type: 'number', small: 'opcionalno' },
      ],
    });
  },
};
