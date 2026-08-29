/* View prijave - login forma i magic-link (token) prijava */
const LoginView = {
  render() {
    View.set(`
      <div class="login-wrap">
        <div class="auth-card">
          <div style="text-align:center;font-size:2rem;margin-bottom:6px">🍃</div>
          <h1>EkoZavrsni</h1>
          <div class="auth-sub">Prijava u sustav odabira završnih radova</div>
          <form class="form" id="login-form">
            <div class="field"><label>Email</label><input type="email" name="email" required placeholder="ime.prezime@vub.hr"></div>
            <div class="field"><label>Lozinka</label><input type="password" name="lozinka" required></div>
            <button type="submit" class="btn" style="width:100%">Prijavi se</button>
          </form>
        </div>
      </div>`, { topbar: false });
  },

  missing() {
    View.set('<div class="login-wrap"><div class="auth-card"><h1>Prijava</h1><p class="muted">Nedostaje link (token).</p></div></div>', { topbar: false });
  },

  error(msg) {
    View.set('<div class="login-wrap"><div class="auth-card"><h1>Prijava</h1><p style="color:#c62828">' + UI.esc(msg) + '</p></div></div>', { topbar: false });
  },
};
