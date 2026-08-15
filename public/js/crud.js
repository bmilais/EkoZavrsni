/* Generički CRUD stranice (lista + forma) */
const Crud = {
  async list(config) {
    const app = document.getElementById('app');

    let data = null;
    if (config.load) {
      data = await config.load();
    } else {
      const res = await API.get(config.listUrl);
      data = res.data;
    }

    const rows = data.map(row => {
      const cells = config.columns.map(c => {
        if (c.render) return c.render(row);
        return UI.esc(row[c.key]);
      });
      return cells;
    });

    const actions = config.rowActions ? config.rowActions : () => '';
    const rowsWithActions = rows.map((cells, i) => {
      const act = actions(data[i]);
      return act ? cells.concat([act]) : cells;
    });
    const headers = config.columns.map(c => c.label);
    if (config.rowActions) headers.push('Akcije');

    let toolbar = '';
    if (config.newBtn || config.extraToolbar) {
      toolbar = '<div class="page-toolbar">' +
        '<div>' + (config.extraToolbar || '') + '</div>' +
        (config.newBtn
          ? '<a class="btn" href="#' + config.newBtn.route + '">' + UI.esc(config.newBtn.label) + '</a>'
          : '') +
        '</div>';
    }

    let html = '<div class="page">';
    html += '<h1>' + UI.esc(config.title) + '</h1>';
    html += toolbar;
    if (config.filters) html += config.filters;
    html += UI.table(headers, rowsWithActions);
    html += '<p class="mt"><a href="#/admin">&larr; Povratak na admin panel</a></p>';
    html += '</div>';

    app.innerHTML = html;
    if (config.afterRender) config.afterRender();
  },

  form(config) {
    const app = document.getElementById('app');
    const values = config.values || {};
    const options = config.options || {};
    const title = config.title || (config.id ? 'Uredi' : 'Novi unos');

    const fieldsHtml = UI.fieldsHtml(config.fields, values, options);

    let html = '<div class="page">';
    html += '<h1>' + UI.esc(title) + '</h1>';
    html += '<form class="form" id="crud-form">';
    if (config.id) html += '<input type="hidden" name="id" value="' + config.id + '">';
    html += fieldsHtml;
    html += '<div class="field">';
    html += '<button type="submit" class="btn" id="crud-save">Spremi</button> ';
    html += '<a class="btn secondary" href="#' + config.backRoute + '">Odustani</a>';
    html += '</div>';
    html += '</form></div>';

    app.innerHTML = html;

    document.getElementById('crud-form').onsubmit = async (e) => {
      e.preventDefault();
      const form = e.target;
      const data = UI.formDataFrom(form);
      const btn = document.getElementById('crud-save');
      btn.disabled = true;

      try {
        if (config.beforeSave) {
          const custom = await config.beforeSave(data);
          if (custom === false) { btn.disabled = false; return; }
        }
        await API.post(config.saveUrl, data);
        UI.toast(config.successMsg || 'Spremljeno.', 'success');
        location.hash = config.redirectAfter || config.backRoute;
      } catch (err) {
        btn.disabled = false;
        UI.toast(err.message, 'error');
      }
    };
  },

  async deleteItem({ url, id, title, confirmText }) {
    UI.confirm({
      title: title || 'Potvrda brisanja',
      body: '<p>' + UI.esc(confirmText || 'Obrisati zapis?') + '</p>',
      confirmText: 'Obriši',
      onConfirm: async () => {
        await API.post(url, { id: id });
        UI.toast('Obrisano.', 'success');
        window.Router.run();
      },
    });
  },
};
