/* View profesora - lista i forma */
const ProfesorView = {
  list(data) {
    Crud.list({
      title: 'Profesori',
      newBtn: { label: '+ Novi profesor', route: '/admin/profesori/novi' },
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Ime', render: r => UI.esc(r.IME) },
        { label: 'Prezime', render: r => UI.esc(r.PREZIME) },
        { label: 'Email', render: r => UI.esc(r.EMAIL || '-') },
        { label: 'Uloga', render: r => UI.badge(r.ULOGA_LABEL, r.OVLASTI == 0 ? 'blue' : 'gray') },
      ],
      load: async () => data,
      rowActions: row =>
        '<a href="#/admin/profesori/uredi?id=' + row.ID + '">Uredi</a>' +
        ' | <button class="btn small danger" onclick="Crud.deleteItem({url:\'profesori/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati profesora?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values) {
    Crud.form({
      title: id > 0 ? 'Uredi profesora' : 'Novi profesor',
      id, backRoute: '/admin/profesori',
      saveUrl: 'profesori/spremi', values,
      options: { ovlasti: [{ value: 0, label: 'Admin' }, { value: 1, label: 'Nastavnik' }] },
      fields: [
        { name: 'ime', label: 'Ime', type: 'text', required: true, size: 30 },
        { name: 'prezime', label: 'Prezime', type: 'text', required: true, size: 30 },
        { name: 'email', label: 'Email', type: 'email', size: 40 },
        { name: 'lozinka', label: 'Lozinka', type: 'text', small: id > 0 ? 'ostavi prazno da ostane ista' : '', size: 20 },
        { name: 'ovlasti', label: 'Uloga', type: 'select', required: true, placeholder: false },
      ],
    });
  },
};
