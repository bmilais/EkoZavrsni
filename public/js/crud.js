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

    // Skupno označavanje: prvi stupac s checkboxima + "označi sve" u zaglavlju
    if (config.bulk) {
      headers.unshift('<input type="checkbox" id="bulk-all" title="Označi sve" aria-label="Označi sve">');
      rowsWithActions.forEach((cells, i) => {
        cells.unshift('<input type="checkbox" class="bulk-cb" value="' + UI.esc(String(data[i].ID)) + '" aria-label="Označi red">');
      });
    }

    let toolbar = '';
    const bulkBtn = config.bulk
      ? ' <button type="button" class="btn danger small" id="bulk-delete" disabled>Obriši označene</button>'
      : '';
    if (config.newBtn || config.extraToolbar || config.bulk) {
      toolbar = '<div class="page-toolbar">' +
        '<div>' + (config.extraToolbar || '') + bulkBtn + '</div>' +
        (config.newBtn
          ? '<a class="btn" href="#' + config.newBtn.route + '">' + UI.esc(config.newBtn.label) + '</a>'
          : '') +
        '</div>';
    }

    let html = '<div class="page">';
    html += '<p class="back-link"><a href="#' + (config.backRoute || '/admin') + '">&larr; ' + (config.backLabel || 'Povratak na admin panel') + '</a></p>';
    html += '<h1>' + UI.esc(config.title) + '</h1>';
    if (config.subtitle) html += '<p class="muted">' + config.subtitle + '</p>';
    html += toolbar;
    if (config.filters) html += config.filters;
    html += UI.table(headers, rowsWithActions);
    html += '</div>';

    app.innerHTML = html;
    if (config.bulk) Crud.wireBulk(config.bulk);
    if (config.afterRender) config.afterRender();
  },

  /**
   * Povezuje checkboxe, "označi sve" i gumb "Obriši označene".
   * bulk = { url: 'predmeti/obrisi-vise', noun: ['predmet','predmeta','predmeta'] }
   * (oblici za 1 / 2-4 / 5+ zapisa, npr. "1 predmet", "3 predmeta", "12 predmeta")
   */
  wireBulk(bulk) {
    const all = document.getElementById('bulk-all');
    const btn = document.getElementById('bulk-delete');
    const boxes = () => Array.from(document.querySelectorAll('.bulk-cb'));
    const checked = () => boxes().filter(b => b.checked);

    const noun = (n) => {
      const f = bulk.noun || ['zapis', 'zapisa', 'zapisa'];
      const mod10 = n % 10, mod100 = n % 100;
      if (mod10 === 1 && mod100 !== 11) return f[0];
      if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return f[1];
      return f[2];
    };

    const refresh = () => {
      const n = checked().length, total = boxes().length;
      btn.disabled = n === 0;
      btn.textContent = n > 0 ? 'Obriši označene (' + n + ')' : 'Obriši označene';
      all.checked = total > 0 && n === total;
      all.indeterminate = n > 0 && n < total;
      all.disabled = total === 0;
    };

    all.onchange = () => { boxes().forEach(b => { b.checked = all.checked; }); refresh(); };
    boxes().forEach(b => { b.onchange = refresh; });
    refresh();

    btn.onclick = () => {
      const ids = checked().map(b => parseInt(b.value, 10));
      if (ids.length === 0) return;
      UI.confirm({
        title: 'Potvrda brisanja',
        body: '<p>Obrisati ' + ids.length + ' ' + UI.esc(noun(ids.length)) + '?</p>',
        confirmText: 'Obriši',
        onConfirm: async () => {
          const res = await API.post(bulk.url, { ids });
          const n = res.deleted ?? ids.length;
          let msg = 'Obrisano: ' + n + '.';
          if (res.skippedSelf) msg += ' Vlastiti račun je preskočen.';
          UI.toast(msg, 'success');
          window.Router.run();
        },
      });
    };
  },

  form(config) {
    const app = document.getElementById('app');
    const values = config.values || {};
    const options = config.options || {};
    const title = config.title || (config.id ? 'Uredi' : 'Novi unos');

    const fieldsHtml = UI.fieldsHtml(config.fields, values, options);

    let html = '<div class="page">';
    html += '<p class="back-link"><a href="#' + config.backRoute + '">&larr; Povratak</a></p>';
    html += '<h1>' + UI.esc(title) + '</h1>';
    html += '<form class="form" id="crud-form">';
    if (config.id) html += '<input type="hidden" name="id" value="' + config.id + '">';
    if (config.hidden) {
      Object.keys(config.hidden).forEach(k => {
        html += '<input type="hidden" name="' + k + '" value="' + UI.esc(config.hidden[k]) + '">';
      });
    }
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
