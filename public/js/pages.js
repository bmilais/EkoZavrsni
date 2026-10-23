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
  async admin() {
    const u = window.App.user;
    let ciklusi = [];
    try {
      const res = await CiklusModel.list();
      ciklusi = res.data || [];
    } catch (e) { View.error(e); return; }

    let selectedId = SelectedCiklus.get();
    let selected = selectedId ? ciklusi.find(c => parseInt(c.ID, 10) === selectedId) : null;
    if (selectedId && !selected) {
      // Zapamćeni ciklus više ne postoji (npr. obrisan) - zaboravi odabir.
      SelectedCiklus.clear();
    }

    AdminView.render(u, ciklusi, selected || null);
  },

  adminOdaberiCiklus(id) {
    SelectedCiklus.set(parseInt(id, 10));
    this.admin();
  },

  adminPromijeniCiklus() {
    SelectedCiklus.clear();
    this.admin();
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

  async ucenikOdaberi(id, naziv) {
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

  async ucenikPonisti(id, naziv) {
    UcenikPanelView.ponistiModal(naziv, async (razlog) => {
      await OdabirModel.ponistiMoj(id, razlog);
      UI.toast('Odabir otkazan.', 'success');
      window.Router.run();
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
      if (values.status === 'arhivirano') {
        UI.toast('Arhivirani ciklus se ne može uređivati.', 'error');
        window.Router.navigate('#/admin/ciklusi');
        return;
      }
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

  // ---------- učenici (uvijek u kontekstu ciklusa) ----------
  // Ciklus se uzima iz URL-a (?ciklus_id=) ako je zadan, inače iz ciklusa
  // odabranog na admin panelu (SelectedCiklus). Koji god ciklus se ovdje
  // razriješi, postaje (ponovno) "trenutno odabrani" - tako da otvaranje
  // stranice s eksplicitnim ciklus_id (npr. s popisa ciklusa) ažurira i
  // odabir na admin panelu.
  async ucenikCiklusOrRedirect(ctx) {
    const ciklusId = ctx.params.get('ciklus_id') ? parseInt(ctx.params.get('ciklus_id'), 10) : (SelectedCiklus.get() || 0);
    if (!ciklusId) {
      UI.toast('Prvo odaberi ciklus.', 'error');
      window.Router.navigate('#/admin');
      return null;
    }
    try {
      const res = await CiklusModel.get(ciklusId);
      const c = res.data;
      // CiklusService::get() vraća polja malim slovima (za formu); ovdje ih
      // normaliziramo na velika slova da se poklapaju s ostatkom UI-ja
      // (CiklusModel.list(), UcenikView, TemaView...).
      const ciklus = {
        ID: parseInt(c.id, 10),
        NAZIV: c.naziv,
        SKOLSKA_GODINA: c.skolska_godina,
        STATUS: c.status,
      };
      SelectedCiklus.set(ciklus.ID);
      return ciklus;
    } catch (e) {
      SelectedCiklus.clear();
      View.error(e);
      return null;
    }
  },

  async uceniciList(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    try {
      const res = await UcenikModel.list(ciklus.ID);
      UcenikView.list(res.data || [], ciklus);
    } catch (e) { View.error(e); }
  },

  async posaljiLink(id) {
    try {
      await UcenikModel.posaljiLink(id);
      UI.toast('Magic link poslan na email.', 'success');
      window.Router.run(); // osvježi stupac "Link poslan"
    } catch (e) { UI.toast(e.message, 'error'); }
  },

  // Korak 1: admin bira kome se šalje (samo onima bez linka ili svima).
  posaljiLinkSvima(ciklusId) {
    UI.confirm({
      title: 'Pošalji magic link',
      body:
        '<p>Poslati magic link učenicima ovog ciklusa koji ga još nisu dobili?</p>' +
        '<label><input type="checkbox" id="ponovno-svima"> Pošalji ponovno <strong>svima</strong> u ciklusu</label>' +
        '<p class="muted">Za veći broj učenika ovo može potrajati.</p>',
      confirmText: 'Dalje',
      danger: false,
      onConfirm: async () => {
        const ponovnoSvima = document.getElementById('ponovno-svima').checked;

        // Koliko će učenika stvarno dobiti mail - prikazuje se u potvrdi.
        const lista = (await UcenikModel.list(ciklusId)).data || [];
        const broj = ponovnoSvima ? lista.length : lista.filter(u => !u.LINK_POSLAN).length;
        if (broj === 0) {
          UI.toast(ponovnoSvima ? 'U ovom ciklusu nema učenika.' : 'Svi učenici ovog ciklusa već su dobili link.', 'info');
          return; // zatvori prozor, nema se što slati
        }

        this.posaljiLinkSvimaPotvrda(ciklusId, ponovnoSvima, broj);
        return false; // prozor je zamijenjen potvrdom - ne zatvaraj ga
      },
    });
  },

  // Korak 2: "Jeste li sigurni?" s brojem učenika. Tek ovdje se mailovi šalju.
  posaljiLinkSvimaPotvrda(ciklusId, ponovnoSvima, broj) {
    // 1 učenik, 21 učenik ... ali 11 učenika; inače "učenika" (2 učenika, 5 učenika)
    const oblik = (n) => (n % 10 === 1 && n % 100 !== 11) ? 'učenik' : 'učenika';

    UI.confirm({
      title: 'Jeste li sigurni?',
      body:
        '<p>Magic link na email dobit će <strong>' + broj + ' ' + oblik(broj) + '</strong>.</p>' +
        (ponovnoSvima
          ? '<p><strong>Uključeni su i učenici koji su link već dobili</strong> — dobit će ga ponovno.</p>'
          : '') +
        '<p class="muted">Poslani mailovi ne mogu se povući.</p>',
      confirmText: 'Da, pošalji',
      danger: true,
      onConfirm: async () => {
        let res;
        try {
          res = await UcenikModel.posaljiLinkSvima(ciklusId, ponovnoSvima);
        } catch (e) {
          // Npr. prekid zbog vremenskog ograničenja - dio mailova je možda poslan.
          window.Router.run();
          throw new Error(e.message + ' Dio mailova je možda poslan - provjerite stupac "Link poslan" ' +
            'i pokrenite slanje ponovno (poslat će se samo onima koji link još nisu dobili).');
        }

        const greske = res.greske || [];
        window.Router.run(); // osvježi stupac "Link poslan"

        if (res.ukupno === 0) {
          UI.toast('Svi učenici ovog ciklusa već su dobili link.', 'info');
          return;
        }
        if (greske.length === 0) {
          UI.toast('Magic link poslan (' + res.poslano + '/' + res.ukupno + ').', 'success');
          return;
        }

        UI.toast('Poslano ' + res.poslano + '/' + res.ukupno + '. ' + greske.length + ' greška(e) — vidi popis.', 'error', 8000);
        UI.modal({
          title: 'Greške pri slanju magic linkova',
          body: '<ul class="sm">' + greske.map(g => '<li>' + UI.esc(g) + '</li>').join('') + '</ul>',
          foot: '<button class="btn secondary" onclick="UI.closeModal()">Zatvori</button>',
        });
        return false; // UI.confirm ne smije zatvoriti modal s popisom grešaka
      },
    });
  },

  async uceniciForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    let ciklusId = ctx.params.get('ciklus_id') ? parseInt(ctx.params.get('ciklus_id'), 10) : 0;
    if (id > 0) {
      try {
        const res = await UcenikModel.get(id);
        values = res.data;
        ciklusId = ciklusId || parseInt(values.idciklus, 10);
      } catch (e) { View.error(e); return; }
    }
    if (!ciklusId) {
      UI.toast('Prvo odaberi ciklus.', 'error');
      window.Router.navigate('#/admin/ciklusi');
      return;
    }
    let razredi = [];
    try {
      const r = await RazredModel.list();
      razredi = r.data.map(x => ({ value: x.ID, label: x.NAZIV }));
    } catch (e) { View.error(e); return; }
    UcenikView.form(id, values, razredi, ciklusId);
  },

  // ---------- teme (uvijek u kontekstu ciklusa) ----------
  async temeList(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    try {
      const res = await TemaModel.list(ciklus.ID);
      TemaView.list(res.data || [], ciklus);
    } catch (e) { View.error(e); }
  },

  async temeForm(ctx) {
    const id = ctx.params.get('id') ? parseInt(ctx.params.get('id'), 10) : 0;
    let values = {};
    let ciklusId = ctx.params.get('ciklus_id') ? parseInt(ctx.params.get('ciklus_id'), 10) : 0;
    if (id > 0) {
      try {
        const res = await TemaModel.get(id);
        values = res.data;
        ciklusId = ciklusId || parseInt(values.idciklusa, 10);
      } catch (e) { View.error(e); return; }
    }
    if (!ciklusId) {
      UI.toast('Prvo odaberi ciklus.', 'error');
      window.Router.navigate('#/admin/ciklusi');
      return;
    }

    let opt, ciklus;
    try {
      opt = await TemaModel.opcije();
      const c = await CiklusModel.get(ciklusId);
      ciklus = c.data;
      opt.ciklusNaziv = ciklus.naziv;
    } catch (e) { View.error(e); return; }

    TemaView.form(id, values, opt, ciklusId);
  },

  // ---------- odabiri (uvijek u kontekstu ciklusa) ----------
  async odabiriList(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    const filter = ctx.params.get('filter') || 'aktivni';
    try {
      const res = await OdabirModel.list(ciklus.ID, filter);
      OdabirView.list(res.data || [], filter, ciklus);
    } catch (e) { View.error(e); }
  },

  odabiriPonisti(id, ucenik, tema) {
    OdabirView.ponistiModal(ucenik, tema, async (obrazlozenje) => {
      await OdabirModel.ponisti(id, obrazlozenje);
      UI.toast('Odabir poništen.', 'success');
      window.Router.run();
    });
  },

  // ---------- stanje (uvijek u kontekstu ciklusa) ----------
  async stanje(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    try {
      const res = await StanjeModel.get(ciklus.ID);
      StanjeView.render(res, ciklus);
    } catch (e) { View.error(e); }
  },

  // ---------- upute (PDF za učenike, uvijek u kontekstu ciklusa) ----------
  async upute(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    let info;
    try {
      info = (await UputeModel.info(ciklus.ID)).data;
    } catch (e) { View.error(e); return; }

    UputeView.render(ciklus, info);
    UputeView.attachUpload(info.MAX_VELICINA, async (fd) => {
      await UputeModel.upload(fd);
      UI.toast(info.POSTOJI ? 'PDF zamijenjen.' : 'PDF učitan.', 'success');
      window.Router.run();
    });
  },

  // Slanje uputa jednom učeniku (panel Upute i popis učenika), kao "Pošalji link".
  async uputePosaljiUceniku(ucenikId, btn) {
    const tekst = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Šaljem...'; }
    try {
      await UputeModel.posaljiUceniku(ucenikId);
      UI.toast('Upute poslane na email.', 'success');
      window.Router.run(); // osvježi stupac "Upute poslane" na panelu
    } catch (e) {
      if (btn) { btn.disabled = false; btn.textContent = tekst; }
      UI.toast(e.message, 'error', 8000);
    }
  },

  // Korak 1: kome se šalje (samo onima koji upute nisu dobili ili svima).
  uputePosalji(ciklusId) {
    UI.confirm({
      title: 'Pošalji upute',
      body:
        '<p>Poslati PDF upute učenicima ovog ciklusa koji ih još nisu dobili?</p>' +
        '<label><input type="checkbox" id="upute-ponovno-svima"> Pošalji ponovno <strong>svima</strong> u ciklusu</label>' +
        '<p class="muted">Za veći broj učenika ovo može potrajati.</p>',
      confirmText: 'Dalje',
      danger: false,
      onConfirm: async () => {
        const ponovnoSvima = document.getElementById('upute-ponovno-svima').checked;
        const info = (await UputeModel.info(ciklusId)).data;
        const broj = ponovnoSvima ? info.UKUPNO : info.UKUPNO - info.POSLANO;
        if (broj === 0) {
          UI.toast(ponovnoSvima ? 'U ovom ciklusu nema učenika.' : 'Svi učenici ovog ciklusa već su dobili upute.', 'info');
          return;
        }
        this.uputePosaljiPotvrda(ciklusId, ponovnoSvima, broj, info.NAZIV);
        return false; // prozor je zamijenjen potvrdom - ne zatvaraj ga
      },
    });
  },

  // Korak 2: "Jeste li sigurni?" s brojem učenika. Tek ovdje se mailovi šalju.
  uputePosaljiPotvrda(ciklusId, ponovnoSvima, broj, nazivPdf) {
    const oblik = (n) => (n % 10 === 1 && n % 100 !== 11) ? 'učenik' : 'učenika';

    UI.confirm({
      title: 'Jeste li sigurni?',
      body:
        '<p>PDF <strong>' + UI.esc(nazivPdf) + '</strong> kao privitak dobit će <strong>' + broj + ' ' + oblik(broj) + '</strong>.</p>' +
        (ponovnoSvima
          ? '<p><strong>Uključeni su i učenici koji su upute već dobili</strong> — dobit će ih ponovno.</p>'
          : '') +
        '<p class="muted">Poslani mailovi ne mogu se povući.</p>',
      confirmText: 'Da, pošalji',
      danger: true,
      onConfirm: async () => {
        let res;
        try {
          res = await UputeModel.posalji(ciklusId, ponovnoSvima);
        } catch (e) {
          window.Router.run();
          throw new Error(e.message + ' Dio mailova je možda poslan - provjerite stupac "Upute poslane" ' +
            'i pokrenite slanje ponovno (poslat će se samo onima koji upute još nisu dobili).');
        }

        const greske = res.greske || [];
        window.Router.run(); // osvježi stanje slanja na panelu

        if (res.ukupno === 0) {
          UI.toast('Svi učenici ovog ciklusa već su dobili upute.', 'info');
          return;
        }
        if (greske.length === 0) {
          UI.toast('Upute poslane (' + res.poslano + '/' + res.ukupno + ').', 'success');
          return;
        }

        UI.toast('Poslano ' + res.poslano + '/' + res.ukupno + '. ' + greske.length + ' greška(e) — vidi popis.', 'error', 8000);
        UI.modal({
          title: 'Greške pri slanju uputa',
          body: '<ul class="sm">' + greske.map(g => '<li>' + UI.esc(g) + '</li>').join('') + '</ul>',
          foot: '<button class="btn secondary" onclick="UI.closeModal()">Zatvori</button>',
        });
        return false; // UI.confirm ne smije zatvoriti modal s popisom grešaka
      },
    });
  },

  // ---------- uvoz (uvijek u kontekstu ciklusa) ----------
  async uvoz(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    UvozView.render(
      'Uvoz tema iz Excela — ' + UI.esc(ciklus.NAZIV),
      '<table><tr><th>A</th><th>B</th><th>C</th><th>D</th></tr>' +
      '<tr><td>Naziv teme</td><td>Predmet (ID ili naziv)</td><td>Profesor (ID ili "Prezime Ime")</td><td>Razred (ID ili naziv)</td></tr></table>' +
      '<p class="muted">Sve uvezene teme idu u ovaj ciklus (' + UI.esc(ciklus.NAZIV) + ') — stupac za ciklus se više ne koristi jer je znao tiho prebaciti temu u pogrešan ciklus.</p>',
      '#/admin/teme?ciklus_id=' + ciklus.ID,
      { ciklus_id: ciklus.ID }
    );
    UvozView.attachSubmit('tema', fd => TemaModel.uvoz(fd));
  },

  async uvozUcenici(ctx) {
    const ciklus = await this.ucenikCiklusOrRedirect(ctx);
    if (!ciklus) return;
    UvozView.render(
      'Uvoz učenika iz Excela — ' + UI.esc(ciklus.NAZIV),
      '<table><tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th></tr>' +
      '<tr><td>Ime</td><td>Prezime</td><td>Email</td><td>Razred (ID ili naziv)</td><td>Smjer (Ekonomist/Trgovac, opcionalno)</td></tr></table>' +
      '<p class="muted">Nova lozinka se postavlja na "1234" (prijava ide preko magic linka). Učenici se uvoze u ovaj ciklus.</p>',
      '#/admin/ucenici?ciklus_id=' + ciklus.ID,
      { ciklus_id: ciklus.ID }
    );
    UvozView.attachSubmit('učenika', fd => UcenikModel.uvoz(fd));
  },
};
