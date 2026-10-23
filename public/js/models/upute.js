/* Model uputa - PDF upute za učenike odabranog ciklusa */
const UputeModel = {
  info(ciklusId) { return API.get('upute', { ciklus_id: ciklusId }); },
  upload(formData) { return API.upload('upute/upload', formData); },
  posaljiUceniku(ucenikId) { return API.post('upute/posalji-uceniku', { ucenik_id: ucenikId }); },
  posalji(ciklusId, ponovnoSvima) { return API.post('upute/posalji', { ciklus_id: ciklusId, ponovno_svima: !!ponovnoSvima }); },
  preuzmiUrl(ciklusId) { return 'api/upute/preuzmi?ciklus_id=' + encodeURIComponent(ciklusId); },
};
