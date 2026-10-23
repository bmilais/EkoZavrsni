/* Aplikacija: stanje prijave, usmjeravanje i pokretanje */
window.App = {
  user: null,

  async logout(e) {
    if (e) e.preventDefault();
    try { await API.post('auth/logout'); } catch (_) { /* ignore */ }
    window.App.user = null;
    SelectedCiklus.clear();
    window.Router.navigate('#/login');
  },
};

/* Pamti koji je ciklus admin trenutno "otvorio" na admin panelu, da ne mora
   svaki put birati ciklus prilikom odlaska na Učenike/Teme/Odabire/Stanje.
   Vezano samo za ovaj preglednik (localStorage) - nije sigurnosna granica,
   samo UX pogodnost. */
const SelectedCiklus = {
  KEY: 'eko_ciklus_id',
  get() {
    try {
      const v = localStorage.getItem(this.KEY);
      return v ? parseInt(v, 10) : 0;
    } catch (_) { return 0; }
  },
  set(id) {
    try { localStorage.setItem(this.KEY, String(id)); } catch (_) { /* ignore */ }
  },
  clear() {
    try { localStorage.removeItem(this.KEY); } catch (_) { /* ignore */ }
  },
};
window.SelectedCiklus = SelectedCiklus;

const Router = {
  routes: [
    ['login', () => Pages.login(), null],
    ['', () => Pages.login(), null],
    ['prijava', ctx => Pages.prijava(ctx), null],
    ['admin', () => Pages.admin(), 'admin'],
    ['admin/ciklusi', () => Pages.ciklusiList(), 'admin'],
    ['admin/ciklusi/novi', ctx => Pages.ciklusiForm(ctx), 'admin'],
    ['admin/ciklusi/uredi', ctx => Pages.ciklusiForm(ctx), 'admin'],
    ['admin/razredi', () => Pages.razrediList(), 'admin'],
    ['admin/razredi/novi', ctx => Pages.razrediForm(ctx), 'admin'],
    ['admin/razredi/uredi', ctx => Pages.razrediForm(ctx), 'admin'],
    ['admin/predmeti', () => Pages.predmetiList(), 'admin'],
    ['admin/predmeti/novi', ctx => Pages.predmetiForm(ctx), 'admin'],
    ['admin/predmeti/uredi', ctx => Pages.predmetiForm(ctx), 'admin'],
    ['admin/profesori', () => Pages.profesoriList(), 'admin'],
    ['admin/profesori/novi', ctx => Pages.profesoriForm(ctx), 'admin'],
    ['admin/profesori/uredi', ctx => Pages.profesoriForm(ctx), 'admin'],
    ['admin/ucenici', ctx => Pages.uceniciList(ctx), 'admin'],
    ['admin/ucenici/novi', ctx => Pages.uceniciForm(ctx), 'admin'],
    ['admin/ucenici/uredi', ctx => Pages.uceniciForm(ctx), 'admin'],
    ['admin/ucenici/uvoz', ctx => Pages.uvozUcenici(ctx), 'admin'],
    ['admin/teme', ctx => Pages.temeList(ctx), 'admin'],
    ['admin/teme/novi', ctx => Pages.temeForm(ctx), 'admin'],
    ['admin/teme/uredi', ctx => Pages.temeForm(ctx), 'admin'],
    ['admin/teme/uvoz', ctx => Pages.uvoz(ctx), 'admin'],
    ['admin/odabiri', ctx => Pages.odabiriList(ctx), 'admin'],
    ['admin/stanje', ctx => Pages.stanje(ctx), 'admin'],
    ['admin/upute', ctx => Pages.upute(ctx), 'admin'],
    ['nastavnik', () => Pages.nastavnik(), 'nastavnik'],
    ['ucenik', () => Pages.ucenik(), 'ucenik'],
  ],

  navigate(h) {
    if (location.hash === h) this.run();
    else location.hash = h;
  },

  home() {
    const u = window.App.user;
    return '#/' + (u ? u.uloga : 'login');
  },

  run() {
    const raw = location.hash.replace(/^#\/?/, '');
    const parts = raw.split('?');
    const path = parts[0] || '';
    const params = new URLSearchParams(parts[1] || '');
    const ctx = { path, params };
    const u = window.App.user;

    const route = this.routes.find(r => r[0] === path);

    if (!route) {
      this.navigate(this.home());
      return;
    }

    if (path === 'login') {
      if (u) { this.navigate(this.home()); return; }
      Pages.login();
      return;
    }

    if (!u && route[2]) { this.navigate('#/login'); return; }
    if (route[2] && route[2] !== u.uloga) { this.navigate(this.home()); return; }

    route[1](ctx);
  },
};

window.Router = Router;

async function boot() {
  try {
    const res = await API.get('auth/user');
    window.App.user = res.user || null;
  } catch (_) {
    window.App.user = null;
  }
  Router.run();
}

window.addEventListener('hashchange', () => Router.run());
document.addEventListener('DOMContentLoaded', boot);
