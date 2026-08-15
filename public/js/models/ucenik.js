/* Model učenika - sve API operacije vezane uz učenike */
const UcenikModel = {
  list() { return API.get('ucenici'); },
  get(id) { return API.get('ucenici?id=' + id); },
  spremi(data) { return API.post('ucenici/spremi', data); },
  obrisi(id) { return API.post('ucenici/obrisi', { id }); },
  posaljiLink(id) { return API.post('ucenici/posalji-link', { id }); },
  uvoz(formData) { return API.upload('ucenici/uvoz', formData); },
};
