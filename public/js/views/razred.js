/* View razreda - lista i forma */
const RazredView = {
  list(data) {
    Crud.list({
      title: 'Razredi',
      newBtn: { label: '+ Novi razred', route: '/admin/razredi/novi' },
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Naziv', render: r => UI.esc(r.NAZIV) },
      ],
      load: async () => data,
      rowActions: row =>
        '<a href="#/admin/razredi/uredi?id=' + row.ID + '">Uredi</a>' +
        ' | <button class="btn small danger" onclick="Crud.deleteItem({url:\'razredi/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati razred?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values) {
    Crud.form({
      title: id > 0 ? 'Uredi razred' : 'Novi razred',
      id, backRoute: '/admin/razredi',
      saveUrl: 'razredi/spremi', values,
      fields: [{ name: 'naziv', label: 'Naziv razreda', type: 'text', required: true, size: 30 }],
    });
  },
};
