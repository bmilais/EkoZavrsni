/* View tema - lista i forma */
const TemaView = {
  list(data) {
    Crud.list({
      title: 'Teme',
      newBtn: { label: '+ Nova tema', route: '/admin/teme/novi' },
      extraToolbar: '<a class="btn secondary small" href="#/admin/teme/uvoz">Uvoz iz Excela</a>',
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Naziv', render: r => UI.esc(r.NAZIV) },
        { label: 'Predmet', render: r => UI.esc(r.PREDMET_NAZIV) },
        { label: 'Profesor', render: r => UI.esc(r.PROFESOR_NAZIV) },
        { label: 'Razred', render: r => UI.esc(r.RAZRED_NAZIV) },
        { label: 'Ciklus', render: r => UI.esc(r.CIKLUS_NAZIV || '-') },
      ],
      load: async () => data,
      rowActions: row =>
        '<a href="#/admin/teme/uredi?id=' + row.ID + '">Uredi</a>' +
        ' | <button class="btn small danger" onclick="Crud.deleteItem({url:\'teme/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati temu?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values, opt) {
    const options = {
      idpredmeta: opt.predmeti.map(p => ({ value: p.ID, label: p.NAZIV })),
      idprofesora: opt.profesori.map(p => ({ value: p.ID, label: p.PREZIME + ' ' + p.IME })),
      idrazred: opt.razredi.map(r => ({ value: r.ID, label: r.NAZIV })),
      idciklusa: opt.ciklusi.map(c => ({ value: c.ID, label: c.NAZIV })),
    };

    Crud.form({
      title: id > 0 ? 'Uredi temu' : 'Nova tema',
      id, backRoute: '/admin/teme',
      saveUrl: 'teme/spremi', values, options,
      fields: [
        { name: 'naziv', label: 'Naziv teme', type: 'text', required: true, size: 80 },
        { name: 'idpredmeta', label: 'Predmet', type: 'select', required: true },
        { name: 'idprofesora', label: 'Profesor (mentor)', type: 'select', required: true },
        { name: 'idrazred', label: 'Razred', type: 'select', required: true },
        { name: 'idciklusa', label: 'Ciklus', type: 'select', placeholder: '-- Nije vezano uz ciklus --' },
      ],
    });
  },
};
