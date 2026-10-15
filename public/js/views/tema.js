/* View tema - lista i forma, uvijek u kontekstu ciklusa */
const TemaView = {
  list(data, ciklus) {
    const cid = ciklus.ID;
    Crud.list({
      bulk: { url: 'teme/obrisi-vise', noun: ['tema', 'teme', 'tema'] },
      title: 'Teme — ' + UI.esc(ciklus.NAZIV),
      subtitle: 'Ciklus: ' + UI.esc(ciklus.SKOLSKA_GODINA || ''),
      backRoute: '/admin/ciklusi',
      backLabel: 'Povratak na popis ciklusa',
      newBtn: { label: '+ Nova tema', route: '/admin/teme/novi?ciklus_id=' + cid },
      extraToolbar: '<a class="btn secondary small" href="#/admin/ucenici?ciklus_id=' + cid + '">→ Učenici ovog ciklusa</a> ' +
        '<a class="btn secondary small" href="#/admin/teme/uvoz?ciklus_id=' + cid + '">Uvoz iz Excela</a> ' +
        '<a class="btn secondary small" href="api/export/teme-excel?ciklus_id=' + cid + '">Export Excel</a> ' +
        '<a class="btn secondary small" href="api/export/teme-pdf?ciklus_id=' + cid + '">Export PDF</a>',
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Naziv', render: r => UI.esc(r.NAZIV) },
        { label: 'Predmet', render: r => UI.esc(r.PREDMET_NAZIV) },
        { label: 'Profesor', render: r => UI.esc(r.PROFESOR_NAZIV) },
        { label: 'Razred', render: r => UI.esc(r.RAZRED_NAZIV) },
      ],
      load: async () => data,
      rowActions: row =>
        '<a class="btn link" href="#/admin/teme/uredi?id=' + row.ID + '&ciklus_id=' + cid + '">Uredi</a>' +
        '<button class="btn link danger" onclick="Crud.deleteItem({url:\'teme/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati temu?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values, opt, ciklusId) {
    const options = {
      idpredmeta: opt.predmeti.map(p => ({ value: p.ID, label: p.NAZIV })),
      idprofesora: opt.profesori.map(p => ({ value: p.ID, label: p.PREZIME + ' ' + p.IME })),
      idrazred: opt.razredi.map(r => ({ value: r.ID, label: r.NAZIV })),
    };

    if (!values.idciklusa) values.idciklusa = ciklusId;

    Crud.form({
      title: id > 0 ? 'Uredi temu' : 'Nova tema',
      id, backRoute: '/admin/teme?ciklus_id=' + ciklusId,
      saveUrl: 'teme/spremi', values, options,
      redirectAfter: '#/admin/teme?ciklus_id=' + ciklusId,
      fields: [
        { name: 'naziv', label: 'Naziv teme', type: 'text', required: true, size: 80 },
        { name: 'idpredmeta', label: 'Predmet', type: 'select', required: true },
        { name: 'idprofesora', label: 'Profesor (mentor)', type: 'select', required: true },
        { name: 'idrazred', label: 'Razred', type: 'select', required: true },
        { name: 'idciklusa', label: 'Ciklus', type: 'select', required: true, placeholder: false, options: [{ value: ciklusId, label: opt.ciklusNaziv || ('Ciklus #' + ciklusId) }] },
      ],
    });
  },
};
