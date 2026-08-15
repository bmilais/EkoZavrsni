/* Zajednički view - layout, postavljanje HTML-a i pomoćne funkcije */
const View = {
  set(html, opts = {}) {
    const top = opts.topbar === false ? '' : this.layout();
    document.getElementById('app').innerHTML = top + html;
  },

  layout() {
    const u = window.App.user;
    if (!u) return '';
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
      links = items.map(([r, l]) =>
        '<a href="#' + r + '"' + (location.hash === '#' + r ? ' style="text-decoration:underline"' : '') + '>' + l + '</a>'
      ).join(' | ');
    } else if (u.uloga === 'nastavnik') {
      links = '<a href="#/nastavnik">Panel</a>';
    } else if (u.uloga === 'ucenik') {
      links = '<a href="#/ucenik">Moje teme</a>';
    }
    return '<div class="topbar">' +
      '<a href="#/"><strong>EkoZavrsni</strong></a>' +
      (links ? ' | ' + links : '') +
      '<span class="user">' + UI.esc(u.ime + ' ' + u.prezime) +
      ' <a href="#" onclick="window.App.logout(event)" style="margin-left:10px">Odjava</a></span>' +
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
