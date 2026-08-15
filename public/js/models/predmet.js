/* Model predmeta - sve API operacije vezane uz predmete */
const PredmetModel = {
  list() { return API.get('predmeti'); },
  get(id) { return API.get('predmeti?id=' + id); },
  spremi(data) { return API.post('predmeti/spremi', data); },
  obrisi(id) { return API.post('predmeti/obrisi', { id }); },
};
