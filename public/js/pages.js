/* Controlleri - spajaju modele (podaci) i view-ove (prikaz) */
const Pages = {

  // ---------- auth ----------
  async login() {
    if (window.App.user) {
      location.hash = '#/' + window.App.user.uloga;
      return;
    }
    LoginView.render();
    document.getElementById('login-form').onsubmit = async (e) => {
      e.preventDefault();
      const btn = e.target.querySelector('button');
      btn.disabled = true;
      try {
        const data = await AuthModel.login(UI.formDataFrom(e.target));
        window.App.user = data.user;
        UI.toast('Dobrodošli!', 'success');
        location.hash = '#/' + data.user.uloga;
      } catch (err) {
        btn.disabled = false;
        UI.toast(err.message, 'error');
      }
    };
  },

  async prijava(ctx) {
    const token = ctx.params.get('token') || '';
    if (!token) {
      LoginView.missing();
      return;
    }
    try {
      const data = await AuthModel.prijava(token);
      window.App.user = data.user;
      window.Router.navigate('#/ucenik');
    } catch (e) {
      LoginView.error(e.message);
    }
  },

  // ---------- dashboardi ----------
  admin() {
    AdminView.render(window.App.user);
  },

  nastavnik() {
    const u = window.App.user;
    if (!u || u.uloga !== 'nastavnik') return;
    View.set(`
      <div class="page">
        <h1>Nastavnički panel</h1>
        <p class="muted">Pozdrav, ${UI.esc(u.ime + ' ' + u.prezime)}. Učitavam podatke...</p>
      </div>`);
    this.nastavnikLoad(0, 'aktivni');
  },

  async nastavnikLoad(temaId, filter) {
    this._nastavnikTema = temaId;
    this._nastavnikFilter = filter;
    try {
      const [teme, odabiri] = await Promise.all([
        NastavnikModel.teme(),
        NastavnikModel.odabiri(temaId, filter),
      ]);
      NastavnikView.render(teme.data || [], odabiri.data || [], temaId, filter);
    } catch (e) { View.error(e); }
  },

  nastavnikFilterTema(temaId) {
    this.nastavnikLoad(parseInt(temaId, 10), this._nastavnikFilter || 'aktivni');
  },

  nastavnikFilterStatus(filter) {
    this.nastavnikLoad(this._nastavnikTema || 0, filter);
  },

  ucenik() {
    const u = window.App.user;
    if (!u || u.uloga !== 'ucenik') return;
    View.set(`
      <div class="page">
        <h1>Učenički panel</h1>
        <p class="muted">Pozdrav, ${UI.esc(u.ime + ' ' + u.prezime)}. Učitavam podatke...</p>
      </div>`);

    (async () => {
      try {
        const [moji, dostupne] = await Promise.all([
          OdabirModel.moji(),
          OdabirModel.dostupne(),
        ]);
        UcenikPanelView.render(moji.data || [], dostupne.data || [], dostupne.ciklus || null);
      } catch (e) { View.error(e); }
    })();
  },

  async ucenikOdaberi(id, nazivEnc) {
    const naziv = decodeURIComponent(nazivEnc);
    UI.confirm({
      title: 'Odabir teme',
      body: 'Želite li odabrati temu <strong>' + UI.esc(naziv) + '</strong>?',
      confirmText: 'Odaberi',
      onConfirm: async () => {
        await OdabirModel.odaberi(id);
        UI.toast('Tema odabrana.', 'success');
        window.Router.run();
      },
    });
  },

  async ucenikPonisti(id, nazivEnc) {
    const naziv = decodeURIComponent(nazivEnc);
    UI.confirm({
      title: 'Poništi odabir',
      body: 'Otkažite odabir teme <strong>' + UI.esc(naziv) + '</strong>?',
      confirmText: 'Poništi',
      onConfirm: async () => {
        await OdabirModel.ponistiMoj(id);
        UI.toast('Odabir otkazan.', 'success');
        window.Router.run();
      },
    });
  },

  // ---------- ciklusi ----------
  async ciklusiList() {
    try {
      const res = await CiklusModel.list();
      CiklusView.list(res.data || []);
    } catch (e) { View.error(e); }
  },

  async ciklusStatus(id, status) {
    try {
      await CiklusModel.status(id, status);
      UI.toast('Status promijenjen.', 'success');
      window.Router.run();
    } catch (e) { UI.toast(e.message, 'error'); }
  },

  async ciklusiForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    if (id > 0) {
      try {
        const res = await CiklusModel.get(id);
        values = res.data;
      } catch (e) { View.error(e); return; }
    }
    CiklusView.form(id, values);
  },

  // ---------- razredi ----------
  async razrediList() {
    try {
      const res = await RazredModel.list();
      RazredView.list(res.data || []);
    } catch (e) { View.error(e); }
  },

  async razrediForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    if (id > 0) {
      try {
        const res = await RazredModel.get(id);
        values = res.data;
      } catch (e) { View.error(e); return; }
    }
    RazredView.form(id, values);
  },

  // ---------- predmeti ----------
  async predmetiList() {
    try {
      const res = await PredmetModel.list();
      PredmetView.list(res.data || []);
    } catch (e) { View.error(e); }
  },

  async predmetiForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    if (id > 0) {
      try {
        const res = await PredmetModel.get(id);
        values = res.data;
      } catch (e) { View.error(e); return; }
    }
    PredmetView.form(id, values);
  },

  // ---------- profesori ----------
  async profesoriList() {
    try {
      const res = await ProfesorModel.list();
      ProfesorView.list(res.data || []);
    } catch (e) { View.error(e); }
  },

  async profesoriForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    if (id > 0) {
      try {
        const res = await ProfesorModel.get(id);
        values = res.data;
      } catch (e) { View.error(e); return; }
    }
    ProfesorView.form(id, values);
  },

  // ---------- učenici ----------
  async uceniciList() {
    try {
      const res = await UcenikModel.list();
      UcenikView.list(res.data || []);
    } catch (e) { View.error(e); }
  },

  async posaljiLink(id) {
    try {
      await UcenikModel.posaljiLink(id);
      UI.toast('Magic link poslan na email.', 'success');
    } catch (e) { UI.toast(e.message, 'error'); }
  },

  async uceniciForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    if (id > 0) {
      try {
        const res = await UcenikModel.get(id);
        values = res.data;
      } catch (e) { View.error(e); return; }
    }
    let razredi = [];
    try {
      const r = await RazredModel.list();
      razredi = r.data.map(x => ({ value: x.ID, label: x.NAZIV }));
    } catch (e) { View.error(e); return; }
    UcenikView.form(id, values, razredi);
  },

  // ---------- teme ----------
  async temeList() {
    try {
      const res = await TemaModel.list();
      TemaView.list(res.data || []);
    } catch (e) { View.error(e); }
  },

  async temeForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    if (id > 0) {
      try {
        const res = await TemaModel.get(id);
        values = res.data;
      } catch (e) { View.error(e); return; }
    }

    let opt;
    try {
      opt = await TemaModel.opcije();
    } catch (e) { View.error(e); return; }

    TemaView.form(id, values, opt);
  },

  // ---------- odabiri ----------
  async odabiriList(ctx) {
    const filter = ctx.params.get('filter') || 'aktivni';
    try {
      const res = await OdabirModel.list(filter);
      OdabirView.list(res.data || [], filter);
    } catch (e) { View.error(e); }
  },

  odabiriPonisti(id, ucenikEnc, temaEnc) {
    const ucenik = decodeURIComponent(ucenikEnc);
    const tema = decodeURIComponent(temaEnc);
    OdabirView.ponistiModal(ucenik, tema, async (obrazlozenje) => {
      await OdabirModel.ponisti(id, obrazlozenje);
      UI.toast('Odabir poništen.', 'success');
      window.Router.run();
    });
  },

  // ---------- stanje ----------
  async stanje() {
    try {
      StanjeView.render(await StanjeModel.get());
    } catch (e) { View.error(e); }
  },

  // ---------- uvoz ----------
  uvoz() {
    UvozView.render(
      'Uvoz tema iz Excela',
      '<table><tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th></tr>' +
      '<tr><td>Naziv teme</td><td>Predmet (ID ili naziv)</td><td>Profesor (ID ili "Prezime Ime")</td><td>Razred (ID ili naziv)</td><td>Ciklus (ID ili naziv, opcionalno)</td></tr></table>',
      '#/admin/teme'
    );
    UvozView.attachSubmit('tema', fd => TemaModel.uvoz(fd));
  },

  uvozUcenici() {
    UvozView.render(
      'Uvoz učenika iz Excela',
      '<table><tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th></tr>' +
      '<tr><td>Ime</td><td>Prezime</td><td>Email</td><td>Razred (ID ili naziv)</td><td>Smjer (Ekonomist/Trgovac, opcionalno)</td></tr></table>' +
      '<p class="muted">Nova lozinka se postavlja na "1234" (prijava ide preko magic linka).</p>',
      '#/admin/ucenici'
    );
    UvozView.attachSubmit('učenika', fd => UcenikModel.uvoz(fd));
  },
};
