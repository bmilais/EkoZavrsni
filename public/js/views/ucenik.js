/* View učenika (admin) - lista i forma, uvijek u kontekstu ciklusa */
const UcenikView = {
  list(data, ciklus) {
    const cid = ciklus.ID;
    Crud.list({
      bulk: { url: 'ucenici/obrisi-vise', noun: ['učenik', 'učenika', 'učenika'] },
      title: 'Učenici — ' + UI.esc(ciklus.NAZIV),
      subtitle: 'Ciklus: ' + UI.esc(ciklus.SKOLSKA_GODINA || ''),
      backRoute: '/admin/ciklusi',
      backLabel: 'Povratak na popis ciklusa',
      newBtn: { label: '+ Novi učenik', route: '/admin/ucenici/novi?ciklus_id=' + cid },
      extraToolbar: '<a class="btn secondary small" href="#/admin/teme?ciklus_id=' + cid + '">→ Teme ovog ciklusa</a> ' +
        '<a class="btn secondary small" href="#/admin/ucenici/uvoz?ciklus_id=' + cid + '">Uvoz iz Excela</a> ' +
        '<a class="btn secondary small" href="api/export/ucenici-excel?ciklus_id=' + cid + '">Export Excel</a> ' +
        '<a class="btn secondary small" href="api/export/ucenici-pdf?ciklus_id=' + cid + '">Export PDF</a> ' +
        '<button class="btn small" onclick="Pages.posaljiLinkSvima(' + cid + ')">Pošalji linkove</button>',
      columns: [
        { key: 'ID', label: 'ID' },
        { label: 'Ime i prezime', render: r => UI.esc(r.PREZIME + ' ' + r.IME) },
        { label: 'Email', render: r => UI.esc(r.EMAIL) },
        { label: 'Smjer', render: r => UI.esc(r.SMJER_LABEL) },
        { label: 'Razred', render: r => UI.esc(r.RAZRED_NAZIV) },
        { label: 'Link poslan', render: r => r.LINK_POSLAN ? View.fmtDate(r.LINK_POSLAN) : UI.badge('Nije poslan', 'gray') },
      ],
      load: async () => data,
      rowActions: row =>
        '<a class="btn link" href="#/admin/ucenici/uredi?id=' + row.ID + '&ciklus_id=' + cid + '">Uredi</a>' +
        '<button class="btn link" onclick="Pages.posaljiLink(' + row.ID + ')">Pošalji link</button>' +
        '<button class="btn link" onclick="Pages.uputePosaljiUceniku(' + Number(row.ID) + ', this)">Pošalji upute</button>' +
        '<button class="btn link danger" onclick="Crud.deleteItem({url:\'ucenici/obrisi\', id:' + row.ID + ', confirmText:\'Obrisati učenika?\', onClick:()=>window.Router.run()})">Obriši</button>',
    });
  },

  form(id, values, razredi, ciklusId) {
    Crud.form({
      title: id > 0 ? 'Uredi učenika' : 'Novi učenik',
      id, backRoute: '/admin/ucenici?ciklus_id=' + ciklusId,
      saveUrl: 'ucenici/spremi', values,
      hidden: { idciklus: ciklusId },
      options: { smjer: [{ value: 1, label: 'Ekonomist' }, { value: 2, label: 'Trgovac' }], idrazred: razredi },
      redirectAfter: '#/admin/ucenici?ciklus_id=' + ciklusId,
      fields: [
        { name: 'ime', label: 'Ime', type: 'text', required: true, size: 30 },
        { name: 'prezime', label: 'Prezime', type: 'text', required: true, size: 30 },
        { name: 'email', label: 'Email', type: 'email', required: true, size: 40 },
        { name: 'lozinka', label: 'Lozinka', type: 'password', autocomplete: 'new-password', small: id > 0 ? 'ostavi prazno da ostane ista' : '', size: 20 },
        { name: 'smjer', label: 'Smjer', type: 'select', required: true, placeholder: false },
        { name: 'idrazred', label: 'Razred', type: 'select', required: true },
      ],
    });
  },
};
