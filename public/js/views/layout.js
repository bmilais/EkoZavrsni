/* Zajednički view - layout, postavljanje HTML-a i pomoćne funkcije */
const View = {
  set(html, opts = {}) {
    const top = opts.topbar === false ? '' : this.layout();
    document.getElementById('app').innerHTML = top + html;
  },

  layout() {
    const u = window.App.user;
    if (!u) return '';

    const isActive = (r) => {
      const h = location.hash;
      // podstranice (npr. #/admin/ucenici/novi) zadržavaju aktivno na roditelju
      if (h === '#' + r) return ' class="active"';
      if (r !== '/admin' && h.indexOf('#' + r + '/') === 0) return ' class="active"';
      return '';
    };

    let links = '';
    if (u.uloga === 'admin') {
      const items = [
        ['/admin', 'Panel'],
        ['/admin/ciklusi', 'Ciklusi'],
        ['/admin/razredi', 'Razredi'],
        ['/admin/predmeti', 'Predmeti'],
        ['/admin/profesori', 'Profesori'],
        ['/admin/ucenici', 'Učenici'],
        ['/admin/teme', 'Teme'],
        ['/admin/odabiri', 'Odabiri'],
        ['/admin/stanje', 'Stanje'],
      ];
      links = '<nav>' + items.map(([r, l]) =>
        '<a href="#' + r + '"' + isActive(r) + '>' + l + '</a>'
      ).join('') + '</nav>';
    } else {
      let item;
      if (u.uloga === 'nastavnik') item = ['/nastavnik', 'Panel'];
      else item = ['/ucenik', 'Moje teme'];
      links = '<nav><a href="#' + item[0] + '"' + isActive(item[0]) + '>' + item[1] + '</a></nav>';
    }

    const inicijali = (u.ime ? u.ime[0] : '') + (u.prezime ? u.prezime[0] : '');

    return '<div class="topbar">' +
      '<a href="#/" class="brand">🍃 EkoZavrsni<small>Sustav odabira završnih radova</small></a>' +
      links +
      '<span class="user"><span class="avatar">' + UI.esc(inicijali.toUpperCase()) + '</span>' +
      UI.esc(u.ime + ' ' + u.prezime) +
      ' <a href="#" onclick="window.App.logout(event)" style="margin-left:8px">Odjava</a></span>' +
      '</div>';
  },

  error(e) {
    this.set('<div class="page"><h1>Greška</h1><p class="muted">' + UI.esc(e.message) + '</p></div>');
  },

  fmtDate(s) {
    if (!s) return '-';
    return s.replace(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/, '$3.$2.$1. $4:$5');
  },
};
