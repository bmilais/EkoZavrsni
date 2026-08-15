/* Model ciklusa - sve API operacije vezane uz cikluse */
const CiklusModel = {
  list() { return API.get('ciklusi'); },
  get(id) { return API.get('ciklusi?id=' + id); },
  spremi(data) { return API.post('ciklusi/spremi', data); },
  status(id, status) { return API.post('ciklusi/status', { id, status }); },
  obrisi(id) { return API.post('ciklusi/obrisi', { id }); },
};
