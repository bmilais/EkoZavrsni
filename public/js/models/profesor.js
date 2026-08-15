/* Model profesora - sve API operacije vezane uz profesore */
const ProfesorModel = {
  list() { return API.get('profesori'); },
  get(id) { return API.get('profesori?id=' + id); },
  spremi(data) { return API.post('profesori/spremi', data); },
  obrisi(id) { return API.post('profesori/obrisi', { id }); },
};
