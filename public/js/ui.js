/* UI pomoćne funkcije */
const UI = {
  esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  },

  toast(msg, type = 'info', ms = 3500) {
    const wrap = document.getElementById('toast-wrap');
    const div = document.createElement('div');
    div.className = 'toast ' + type;
    div.textContent = msg;
    wrap.appendChild(div);
    setTimeout(() => div.remove(), ms);
  },

  modal({ title, body, foot, onClose }) {
    const wrap = document.getElementById('modal-wrap');
    wrap.innerHTML = '';
    wrap.classList.add('open');

    const m = document.createElement('div');
    m.className = 'modal';
    m.innerHTML =
      (title ? '<div class="modal-head">' + UI.esc(title) + '</div>' : '') +
      '<div class="modal-body">' + (body || '') + '</div>' +
      (foot ? '<div class="modal-foot">' + foot + '</div>' : '');

    wrap.appendChild(m);
    wrap.onclick = (e) => { if (e.target === wrap) UI.closeModal(onClose); };
  },

  confirm({ title, body, confirmText = 'Potvrdi', danger = true, onConfirm }) {
    const foot =
      '<button class="btn secondary" onclick="UI.closeModal()">Odustani</button>' +
      '<button class="btn ' + (danger ? 'danger' : '') + '" id="modal-confirm">' + UI.esc(confirmText) + '</button>';

    UI.modal({ title, body, foot });

    document.getElementById('modal-confirm').onclick = async () => {
      const btn = document.getElementById('modal-confirm');
      btn.disabled = true;
      btn.textContent = '...';
      try {
        await onConfirm();
        UI.closeModal();
      } catch (e) {
        btn.disabled = false;
        btn.textContent = confirmText;
        UI.toast(e.message, 'error');
      }
    };
  },

  closeModal(onClose) {
    const wrap = document.getElementById('modal-wrap');
    wrap.classList.remove('open');
    wrap.innerHTML = '';
    if (onClose) onClose();
  },

  badge(text, color) {
    return '<span class="badge ' + color + '">' + UI.esc(text) + '</span>';
  },

  table(headers, rows) {
    let html = '<table><tr>' + headers.map(h => '<th>' + h + '</th>').join('') + '</tr>';
    if (rows.length === 0) {
      html += '<tr><td colspan="' + headers.length + '" class="muted">Nema podataka.</td></tr>';
    } else {
      html += rows.map(r => '<tr>' + r.map(c => '<td>' + (c === null || c === undefined ? '' : c) + '</td>').join('') + '</tr>').join('');
    }
    return html + '</table>';
  },

  fieldsHtml(fields, values, options) {
    values = values || {};
    options = options || {};
    return fields.map(f => {
      const v = values[f.name] !== undefined && values[f.name] !== null ? values[f.name] : '';
      const opt = f.options || options[f.name] || [];
      const req = f.required ? ' required' : '';
      let input;

      switch (f.type) {
        case 'select':
          let opts = '';
          if (f.placeholder !== false) {
            opts += '<option value="">' + UI.esc(f.placeholder || '-- Odaberi --') + '</option>';
          }
          opt.forEach(o => {
            const val = typeof o === 'object' ? o.value : o;
            const lab = typeof o === 'object' ? o.label : o;
            const sel = String(val) === String(v) ? ' selected' : '';
            opts += '<option value="' + UI.esc(val) + '"' + sel + '>' + UI.esc(lab) + '</option>';
          });
          input = '<select name="' + f.name + '"' + req + '>' + opts + '</select>';
          break;
        case 'textarea':
          input = '<textarea name="' + f.name + '" rows="' + (f.rows || 4) + '" cols="60"' + req + '>' + UI.esc(v) + '</textarea>';
          break;
        case 'checkbox':
          input = '<input type="checkbox" name="' + f.name + '" value="1"' + (v == 1 ? ' checked' : '') + '>';
          break;
        default:
          input = '<input type="' + (f.type || 'text') + '" name="' + f.name + '" value="' + UI.esc(v) + '"' +
            (f.size ? ' size="' + f.size + '"' : '') + (f.step ? ' step="' + f.step + '"' : '') + req + '>';
      }

      return '<div class="field' + (f.wide ? ' wide' : '') + '">' +
        '<label>' + UI.esc(f.label) +
        (f.small ? ' <small>' + UI.esc(f.small) + '</small>' : '') +
        '</label>' + input + '</div>';
    }).join('');
  },

  formDataFrom(form) {
    const fd = new FormData(form);
    const data = {};
    fd.forEach((v, k) => { data[k] = v; });
    return data;
  },
};
