/* API klijent - fetch wrapper */
const API = {
  async request(method, path, body, isFormData) {
    const opts = { method, credentials: 'same-origin' };
    if (body !== undefined && body !== null) {
      if (isFormData) {
        opts.body = body;
      } else {
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(body);
      }
    }

    const r = await fetch('api/' + path, opts);
    const ct = r.headers.get('content-type') || '';
    let data = null;
    if (ct.includes('application/json')) {
      try { data = await r.json(); } catch (_) { data = null; }
    }

    if (!r.ok) {
      const err = new Error((data && data.error) || ('Greška HTTP ' + r.status));
      err.status = r.status;
      err.data = data;
      throw err;
    }
    return data;
  },

  get(path, params) {
    if (params) {
      const qs = new URLSearchParams();
      Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') qs.append(k, v);
      });
      const s = qs.toString();
      if (s) path += (path.includes('?') ? '&' : '?') + s;
    }
    return this.request('GET', path);
  },
  post(path, body) { return this.request('POST', path, body, false); },
  upload(path, formData) { return this.request('POST', path, formData, true); },
};
