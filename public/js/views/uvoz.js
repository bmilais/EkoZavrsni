/* View uvoza iz Excela - zajednički za teme i učenike */
const UvozView = {
  render(title, opis, backRoute) {
    View.set(`
      <div class="page">
        <h1>${title}</h1>
        <p class="muted">Očekivani redoslijed stupaca (prvi red = zaglavlje, preskače se):</p>
        ${opis}
        <br>
        <form class="form" id="uvoz-form">
          <div class="field"><label>Excel datoteka</label><input type="file" name="excel" accept=".xlsx,.xls" required></div>
          <button type="submit" class="btn">Uvezi</button>
          <a class="btn secondary" href="${backRoute}">Odustani</a>
        </form>
        <div id="uvoz-rezultat" class="mt"></div>
      </div>`);
  },

  attachSubmit(jedinica, onSubmit) {
    document.getElementById('uvoz-form').onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const btn = e.target.querySelector('button');
      btn.disabled = true;
      const rez = document.getElementById('uvoz-rezultat');
      rez.innerHTML = '<p class="muted">Obrađujem datoteku...</p>';
      try {
        const data = await onSubmit(fd);
        let html = '<h2>Rezultat</h2>';
        html += '<p class="' + (data.ubaceno > 0 ? 'badge green' : '') + '">Uspješno uvezeno: <strong>' + data.ubaceno + '</strong> ' + jedinica + '.</p>';
        if (data.greske.length) {
          html += '<p class="muted">Greške:</p><ul class="sm">' + data.greske.map(g => '<li>' + UI.esc(g) + '</li>').join('') + '</ul>';
        }
        rez.innerHTML = html;
        UI.toast('Uvoz završen.', 'success');
      } catch (err) {
        rez.innerHTML = '<p style="color:red">' + UI.esc(err.message) + '</p>';
      } finally {
        btn.disabled = false;
      }
    };
  },
};
