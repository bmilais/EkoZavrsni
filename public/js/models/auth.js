/* Model prijave - sve API operacije vezane uz auth */
const AuthModel = {
  login(data) { return API.post('auth/login', data); },
  logout() { return API.post('auth/logout'); },
  user() { return API.get('auth/user'); },
  prijava(token) { return API.post('auth/prijava', { token }); },
};
