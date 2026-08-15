/* View učenika (admin) - lista i forma */
const UcenikView = {
  list(data) {
    Crud.list({
      title: 'Učenici',
      newBtn: { label: '+ Novi učenik', route: '/admin/ucenici/novi' },
      extraToolbar: '<a class="btn secondary small" href="#/admin/ucenici/uvoz">Uvoz iz Excela</a>',
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Ime i prezime', render: r => UI.esc(r.PREZIME + ' ' + r.IME) },
        { label: 'Email', render: r => UI.esc(r.EMAIL) },
        { label: 'Smjer', render: r => UI.esc(r.SMJER_LABEL) },
        { label: 'Razred', render: r => UI.esc(r.RAZRED_NAZIV) },
      ],
      load: async () => data,
      rowActions: row =>
        '<a href="#/admin/ucenici/uredi?id=' + row.ID + '">Uredi</a>' +
        ' | <button class="btn small" onclick="Pages.posaljiLink(' + row.ID + ')">Pošalji link</button>' +
        ' | <button class="btn small danger" onclick="Crud.deleteItem({url:\'ucenici/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati učenika?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values, razredi) {
    Crud.form({
      title: id > 0 ? 'Uredi učenika' : 'Novi učenik',
      id, backRoute: '/admin/ucenici',
      saveUrl: 'ucenici/spremi', values,
      options: { smjer: [{ value: 1, label: 'Ekonomist' }, { value: 2, label: 'Trgovac' }], idrazred: razredi },
      fields: [
        { name: 'ime', label: 'Ime', type: 'text', required: true, size: 30 },
        { name: 'prezime', label: 'Prezime', type: 'text', required: true, size: 30 },
        { name: 'email', label: 'Email', type: 'email', required: true, size: 40 },
        { name: 'lozinka', label: 'Lozinka', type: 'text', small: id > 0 ? 'ostavi prazno da ostane ista' : '', size: 20 },
        { name: 'smjer', label: 'Smjer', type: 'select', required: true, placeholder: false },
        { name: 'idrazred', label: 'Razred', type: 'select', required: true },
      ],
    });
  },
};
