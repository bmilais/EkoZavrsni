/* Model razreda - sve API operacije vezane uz razrede */
const RazredModel = {
  list() { return API.get('razredi'); },
  get(id) { return API.get('razredi?id=' + id); },
  spremi(data) { return API.post('razredi/spremi', data); },
  obrisi(id) { return API.post('razredi/obrisi', { id }); },
};
