/* Model odabira - administracija i učenikov panel */
const OdabirModel = {
  list(filter) { return API.get('odabiri?filter=' + (filter || 'aktivni')); },
  ponisti(id, obrazlozenje) { return API.post('odabiri/ponisti', { id, obrazlozenje }); },
  moji() { return API.get('ucenik/moji'); },
  dostupne() { return API.get('ucenik/teme'); },
  odaberi(temaId) { return API.post('ucenik/odaberi', { tema_id: temaId }); },
  ponistiMoj(id) { return API.post('ucenik/ponisti', { id }); },
};
