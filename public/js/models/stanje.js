/* Model stanja - sve API operacije vezane uz stanje zauzetosti */
const StanjeModel = {
  get(ciklusId) { return API.get('stanje?ciklus_id=' + ciklusId); },
};
