/* Model odabira - administracija i učenikov panel */
const OdabirModel = {
  list(ciklusId, filter) { return API.get('odabiri?ciklus_id=' + ciklusId + '&filter=' + (filter || 'aktivni')); },
  ponisti(id, obrazlozenje) { return API.post('odabiri/ponisti', { id, obrazlozenje }); },
  moji() { return API.get('ucenik/moji'); },
  dostupne() { return API.get('ucenik/teme'); },
  odaberi(temaId) { return API.post('ucenik/odaberi', { tema_id: temaId }); },
  ponistiMoj(id, razlog) { return API.post('ucenik/ponisti', { id, razlog }); },
};
