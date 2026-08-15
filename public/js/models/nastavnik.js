/* Model nastavničkog panela - sve API operacije vezane uz nastavnika */
const NastavnikModel = {
  teme() { return API.get('nastavnik/teme'); },
  odabiri(temaId, filter) { return API.get('nastavnik/odabiri', { tema_id: temaId, filter }); },
};
