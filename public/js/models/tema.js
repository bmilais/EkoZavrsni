/* Model tema - sve API operacije vezane uz teme */
const TemaModel = {
  list() { return API.get('teme'); },
  get(id) { return API.get('teme?id=' + id); },
  opcije() { return API.get('teme/opcije'); },
  spremi(data) { return API.post('teme/spremi', data); },
  obrisi(id) { return API.post('teme/obrisi', { id }); },
  uvoz(formData) { return API.upload('teme/uvoz', formData); },
};
