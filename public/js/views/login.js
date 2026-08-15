/* View prijave - login forma i magic-link (token) prijava */
const LoginView = {
  render() {
    View.set(`
      <div class="page" style="max-width:420px;margin:40px auto">
        <h1>Prijava</h1>
        <form class="form" id="login-form">
          <div class="field"><label>Email</label><input type="email" name="email" required></div>
          <div class="field"><label>Lozinka</label><input type="password" name="lozinka" required></div>
          <button type="submit" class="btn">Prijavi se</button>
        </form>
      </div>`, { topbar: false });
  },

  missing() {
    View.set('<div class="page"><h1>Prijava</h1><p class="muted">Nedostaje link (token).</p></div>', { topbar: false });
  },

  error(msg) {
    View.set('<div class="page"><h1>Prijava</h1><p style="color:#c62828">' + UI.esc(msg) + '</p></div>', { topbar: false });
  },
};
