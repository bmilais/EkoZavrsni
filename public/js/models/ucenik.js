/* Model učenika - sve API operacije vezane uz učenike */
const UcenikModel = {
  list(ciklusId) { return API.get('ucenici' + (ciklusId ? '?ciklus_id=' + ciklusId : '')); },
  get(id) { return API.get('ucenici?id=' + id); },
  spremi(data) { return API.post('ucenici/spremi', data); },
  obrisi(id) { return API.post('ucenici/obrisi', { id }); },
  posaljiLink(id) { return API.post('ucenici/posalji-link', { id }); },
  posaljiLinkSvima(ciklusId, ponovnoSvima) { return API.post('ucenici/posalji-link-svima', { ciklus_id: ciklusId, ponovno_svima: !!ponovnoSvima }); },
  uvoz(formData) { return API.upload('ucenici/uvoz', formData); },
};
