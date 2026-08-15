/* View admin dashboarda */
const AdminView = {
  render(u) {
    View.set(`
      <div class="page">
        <h1>Admin panel</h1>
        <p>Pozdrav, ${UI.esc(u.ime + ' ' + u.prezime)}.</p>
        <p class="muted">Odaberite opciju iz izbornika na vrhu.</p>
      </div>`);
  },
};
